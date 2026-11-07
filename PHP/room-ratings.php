<?php

function staySmartEnsureRoomRatingsTable(mysqli $connection): void
{
    $tableCheck = $connection->query(
        "SELECT COUNT(*) AS table_count
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'room_reviews'"
    );
    $tableExists = (int) $tableCheck->fetch_assoc()['table_count'] > 0;

    if (!$tableExists) {
        $connection->query(
            'CREATE TABLE room_reviews (
                booking_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                room_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                review_text VARCHAR(1000) NOT NULL DEFAULT \'\',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (booking_id),
                KEY idx_room_reviews_room_id (room_id),
                KEY idx_room_reviews_user_id (user_id),
                CONSTRAINT chk_room_reviews_rating CHECK (rating BETWEEN 1 AND 5)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $connection->query('UPDATE rooms SET rating = 0');
    }
    $textColumn = $connection->query("SHOW COLUMNS FROM room_reviews LIKE 'review_text'");
    if ($textColumn->num_rows === 0) {
        try { $connection->query("ALTER TABLE room_reviews ADD COLUMN review_text VARCHAR(1000) NOT NULL DEFAULT ''"); }
        catch (mysqli_sql_exception $error) { if ((int) $error->getCode() !== 1060) throw $error; }
    }
    require_once __DIR__.'/data-flags.php';
    staySmartEnsureSampleFlags($connection);
}

function staySmartLatestReviews(mysqli $connection, ?int $userId = null): array
{
    $today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Dubai')))->format('Y-m-d');
    $sql = "SELECT r.booking_id, r.user_id, r.rating, r.review_text, r.created_at, r.is_sample,
        COALESCE(NULLIF(a.full_name, ''), NULLIF(u.full_name, ''), u.username) AS guest_name,
        b.room_name
        FROM room_reviews r
        INNER JOIN archive_bookings b ON b.booking_id = r.booking_id AND b.user_id = r.user_id AND b.room_id = r.room_id
        INNER JOIN users u ON u.id = r.user_id
        LEFT JOIN account_links l ON l.user_id = u.id AND l.current_type = 'admin'
        LEFT JOIN admin a ON a.id = l.admin_id
        WHERE b.check_out <= ? AND r.rating BETWEEN 1 AND 5
          AND ((l.admin_id IS NULL AND u.deleted_at IS NULL) OR (l.admin_id IS NOT NULL AND a.deleted_at IS NULL))";
    if ($userId !== null) $sql .= ' AND r.user_id = ?';
    $sql .= ' ORDER BY r.created_at DESC, r.booking_id DESC LIMIT 4';
    $query = $connection->prepare($sql);
    if ($userId !== null) $query->bind_param('si', $today, $userId);
    else $query->bind_param('s', $today);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

function staySmartReviewAvatar(int $userId): string
{
    $path = dirname(__DIR__) . '/Assets/profile-photos/user-' . $userId . '.png';
    return is_file($path) ? 'Assets/profile-photos/user-' . $userId . '.png?v=' . filemtime($path) : 'Assets/guest-avatar-default.png';
}

