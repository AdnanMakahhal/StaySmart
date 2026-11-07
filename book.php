<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/booking-identity.php';
require_once __DIR__ . '/PHP/payments.php';
staySmartEnsurePayments($con);
require_once 'PHP/connect.php';
require_once 'PHP/room-management.php';
staySmartEnsureRoomManagementColumns($con);

function staySmartBookingRedirect(string $message, ?string $roomName = null): void
{
    $_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Check your booking', 'message' => $message];
    $target = 'booking-form.php';
    $requestedRoom = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
    if ($requestedRoom && $requestedRoom > 0) {
        $target .= '?id=' . $requestedRoom;
    } elseif ($roomName !== null && $roomName !== '') {
        $target .= '?roomname=' . rawurlencode($roomName);
    }
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book'])) {
    header('Location: rooms.php');
    exit;
}

$bookingUserId = staySmartBookingUserId($con);
if ($bookingUserId === null) {
    $_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Please sign in first', 'message' => 'Sign in before sending a booking request.'];
    $requestedId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
    header('Location: login.php' . ($requestedId && $requestedId > 0 ? '?next=' . rawurlencode('booking-form.php?id=' . $requestedId) : ''));
    exit;
}
$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || empty($_SESSION['booking_csrf']) || !hash_equals($_SESSION['booking_csrf'], $submittedToken)) {
    staySmartBookingRedirect('Your booking session expired. Please check the details and try again.');
}
$_SESSION['booking_old'] = [];
$checkoutToken = $_POST['checkout_token'] ?? '';
if (is_string($checkoutToken) && $checkoutToken !== '' && hash_equals($_SESSION['completed_checkout_token'] ?? '', $checkoutToken)) {
    header('Location: index.php'); exit;
}
$draft = $_SESSION['checkout_draft'] ?? null;
if (!$draft || !is_string($checkoutToken) || !hash_equals($draft['token'], $checkoutToken) || (int)$draft['user_id'] !== $bookingUserId || time() - $draft['created_at'] > 1800) {
    staySmartBookingRedirect('Please review your stay and payment details at checkout before confirming.');
}
foreach (['room_id','check_in','check_out','guests','phone_number','mbox'] as $field) $_POST[$field] = (string)$draft[$field];
foreach (['room_id', 'check_in', 'check_out', 'guests', 'phone_number', 'mbox'] as $field) {
    if (is_string($_POST[$field] ?? null)) $_SESSION['booking_old'][$field] = substr($_POST[$field], 0, 2000);
}

$roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
$userId = $bookingUserId;
$checkInRaw = is_string($_POST['check_in'] ?? null) ? $_POST['check_in'] : '';
$checkOutRaw = is_string($_POST['check_out'] ?? null) ? $_POST['check_out'] : '';
$guestCount = filter_var($_POST['guests'] ?? null, FILTER_VALIDATE_INT);
$phone = is_string($_POST['phone_number'] ?? null) ? trim($_POST['phone_number']) : '';
$message = is_string($_POST['mbox'] ?? null) ? trim($_POST['mbox']) : '';

if (!$roomId || $roomId < 1 || !$guestCount || $guestCount < 1 || $phone === '' || strlen($phone) > 30 || strlen($message) > 2000) {
    staySmartBookingRedirect('Please provide valid booking details.');
}

$bookingZone = new DateTimeZone('Asia/Dubai');
$checkIn = DateTimeImmutable::createFromFormat('!Y-m-d', $checkInRaw, $bookingZone);
$checkOut = DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutRaw, $bookingZone);
$today = new DateTimeImmutable('today', $bookingZone);
if (!$checkIn || !$checkOut || $checkIn->format('Y-m-d') !== $checkInRaw ||
    $checkOut->format('Y-m-d') !== $checkOutRaw || $checkOut <= $checkIn) {
    staySmartBookingRedirect('Please choose valid check-in and check-out dates.');
}
if ($checkIn < $today) {
    staySmartBookingRedirect('Check-in cannot be in the past. Choose today or a future date.');
}

$roomName = null;
try {
    $con->begin_transaction();
    $roomQuery = $con->prepare('SELECT room_name, price, quantity, capacity, is_visible, is_open FROM rooms WHERE id = ? FOR UPDATE');
    $roomQuery->bind_param('i', $roomId);
    $roomQuery->execute();
    $room = $roomQuery->get_result()->fetch_assoc();
    $roomQuery->close();

    if (!$room) {
        throw new RuntimeException('This room is no longer available.');
    }
    $roomName = $room['room_name'];
    if ((int) $room['is_visible'] !== 1 || (int) $room['is_open'] !== 1 || (int) $room['quantity'] < 1) {
        throw new RuntimeException('This room is not accepting bookings right now.');
    }
    if ((float)$room['price'] !== (float)$draft['price']) throw new RuntimeException('The room rate has changed. Please review checkout again before confirming.');
    if ($guestCount > (int) $room['capacity']) {
        throw new RuntimeException('The number of guests exceeds this room’s capacity.');
    }

    $userQuery = $con->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
    $userQuery->bind_param('i', $userId);
    $userQuery->execute();
    $user = $userQuery->get_result()->fetch_assoc();
    $userQuery->close();
    if (!$user) {
        throw new RuntimeException('Your account could not be verified. Please sign in again.');
    }
    $user['username'] = $signedInAccount['username'];

    $bookingDate = $today->format('Y-m-d');
    $price = (float) $room['price'];
    $bookedAt = gmdate('Y-m-d H:i:s');
    $totalColumn = $con->query("SHOW COLUMNS FROM bookings LIKE 'total_price'")->fetch_assoc();
    $generatedTotal = stripos($totalColumn['Extra'] ?? '', 'GENERATED') !== false;
    $insert = $con->prepare('INSERT INTO bookings (user_id, room_id, user_name, room_name, check_in, check_out, guests, phone_number, message, booking_date, price, booked_at' . ($generatedTotal ? '' : ', total_price') . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?' . ($generatedTotal ? '' : ', ?') . ')');
    if ($generatedTotal) {
    $insert->bind_param('iissssisssds', $userId, $roomId, $user['username'], $room['room_name'], $checkInRaw, $checkOutRaw, $guestCount, $phone, $message, $bookingDate, $price, $bookedAt);
    } else {
        $totalPrice = (float) $checkIn->diff($checkOut)->days * $price;
        $insert->bind_param('iissssisssdsd', $userId, $roomId, $user['username'], $room['room_name'], $checkInRaw, $checkOutRaw, $guestCount, $phone, $message, $bookingDate, $price, $bookedAt, $totalPrice);
    }
    $insert->execute();
    $newBookingId = (int) $con->insert_id;
    $insert->close();
    $paymentAmount = round((float)$checkIn->diff($checkOut)->days * $price, 2);
    $payment = $con->prepare("INSERT INTO booking_payments (booking_id,user_id,method,status,amount,currency,created_at) VALUES (?,?,'pay_at_property','unpaid',?,'USD',UTC_TIMESTAMP())");
    $payment->bind_param('iid', $newBookingId, $userId, $paymentAmount); $payment->execute();

    $updateRoom = $con->prepare("UPDATE rooms SET quantity = quantity - 1, availability = IF(quantity <= 1, 'not available', availability) WHERE id = ? AND quantity > 0 AND is_visible = 1 AND is_open = 1");
    $updateRoom->bind_param('i', $roomId);
    $updateRoom->execute();
    if ($updateRoom->affected_rows !== 1) {
        $updateRoom->close();
        throw new RuntimeException('This room was just booked. Please choose another room.');
    }
    $updateRoom->close();
    $con->commit();
} catch (Throwable $error) {
    $con->rollback();
    error_log('StaySmart booking failed: ' . $error->getMessage());
    staySmartBookingRedirect($error instanceof RuntimeException && !($error instanceof mysqli_sql_exception) ? $error->getMessage() : 'Your booking could not be completed. Please try again.', $roomName);
}

$_SESSION['booking_success_id'] = $newBookingId;
$_SESSION['completed_checkout_token'] = $checkoutToken;
unset($_SESSION['booking_old']);
unset($_SESSION['checkout_draft']);
header('Location: index.php');
exit;
