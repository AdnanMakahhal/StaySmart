<?php
require_once __DIR__ . '/booking-identity.php';
require_once __DIR__ . '/payments.php';
function staySmartEnsureBookingChanges(mysqli $db): void
{
    staySmartEnsurePayments($db);
    $db->query("CREATE TABLE IF NOT EXISTS cancelled_bookings (
        booking_id INT UNSIGNED PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
        room_id INT UNSIGNED NOT NULL, room_name VARCHAR(150) NOT NULL,
        user_name VARCHAR(100) NOT NULL, check_in DATE NOT NULL, check_out DATE NOT NULL,
        guests INT UNSIGNED NOT NULL, phone_number VARCHAR(30) NOT NULL,
        message TEXT NULL, price DECIMAL(10,2) NOT NULL, total_price DECIMAL(12,2) NOT NULL,
        booked_at DATETIME NULL, cancelled_at DATETIME NOT NULL,
        KEY idx_cancelled_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    require_once __DIR__.'/data-flags.php';
    staySmartEnsureSampleFlags($db);
}

function staySmartCanChangeBooking(array $booking, ?DateTimeImmutable $now = null): bool
{
    $zone = new DateTimeZone('Asia/Dubai');
    $now = $now ?? new DateTimeImmutable('now', $zone);
    $checkIn = new DateTimeImmutable($booking['check_in'] . ' 00:00:00', $zone);
    return ($booking['status'] ?? '') === 'booked' && $checkIn->getTimestamp() - $now->getTimestamp() >= 72 * 3600;
}

function staySmartChangeBooking(mysqli $db, int $userId, int $bookingId, string $action, array $input): void
{
    if (!in_array($action, ['edit', 'cancel'], true)) throw new RuntimeException('Choose a valid booking action.');
    staySmartEnsureBookingChanges($db);
    $db->begin_transaction();
    try {
        $query = $db->prepare('SELECT bookings.*, status AS status FROM bookings WHERE booking_id = ? AND user_id = ? FOR UPDATE');
        $query->bind_param('ii', $bookingId, $userId);
        $query->execute();
        $booking = $query->get_result()->fetch_assoc();
        if (!$booking) throw new RuntimeException('This booking is unavailable or no longer active.');
        if (!staySmartCanChangeBooking($booking)) throw new RuntimeException('Changes and cancellations close 72 hours before check-in. Contact our team for help.');
        if ($action === 'cancel') {
            $archive = $db->prepare('INSERT INTO cancelled_bookings (booking_id, user_id, room_id, room_name, user_name, check_in, check_out, guests, phone_number, message, price, total_price, booked_at, cancelled_at, is_sample) SELECT booking_id, user_id, room_id, room_name, user_name, check_in, check_out, guests, phone_number, message, price, total_price, booked_at, UTC_TIMESTAMP(), is_sample FROM bookings WHERE booking_id = ? AND user_id = ?');
            $archive->bind_param('ii', $bookingId, $userId);
            $archive->execute();
            $delete = $db->prepare('DELETE FROM bookings WHERE booking_id = ? AND user_id = ?');
            $delete->bind_param('ii', $bookingId, $userId);
            $delete->execute();
            $restore = $db->prepare("UPDATE rooms SET quantity = quantity + 1, availability = 'available' WHERE id = ?");
            $restore->bind_param('i', $booking['room_id']);
            if (empty($booking['is_sample'])) $restore->execute();
            $payment = $db->prepare("UPDATE booking_payments SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'unpaid'");
            $payment->bind_param('ii', $bookingId, $userId); $payment->execute();
        } else {
            $zone = new DateTimeZone('Asia/Dubai');
            $in = is_string($input['check_in'] ?? null) ? $input['check_in'] : '';
            $out = is_string($input['check_out'] ?? null) ? $input['check_out'] : '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $in) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out)) throw new RuntimeException('Choose valid check-in and check-out dates.');
            $start = DateTimeImmutable::createFromFormat('!Y-m-d', $in, $zone);
            $end = DateTimeImmutable::createFromFormat('!Y-m-d', $out, $zone);
            if (!$start || !$end || $start->format('Y-m-d') !== $in || $end->format('Y-m-d') !== $out || $end <= $start) throw new RuntimeException('Check-out must be after a valid check-in date.');
            if (!staySmartCanChangeBooking(['status' => 'booked', 'check_in' => $in])) throw new RuntimeException('Your new check-in must be at least 72 hours from now.');
            $price = (float) $booking['price'];
            $oldNights = (new DateTimeImmutable($booking['check_in']))->diff(new DateTimeImmutable($booking['check_out']))->days;
            if ($price == 0 && (float) $booking['total_price'] > 0 && $oldNights > 0) $price = (float) $booking['total_price'] / $oldNights;
            $total = round($price * $start->diff($end)->days, 2);
            $column = $db->query("SHOW COLUMNS FROM bookings LIKE 'total_price'")->fetch_assoc();
            $generated = stripos($column['Extra'] ?? '', 'GENERATED') !== false;
            $update = $db->prepare('UPDATE bookings SET check_in = ?, check_out = ?, price = ?' . ($generated ? '' : ', total_price = ?') . ' WHERE booking_id = ? AND user_id = ?');
            if ($generated) $update->bind_param('ssdii', $in, $out, $price, $bookingId, $userId);
            else $update->bind_param('ssddii', $in, $out, $price, $total, $bookingId, $userId);
            $update->execute();
            $payment = $db->prepare("UPDATE booking_payments SET amount = ? WHERE booking_id = ? AND user_id = ? AND status = 'unpaid'");
            $payment->bind_param('dii', $total, $bookingId, $userId); $payment->execute();
        }
        staySmartBookingEvent($db, $userId, ($action === 'edit' ? 'edited booking #' : 'cancelled booking #') . $bookingId);
        $db->commit();
    } catch (Throwable $error) { $db->rollback(); throw $error; }
}
