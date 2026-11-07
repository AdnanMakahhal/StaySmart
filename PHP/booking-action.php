<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/booking-identity.php';
$bookingUserId = staySmartBookingUserId($con);
require_once __DIR__ . '/booking-management.php';
if ($bookingUserId === null) { http_response_code(403); exit('Sign in to manage your booking.'); }
$id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
$target = $id && $id > 0 ? '../booking-details.php?id=' . $id : '../mybooking.php';
try {
    $token = $_POST['csrf_token'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || empty($_SESSION['cancel_booking_csrf']) || !hash_equals($_SESSION['cancel_booking_csrf'], $token)) throw new RuntimeException('Your session expired. Refresh the booking and try again.');
    if (!$id || $id < 1) throw new RuntimeException('Choose a valid booking.');
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    staySmartChangeBooking($con, $bookingUserId, $id, $action, $_POST);
    $_SESSION['flash_alert'] = ['type' => 'success', 'title' => $action === 'cancel' ? 'Booking cancelled' : 'Dates updated', 'message' => $action === 'cancel' ? 'Your booking has been cancelled. Its details remain in your history.' : 'Your new dates and updated room total have been saved.'];
} catch (mysqli_sql_exception $error) {
    error_log('Booking action failed: ' . $error->getMessage());
    $_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Could not update booking', 'message' => 'Please refresh your booking and try again.'];
} catch (RuntimeException $error) {
    $_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Booking unchanged', 'message' => $error->getMessage()];
}
header('Location: ' . $target);
exit;
