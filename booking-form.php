<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/public-room.php';
require_once __DIR__ . '/PHP/booking-identity.php';
$bookingUserId = staySmartBookingUserId($con);
if ($room && $canBook && $bookingUserId === null) {
    $_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Please sign in first', 'message' => 'You can explore room details without an account. Sign in to book this room.'];
    header('Location: login.php?next=' . rawurlencode('booking-form.php?id=' . (int) $room['id']));
    exit;
}
$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Dubai'));
if (empty($_SESSION['booking_csrf'])) $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
$message = $_SESSION['booking_message'] ?? '';
unset($_SESSION['booking_message']);
$old = $room && (int) ($_SESSION['booking_old']['room_id'] ?? 0) === (int) $room['id'] ? $_SESSION['booking_old'] : [];
$oldValue = static fn($key, $default = '') => $escape($old[$key] ?? $default);
$guest = null;
if ($bookingUserId !== null) $guest = $signedInAccount;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Plan your stay | StaySmart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css"><link rel="stylesheet" href="CSS/checkout.css">
</head><body class="stay-page booking-page">
<?php include 'PHP/header.php'; ?>
<main class="stay-main">
<nav class="stay-breadcrumb" aria-label="Breadcrumb"><a href="rooms.php">Rooms</a><span aria-hidden="true">/</span><?php if ($room): ?><a href="room-details.php?id=<?php echo (int) $room['id']; ?>">Room details</a><span aria-hidden="true">/</span><?php endif; ?><span>Book your stay</span></nav>
<header class="stay-heading"><p class="stay-eyebrow">A FEW DETAILS, THEN YOU’RE ON YOUR WAY</p><h1>Make room for your next escape.</h1><p>Choose your dates, add your contact details, and continue to checkout.</p></header>
<?php if ($message): ?><p class="stay-notice" role="alert"><?php echo $escape($message); ?></p><?php endif; ?>
<?php if (!$room || !$canBook): ?>
<section class="stay-empty"><h2>This room isn’t accepting bookings right now.</h2><p>Browse our other spaces to find a room for your stay.</p><a class="stay-primary" href="rooms.php">Explore rooms</a></section>
<?php else: ?>
<div class="stay-layout booking-layout">
    <div>
        <?php if (!$guest): ?>
        <section class="booking-panel booking-signin"><span class="booking-section-number"><i class="fa-regular fa-user" aria-hidden="true"></i></span><h2>Sign in to reserve your room</h2><p>Your booking will be saved to your account so you can review and manage your stay.</p><a class="stay-primary" href="login.php?next=booking-form.php%3Fid%3D<?php echo (int) $room['id']; ?>">Sign in &amp; continue</a><a class="stay-secondary" href="register.php">Create an account</a></section>
        <?php else: ?>
        <form id="stay-booking-form" action="checkout.php" method="post" data-rate="<?php echo $escape($room['price']); ?>">
            <input type="hidden" name="room_id" value="<?php echo (int) $room['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['booking_csrf']); ?>">
            <section class="booking-panel"><div class="booking-section-heading"><span class="booking-section-number">01</span><div><h2>Your stay</h2><p>A little time away, just the way you like it.</p></div></div>
                <div class="booking-fields">
                    <div><label for="check-in">Check-in</label><input type="date" id="check-in" name="check_in" min="<?php echo $today->format('Y-m-d'); ?>" value="<?php echo $oldValue('check_in'); ?>" aria-describedby="check-in-help" required><small id="check-in-help">Today or a future date, in Dubai time.</small></div>
                    <div><label for="check-out">Check-out</label><input type="date" id="check-out" name="check_out" min="<?php echo $today->modify('+1 day')->format('Y-m-d'); ?>" value="<?php echo $oldValue('check_out'); ?>" required></div>
                    <div class="booking-field-wide"><label for="stay-guests">Guests</label><select id="stay-guests" name="guests" required><?php for ($count = 1; $count <= (int) $room['capacity']; $count++): ?><option value="<?php echo $count; ?>" <?php echo (int) ($old['guests'] ?? 1) === $count ? 'selected' : ''; ?>><?php echo $count; ?> <?php echo $count === 1 ? 'guest' : 'guests'; ?></option><?php endfor; ?></select><small>This room accommodates up to <?php echo (int) $room['capacity']; ?> guests.</small></div>
                </div>
            </section>
            <section class="booking-panel"><div class="booking-section-heading"><span class="booking-section-number">02</span><div><h2>Your contact details</h2><p>So we can get in touch about your reservation.</p></div></div>
                <div class="booking-account"><strong><?php echo $escape($guest['full_name'] ?: $guest['username']); ?></strong><span><?php echo $escape($guest['email']); ?></span><a href="<?php echo isset($_SESSION['admin_id']) ? 'admin/admin.php' : 'profile.php'; ?>">Update profile</a></div>
                <div class="booking-fields"><div class="booking-field-wide"><label for="stay-phone">Phone number</label><input type="tel" name="phone_number" id="stay-phone" value="<?php echo $oldValue('phone_number'); ?>" autocomplete="tel" maxlength="30" placeholder="Include your country code" required></div><div class="booking-field-wide"><label for="stay-notes">Anything we should know? <span>(optional)</span></label><textarea id="stay-notes" name="mbox" rows="4" maxlength="2000" placeholder="Arrival plans, special requests, or a little extra information."><?php echo $oldValue('mbox'); ?></textarea><small>Special requests are subject to availability.</small></div></div>
            </section>
            <section class="booking-panel booking-review"><div class="booking-section-heading"><span class="booking-section-number">03</span><div><h2>Next, review your checkout</h2><p>Review your dates and total, then choose how to complete your reservation.</p></div></div><div class="booking-submit-total"><span>Room total · USD</span><strong id="booking-submit-total">Choose your dates</strong></div><p class="booking-payment-note">Pay at the property. You can review all payment details before confirming your booking.</p><button class="stay-primary" id="submit-booking" name="book" value="1" type="submit">Continue to checkout <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button><p id="booking-submit-status" role="status" aria-live="polite"></p></section>
        </form>
        <?php endif; ?>
    </div>
    <aside class="stay-reservation-card booking-summary" aria-labelledby="stay-summary-title">
        <?php if ($hasPhoto): ?><img class="booking-summary-photo" src="Assets/our%20rooms/<?php echo rawurlencode($roomPhoto); ?>" alt="<?php echo $escape($room['room_name']); ?>" width="600" height="360"><?php endif; ?>
        <p class="stay-eyebrow">YOUR STAY AT A GLANCE</p><h2 id="stay-summary-title"><?php echo $escape($room['room_name']); ?></h2><p class="stay-muted"><?php echo $escape($room['beds']); ?> · <?php echo $escape($room['size']); ?></p><a class="booking-room-back" href="room-details.php?id=<?php echo (int) $room['id']; ?>">View room details</a>
        <dl class="booking-price-lines"><div><dt>Nightly rate</dt><dd>$<?php echo number_format((float) $room['price'], 2); ?> <small>USD</small></dd></div><div><dt>Length of stay</dt><dd id="stay-nights">Choose dates</dd></div><div><dt>Guests</dt><dd id="stay-summary-guests"><?php echo (int) ($old['guests'] ?? 1); ?></dd></div><div class="booking-grand-total"><dt>Room total</dt><dd id="stay-total">—</dd></div></dl><p class="stay-muted" id="stay-total-help">Select check-in and check-out to calculate your room total.</p><div class="stay-card-help"><i class="fa-regular fa-comments" aria-hidden="true"></i><span>Need a hand?<br><a href="contact.php">Contact our team</a></span></div>
    </aside>
</div>
<?php endif; ?>
</main><script src="JS/booking.js" defer></script>
<?php include 'PHP/footer.php'; ?>
