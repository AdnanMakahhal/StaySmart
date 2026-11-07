<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: status.php'); exit; }
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['booking_status_csrf'] ?? '', $token) || empty($_SESSION['booking_status_csrf'])) { http_response_code(403); exit('Refresh the bookings page and try again.'); }
$id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
$actions = array_intersect(['checkin_status', 'checkout_status', 'booked', 'available'], array_keys($_POST));
if (!$id || count($actions) !== 1) { http_response_code(400); exit('A valid booking and action are required.'); }
$action = reset($actions);
try {
    $con->begin_transaction();
    $query = $con->prepare('SELECT * FROM bookings WHERE booking_id = ? FOR UPDATE');
    $query->bind_param('i', $id);
    $query->execute();
    $booking = $query->get_result()->fetch_assoc();
    if (!$booking) throw new RuntimeException('Booking no longer exists.');
    if ($action === 'checkout_status') {
        $archive = $con->prepare('INSERT INTO archive_bookings (booking_id, user_id, room_id, room_name, check_in, check_out, total_price, booked_at, is_sample) SELECT booking_id, user_id, room_id, room_name, check_in, check_out, total_price, booked_at, is_sample FROM bookings WHERE booking_id = ?');
        $archive->bind_param('i', $id);
        $archive->execute();
    }
    if (in_array($action, ['checkout_status', 'available'], true)) {
        $update = $con->prepare("UPDATE rooms SET quantity = quantity + 1, availability = 'available' WHERE id = ?");
        $update->bind_param('i', $booking['room_id']);
        if (empty($booking['is_sample'])) $update->execute();
        $delete = $con->prepare('DELETE FROM bookings WHERE booking_id = ?');
        $delete->bind_param('i', $id);
        $delete->execute();
    } else {
        $status = $action === 'checkin_status' ? 'checked_in' : 'booked';
        $update = $con->prepare('UPDATE bookings SET status = ? WHERE booking_id = ?');
        $update->bind_param('si', $status, $id);
        $update->execute();
    }
    $con->commit();
    header('Location: ' . ($action === 'checkout_status' ? 'checkedout.php' : 'status.php'));
} catch (Throwable $error) {
    $con->rollback();
    error_log('StaySmart booking status: ' . $error->getMessage());
    http_response_code(409);
    exit('The booking could not be updated. Refresh the booking list and try again.');
}
