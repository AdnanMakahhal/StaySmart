<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/booking-identity.php';
$bookingUserId = staySmartBookingUserId($con);
require_once __DIR__ . '/PHP/booking-management.php';
require_once __DIR__ . '/PHP/room-image.php';
if ($bookingUserId === null) { header('Location: login.php'); exit; }
staySmartEnsureBookingChanges($con);
if (empty($_SESSION['cancel_booking_csrf'])) $_SESSION['cancel_booking_csrf'] = bin2hex(random_bytes(32));
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$userId = $bookingUserId;
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$booking = null;
if ($id && $id > 0) {
    foreach (['bookings', 'archive_bookings', 'cancelled_bookings'] as $table) {
        $status = $table === 'bookings' ? 'status AS status' : ($table === 'archive_bookings' ? "'completed' AS status" : "'cancelled' AS status");
        $q = $con->prepare("SELECT `$table`.*, $status FROM `$table` WHERE booking_id = ? AND user_id = ?");
        $q->bind_param('ii', $id, $userId);
        $q->execute();
        $booking = $q->get_result()->fetch_assoc();
        if ($booking) break;
    }
}
if (!$booking) http_response_code(404);
$editable = $booking && staySmartCanChangeBooking($booking);
$room = null;
if ($booking) {
    $q = $con->prepare('SELECT image, beds, capacity FROM rooms WHERE id = ?');
    $q->bind_param('i', $booking['room_id']);
    $q->execute();
    $room = $q->get_result()->fetch_assoc();
}
$earliestCheckIn = (new DateTimeImmutable('now', new DateTimeZone('Asia/Dubai')))->modify('+72 hours');
if ($earliestCheckIn->format('H:i:s') !== '00:00:00') $earliestCheckIn = $earliestCheckIn->modify('+1 day');
$nights = $booking ? (new DateTimeImmutable($booking['check_in']))->diff(new DateTimeImmutable($booking['check_out']))->days : 0;
$price = $booking ? (float) ($booking['price'] ?? 0) : 0;
if ($booking && $nights > 0 && $price == 0 && (float) $booking['total_price'] > 0) $price = (float) $booking['total_price'] / $nights;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Booking details | StaySmart</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css"></head><body class="stay-page">
<?php include 'PHP/header.php'; ?><main class="stay-main"><nav class="stay-breadcrumb"><a href="mybooking.php">&larr; My bookings</a><span>/</span><span>Booking details</span></nav>
<?php if (!$booking): ?><section class="stay-empty"><h1>Booking not found</h1><p>This booking is unavailable or doesn’t belong to your account.</p><a class="stay-primary" href="mybooking.php">Back to my bookings</a></section><?php else: ?>
<header class="stay-heading"><p class="stay-eyebrow">YOUR RESERVATION<?php echo !empty($booking['is_sample']) ? ' (SAMPLE)' : ''; ?> &middot; #<?php echo (int) $id; ?></p><h1><?php echo $escape($booking['room_name']); ?></h1><p><?php echo $escape(ucwords(str_replace('_', ' ', $booking['status']))); ?> · <?php echo $nights; ?> nights · <?php echo isset($booking['guests']) ? (int) $booking['guests'] . ' guests' : 'Guest count not recorded'; ?></p></header>
<div class="stay-layout"><div>
<section class="booking-panel"><h2>Your booking details</h2><dl class="booking-details-grid"><div><dt>Check-in</dt><dd><?php echo $escape($booking['check_in']); ?></dd></div><div><dt>Check-out</dt><dd><?php echo $escape($booking['check_out']); ?></dd></div><div><dt>Room total</dt><dd>$<?php echo number_format((float) $booking['total_price'], 2); ?> USD</dd></div><div><dt>Booked at</dt><dd><?php echo $escape(staySmartAccountTime($booking['booked_at'] ?? null)); ?><small>Dubai time</small></dd></div><div><dt>Phone</dt><dd><?php echo $escape($booking['phone_number'] ?? 'Not recorded'); ?></dd></div><div><dt>Guests</dt><dd><?php echo isset($booking['guests']) ? (int) $booking['guests'] : 'Not recorded'; ?></dd></div></dl><?php if (!empty($booking['message'])): ?><h3>Your requests</h3><p class="booking-details-notes"><?php echo nl2br($escape($booking['message'])); ?></p><?php endif; ?></section>
<section class="booking-panel"><div class="booking-section-heading"><span class="booking-section-number"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span><div><h2>Change your plans</h2><p>Edits and cancellations close 72 hours before check-in, in Dubai time.</p></div></div>
<?php if ($editable): ?>
<form id="edit-booking-dates" action="PHP/booking-action.php" method="post" data-rate="<?php echo $price; ?>" data-confirm="Save these new dates? Your room total will be updated using your original nightly rate."><input type="hidden" name="action" value="edit"><input type="hidden" name="booking_id" value="<?php echo (int) $id; ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['cancel_booking_csrf']); ?>"><div class="booking-fields"><div><label for="edit-check-in">New check-in</label><input type="date" id="edit-check-in" name="check_in" value="<?php echo $escape($booking['check_in']); ?>" min="<?php echo $earliestCheckIn->format('Y-m-d'); ?>" required></div><div><label for="edit-check-out">New check-out</label><input type="date" id="edit-check-out" name="check_out" value="<?php echo $escape($booking['check_out']); ?>" required></div></div><div class="booking-submit-total"><span>Updated room total · USD</span><strong id="edit-booking-total">$<?php echo number_format((float) $booking['total_price'], 2); ?></strong></div><button class="stay-primary" type="submit">Save new dates</button></form>
<form class="booking-cancel-form" action="PHP/booking-action.php" method="post" data-confirm="Cancel this booking? Your room will be released, and the cancelled booking will remain in your history."><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?php echo (int) $id; ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['cancel_booking_csrf']); ?>"><button type="submit">Cancel booking</button></form>
<?php else: ?><p class="booking-details-notes"><?php echo in_array($booking['status'], ['completed', 'cancelled'], true) ? 'This booking is finished and can no longer be changed.' : 'Online changes are now closed for this booking.'; ?> <a href="contact.php">Contact our team</a> if you need help.</p><?php endif; ?>
</section></div><aside class="stay-reservation-card"><p class="stay-eyebrow">YOUR ROOM</p><?php $photo = $room ? staySmartRoomImage($room['image']) : ''; if ($room && $room['image'] !== '' && is_file(__DIR__ . '/Assets/our rooms/' . $photo)): ?><img class="booking-detail-photo" src="Assets/our%20rooms/<?php echo rawurlencode($photo); ?>" alt="<?php echo $escape($booking['room_name']); ?>"><?php endif; ?><h2><?php echo $escape($booking['room_name']); ?></h2><?php if ($room): ?><p class="stay-muted"><?php echo $escape($room['beds']); ?></p><?php endif; ?><a class="booking-room-back" href="room-details.php?id=<?php echo (int) $booking['room_id']; ?>">View room information</a><a class="stay-secondary" href="payments.php?booking=<?php echo (int)$booking['booking_id']; ?>">View payment details</a><div class="stay-card-help"><i class="fa-regular fa-comments" aria-hidden="true"></i><a href="contact.php">Need a hand with your stay?</a></div></aside></div>
<?php endif; ?></main><script src="JS/booking-manage.js" defer></script><?php include 'PHP/footer.php'; ?>
