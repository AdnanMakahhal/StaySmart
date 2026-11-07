<?php
require_once __DIR__ . '/account-management.php';

function staySmartManageAccount(mysqli $db, int $actorId, array $post): string
{
    $value = static fn($key) => is_string($post[$key] ?? null) ? trim($post[$key]) : '';
    $action = $value('action');
    if (!in_array($action, ['create', 'block', 'unblock', 'delete', 'change_role'], true)) throw new RuntimeException('Choose a valid account action.');
    $db->begin_transaction();
    try {
        // Serialize staff changes and recheck the acting account while holding locks.
        $staff = $db->query('SELECT id, role, is_blocked, deleted_at FROM admin ORDER BY id FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
        $actor = null;
        foreach ($staff as $person) if ((int) $person['id'] === $actorId) $actor = $person;
        if (!$actor || $actor['is_blocked'] || $actor['deleted_at']) throw new RuntimeException('Your staff account is no longer active.');
        if ($action === 'create') {
            $role = $value('role');
            if (!in_array($role, ['admin', 'manager', 'user'], true) || ($actor['role'] !== 'admin' && $role !== 'user')) throw new RuntimeException('You can only create accounts allowed by your role.');
            $name = $value('full_name');
            $username = $value('username');
            $email = $value('email');
            $password = is_string($post['password'] ?? null) ? $post['password'] : '';
            if ($name === '' || strlen($name) > 150 || $username === '' || strlen($username) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || strlen($password) < 10 || strlen($password) > 72) throw new RuntimeException('Enter a valid name, username, email and a password of 10 to 72 characters.');
            foreach (['users', 'admin'] as $table) {
                $check = $db->prepare("SELECT id FROM `$table` WHERE username = ? OR email = ? LIMIT 1 FOR UPDATE");
                $check->bind_param('ss', $username, $email);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) throw new RuntimeException('That username or email is already in use.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $now = gmdate('Y-m-d H:i:s');
            $type = $role === 'user' ? 'user' : 'admin';
            if ($type === 'user') {
                $insert = $db->prepare('INSERT INTO users (username, full_name, email, password, created_at) VALUES (?, ?, ?, ?, ?)');
                $insert->bind_param('sssss', $username, $name, $email, $hash, $now);
            } else {
                $insert = $db->prepare('INSERT INTO admin (username, full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->bind_param('ssssss', $username, $name, $email, $hash, $role, $now);
            }
            $insert->execute();
            staySmartAccountEvent($db, $type, (int) $db->insert_id, 'created', $actorId);
            $message = ucfirst($role) . ' account created.';
        } else {
            $type = $value('account_type');
            $table = staySmartAccountTable($type);
            $id = filter_var($post['account_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1 || !staySmartCanManage($actor['role'], $type)) throw new RuntimeException('You do not have permission to manage this account.');
            if ($type === 'admin' && $id === $actorId) throw new RuntimeException('You cannot change your own role, block or delete your own account.');
            $target = $db->prepare("SELECT *, password AS account_password FROM `$table` WHERE id = ? FOR UPDATE");
            $target->bind_param('i', $id);
            $target->execute();
            $account = $target->get_result()->fetch_assoc();
            if (!$account || $account['deleted_at']) throw new RuntimeException('That account is no longer active.');
            if ($type === 'admin' && $account['role'] === 'admin' && $action !== 'unblock') {
                $activeAdmins = array_filter($staff, static fn($person) => $person['role'] === 'admin' && !$person['is_blocked'] && !$person['deleted_at']);
                if (count($activeAdmins) <= 1 && !$account['is_blocked']) throw new RuntimeException('At least one active administrator must remain.');
            }
            $bookingUserId = $type === 'user' ? $id : null;
            if ($action === 'delete' && $type === 'admin') {
                $linked = $db->prepare('SELECT user_id FROM account_links WHERE admin_id = ?');
                $linked->bind_param('i', $id);
                $linked->execute();
                $linkedUser = $linked->get_result()->fetch_assoc();
                $bookingUserId = $linkedUser ? (int) $linkedUser['user_id'] : null;
            }
            if ($action === 'delete' && $bookingUserId !== null) {
                $check = $db->prepare('SELECT booking_id FROM bookings WHERE user_id = ? LIMIT 1 FOR UPDATE');
                $check->bind_param('i', $bookingUserId);
                $check->execute();
                if ($check->get_result()->fetch_assoc()) throw new RuntimeException('This user has active bookings. Complete or cancel them before deleting the account. You can block the account now.');
            }
            if ($action === 'change_role') {
                if ($actor['role'] !== 'admin') throw new RuntimeException('Only administrators can change account roles.');
                $newRole = $value('role');
                if (!in_array($newRole, ['admin', 'manager', 'user'], true)) throw new RuntimeException('Select Admin, Manager or User.');
                $message = staySmartChangeRole($db, $type, $id, $account, $newRole, $actorId);
            } elseif ($action === 'delete') {
                $now = gmdate('Y-m-d H:i:s');
                $stmt = $db->prepare("UPDATE `$table` SET deleted_at = ?, is_blocked = 1 WHERE id = ?");
                $stmt->bind_param('si', $now, $id);
            } else {
                $blocked = $action === 'block' ? 1 : 0;
                $stmt = $db->prepare("UPDATE `$table` SET is_blocked = ? WHERE id = ?");
                $stmt->bind_param('ii', $blocked, $id);
            }
            if ($action !== 'change_role') {
                $stmt->execute();
                staySmartAccountEvent($db, $type, $id, $action, $actorId);
                $message = $action === 'delete' ? 'Account deleted. Its booking and activity history has been preserved.' : ($action === 'block' ? 'Account blocked. Existing sessions will be denied on their next request.' : 'Account unblocked. The user can sign in again.');
            }
        }
        $db->commit();
        return $message;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function staySmartChangeRole(mysqli $db, string $type, int $id, array $account, string $newRole, int $actorId): string
{
    $oldRole = $type === 'user' ? 'user' : $account['role'];
    if ($oldRole === $newRole) return 'This account already has the selected role.';
    $newType = $newRole === 'user' ? 'user' : 'admin';
    $newId = $id;
    if ($newType === $type) {
        $update = $db->prepare('UPDATE admin SET role = ? WHERE id = ?');
        $update->bind_param('si', $newRole, $id);
        $update->execute();
    } else {
        $column = $type === 'user' ? 'user_id' : 'admin_id';
        $linkQuery = $db->prepare("SELECT * FROM account_links WHERE `$column` = ? FOR UPDATE");
        $linkQuery->bind_param('i', $id);
        $linkQuery->execute();
        $link = $linkQuery->get_result()->fetch_assoc();
        $newTable = staySmartAccountTable($newType);
        $newId = $link ? (int) $link[$newType === 'admin' ? 'admin_id' : 'user_id'] : 0;
        $duplicate = $db->prepare("SELECT id FROM `$newTable` WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1 FOR UPDATE");
        $duplicate->bind_param('ssi', $account['username'], $account['email'], $newId);
        $duplicate->execute();
        if ($duplicate->get_result()->fetch_assoc()) throw new RuntimeException('Another account already uses this email or username. Resolve that conflict before changing the role.');
        if (!$newId) {
            $insert = $db->prepare("INSERT INTO `$newTable` (username, full_name, email, password) VALUES (?, ?, ?, ?)");
            $insert->bind_param('ssss', $account['username'], $account['full_name'], $account['email'], $account['account_password']);
            $insert->execute();
            $newId = (int) $db->insert_id;
        }
        $roleClause = $newType === 'admin' ? ', role = ?' : '';
        $update = $db->prepare("UPDATE `$newTable` SET username = ?, full_name = ?, email = ?, password = ?, is_blocked = ?, created_at = ?, last_login_at = ?, deleted_at = NULL $roleClause WHERE id = ?");
        if ($newType === 'admin') {
            $update->bind_param('ssssisssi', $account['username'], $account['full_name'], $account['email'], $account['account_password'], $account['is_blocked'], $account['created_at'], $account['last_login_at'], $newRole, $newId);
        } else {
            $update->bind_param('ssssissi', $account['username'], $account['full_name'], $account['email'], $account['account_password'], $account['is_blocked'], $account['created_at'], $account['last_login_at'], $newId);
        }
        $update->execute();
        // Retain the source record so bookings, reviews and identifiers remain intact.
        $oldTable = staySmartAccountTable($type);
        $now = gmdate('Y-m-d H:i:s');
        $retire = $db->prepare("UPDATE `$oldTable` SET deleted_at = ? WHERE id = ?");
        $retire->bind_param('si', $now, $id);
        $retire->execute();
        $adminId = $newType === 'admin' ? $newId : $id;
        $userId = $newType === 'user' ? $newId : $id;
        $saveLink = $db->prepare('INSERT INTO account_links (admin_id, user_id, current_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE current_type = VALUES(current_type)');
        $saveLink->bind_param('iis', $adminId, $userId, $newType);
        $saveLink->execute();
        foreach (['account_visits', 'account_events'] as $historyTable) {
            $move = $db->prepare("UPDATE `$historyTable` SET account_type = ?, account_id = ? WHERE account_type = ? AND account_id = ?");
            $move->bind_param('sisi', $newType, $newId, $type, $id);
            $move->execute();
        }
    }
    staySmartAccountEvent($db, $newType, $newId, 'role:' . $oldRole . '->' . $newRole, $actorId);
    return 'Role changed to ' . ucfirst($newRole) . '. Login details and history have been retained. The account may need to sign in again.';
}
