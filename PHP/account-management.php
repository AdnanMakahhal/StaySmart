<?php

function staySmartEnsureAccounts(mysqli $db): void
{
    static $ready = false;
    if ($ready) return;
    $columns = [];
    $result = $db->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('admin', 'users', 'bookings', 'archive_bookings')");
    while ($row = $result->fetch_assoc()) $columns[$row['TABLE_NAME']][$row['COLUMN_NAME']] = true;
    foreach (['admin', 'users'] as $table) {
        $definitions = [
            'is_blocked' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'deleted_at' => 'DATETIME NULL DEFAULT NULL',
            'created_at' => 'DATETIME NULL DEFAULT NULL',
            'last_login_at' => 'DATETIME NULL DEFAULT NULL',
        ];
        if ($table === 'admin') $definitions['role'] = "ENUM('admin', 'manager') NOT NULL DEFAULT 'admin'";
        foreach ($definitions as $column => $definition) {
            if (!isset($columns[$table][$column])) {
                try { $db->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition"); }
                catch (mysqli_sql_exception $error) { if ((int) $error->getCode() !== 1060) throw $error; }
            }
        }
    }
    foreach (['bookings', 'archive_bookings'] as $bookingTable) {
        if (!isset($columns[$bookingTable]['booked_at'])) {
            // Existing bookings retain unknown times instead of receiving a fabricated timestamp.
            try { $db->query("ALTER TABLE `$bookingTable` ADD COLUMN booked_at DATETIME NULL DEFAULT NULL"); }
            catch (mysqli_sql_exception $error) { if ((int) $error->getCode() !== 1060) throw $error; }
        }
    }
    foreach (['message' => 'TEXT NULL', 'price' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00'] as $column => $definition) {
        if (!isset($columns['bookings'][$column])) {
            try { $db->query("ALTER TABLE bookings ADD COLUMN `$column` $definition"); }
            catch (mysqli_sql_exception $error) { if ((int) $error->getCode() !== 1060) throw $error; }
        }
    }
    $db->query("CREATE TABLE IF NOT EXISTS account_visits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        account_type ENUM('admin','user') NOT NULL,
        account_id INT UNSIGNED NOT NULL,
        page_path VARCHAR(255) NOT NULL,
        visited_at DATETIME NOT NULL,
        KEY idx_visits_account (account_type, account_id, visited_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS account_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        account_type ENUM('admin','user') NOT NULL,
        account_id INT UNSIGNED NOT NULL,
        event_type VARCHAR(30) NOT NULL,
        actor_admin_id INT UNSIGNED NULL,
        occurred_at DATETIME NOT NULL,
        KEY idx_events_account (account_type, account_id, occurred_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->query("CREATE TABLE IF NOT EXISTS account_links (
        admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        current_type ENUM('admin','user') NOT NULL,
        UNIQUE KEY uq_account_link_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

function staySmartAccountTable(string $type): string
{
    if (!in_array($type, ['admin', 'user'], true)) throw new RuntimeException('Invalid account type.');
    return $type === 'admin' ? 'admin' : 'users';
}

function staySmartRecordVisit(mysqli $db, string $type, int $id, string $path, ?DateTimeImmutable $now = null): void
{
    $now = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'));
    $table = staySmartAccountTable($type);
    $db->begin_transaction();
    try {
        $lock = $db->prepare("SELECT id FROM `$table` WHERE id = ? FOR UPDATE");
        $lock->bind_param('i', $id);
        $lock->execute();
        $lock->get_result()->fetch_assoc();
        $last = $db->prepare('SELECT visited_at FROM account_visits WHERE account_type = ? AND account_id = ? AND is_sample = 0 ORDER BY visited_at DESC LIMIT 1');
        $last->bind_param('si', $type, $id);
        $last->execute();
        $visit = $last->get_result()->fetch_assoc();
        if (!$visit || $now->getTimestamp() - (new DateTimeImmutable($visit['visited_at'], new DateTimeZone('UTC')))->getTimestamp() >= 86400) {
            $time = $now->format('Y-m-d H:i:s');
            $insert = $db->prepare('INSERT INTO account_visits (account_type, account_id, page_path, visited_at) VALUES (?, ?, ?, ?)');
            $insert->bind_param('siss', $type, $id, $path, $time);
            $insert->execute();
        }
        $db->commit();
    } catch (Throwable $error) { $db->rollback(); throw $error; }
}

function staySmartAccountEvent(mysqli $db, string $type, int $id, string $event, ?int $actor = null): void
{
    $time = gmdate('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO account_events (account_type, account_id, event_type, actor_admin_id, occurred_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sisis', $type, $id, $event, $actor, $time);
    $stmt->execute();
}

function staySmartCanManage(string $actorRole, string $targetType): bool
{
    return $actorRole === 'admin' || ($actorRole === 'manager' && $targetType === 'user');
}

function staySmartAccountTime(?string $time): string
{
    if (!$time) return 'Not recorded';
    return (new DateTimeImmutable($time, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Dubai'))->format('d M Y, H:i:s');
}
