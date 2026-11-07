<?php

function staySmartEnsureRoomManagementColumns(mysqli $connection): void
{
    require_once __DIR__.'/data-flags.php';
    require_once __DIR__.'/availability.php';
    staySmartEnsureSampleFlags($connection);
    staySmartEnsureDateInventory($connection);
    $result = $connection->query(
        "SELECT COLUMN_NAME
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
            AND table_name = 'rooms'
            AND COLUMN_NAME IN ('is_visible', 'is_open')"
    );
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['COLUMN_NAME']] = true;
    }

    if (!isset($columns['is_visible'])) {
        $connection->query('ALTER TABLE rooms ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1');
    }
    if (!isset($columns['is_open'])) {
        $connection->query('ALTER TABLE rooms ADD COLUMN is_open TINYINT(1) NOT NULL DEFAULT 1');
    }
}

