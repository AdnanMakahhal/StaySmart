<?php
// Keep booking ownership stable while staff retain their staff session and role.
function staySmartBookingUserId(mysqli $db): ?int
{
    if (isset($_SESSION['user_id'])) return (int) $_SESSION['user_id'];
    if (!isset($_SESSION['admin_id'])) return null;
    $staffId = (int) $_SESSION['admin_id'];
    $db->begin_transaction();
    try {
        $staffQuery = $db->prepare('SELECT * FROM admin WHERE id = ? FOR UPDATE');
        $staffQuery->bind_param('i', $staffId);
        $staffQuery->execute();
        $staff = $staffQuery->get_result()->fetch_assoc();
        if (!$staff || $staff['deleted_at'] || $staff['is_blocked']) throw new RuntimeException('This staff account is not active.');
        $linkQuery = $db->prepare("SELECT user_id FROM account_links WHERE admin_id = ? AND current_type = 'admin'");
        $linkQuery->bind_param('i', $staffId);
        $linkQuery->execute();
        $link = $linkQuery->get_result()->fetch_assoc();
        if ($link) {
            $id = (int) $link['user_id'];
        } else {
            // Internal booking record; it cannot be used as a separate login.
            $suffix = bin2hex(random_bytes(6));
            $username = 'staff-' . $staffId . '-' . $suffix;
            $email = 'staff-' . $staffId . '-' . $suffix . '@booking.internal.invalid';
            $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $name = $staff['full_name'] ?: $staff['username'];
            $disabledAt = gmdate('Y-m-d H:i:s');
            $insert = $db->prepare('INSERT INTO users (username, full_name, email, password, deleted_at, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->bind_param('ssssss', $username, $name, $email, $password, $disabledAt, $staff['created_at']);
            $insert->execute();
            $id = (int) $db->insert_id;
            $saveLink = $db->prepare("INSERT INTO account_links (admin_id, user_id, current_type) VALUES (?, ?, 'admin')");
            $saveLink->bind_param('ii', $staffId, $id);
            $saveLink->execute();
        }
        $db->commit();
        return $id;
    } catch (Throwable $error) { $db->rollback(); throw $error; }
}

function staySmartBookingEvent(mysqli $db, int $userId, string $event): void
{
    $query = $db->prepare("SELECT admin_id FROM account_links WHERE user_id = ? AND current_type = 'admin'");
    $query->bind_param('i', $userId);
    $query->execute();
    $staff = $query->get_result()->fetch_assoc();
    staySmartAccountEvent($db, $staff ? 'admin' : 'user', $staff ? (int) $staff['admin_id'] : $userId, $event);
}
