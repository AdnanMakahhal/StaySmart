<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/booking-identity.php';
require_once __DIR__ . '/PHP/checkout.php';
require_once __DIR__ . '/PHP/room-image.php';
$bookingUserId = staySmartBookingUserId($con);
if ($bookingUserId === null) {
    $_SESSION['flash_alert'] = ['type'=>'error','title'=>'Please sign in first','message'=>'Sign in to review and confirm your reservation.'];
    header('Location: login.php'); exit;
}
$escape = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || empty($_SESSION['booking_csrf']) || !hash_equals($_SESSION['booking_csrf'], $token)) throw new RuntimeException('Your session expired. Refresh your booking details and try again.');
        $draft = staySmartCheckoutDraft($con, $_POST);
        $_SESSION['booking_old'] = $draft;
        $_SESSION['checkout_draft'] = $draft + ['user_id'=>$bookingUserId, 'created_at'=>time(), 'token'=>bin2hex(random_bytes(32))];
        header('Location: checkout.php', true, 303); exit;
    } catch (mysqli_sql_exception $error) {
        error_log('Checkout preparation failed: '.$error->getMessage());
        $errorMessage = 'Checkout is temporarily unavailable. Please try again.';
    } catch (RuntimeException $error) { $errorMessage = $error->getMessage(); }
    $_SESSION['flash_alert'] = ['type'=>'error','title'=>'Check your stay details','message'=>$errorMessage];
    $id = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
    header('Location: '.($id && $id > 0 ? 'booking-form.php?id='.$id : 'rooms.php')); exit;
}
$draft = $_SESSION['checkout_draft'] ?? null;
if (!$draft || (int) $draft['user_id'] !== $bookingUserId || time() - $draft['created_at'] > 1800) {
    unset($_SESSION['checkout_draft']);
    $_SESSION['flash_alert'] = ['type'=>'error','title'=>'Choose your stay first','message'=>'Enter your dates and contact details to continue to checkout.'];
    header('Location: rooms.php'); exit;
}
try {
    $current = staySmartCheckoutDraft($con, $draft);
    if ($current['price'] !== $draft['price']) {
        $draft = $current + ['user_id'=>$bookingUserId,'created_at'=>time(),'token'=>bin2hex(random_bytes(32))];
        $_SESSION['checkout_draft'] = $draft;
        $_SESSION['flash_alert'] = ['type'=>'error','title'=>'Room rate updated','message'=>'The nightly rate has changed. Please review the new total before confirming.'];
    }
} catch (RuntimeException $error) {
    $_SESSION['flash_alert'] = ['type'=>'error','title'=>'Please review your room','message'=>'This stay is no longer available. Choose another room or update your dates.'];
    unset($_SESSION['checkout_draft']); header('Location: rooms.php'); exit;
}
$total = round($draft['price'] * $draft['nights'], 2);
$photo = staySmartRoomImage($draft['image']);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Checkout &amp; payment | StaySmart</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css"><link rel="stylesheet" href="CSS/checkout.css"></head><body class="stay-page">
<?php include 'PHP/header.php'; ?><main class="stay-main checkout-main">
<nav class="stay-breadcrumb"><a href="booking-form.php?id=<?php echo (int) $draft['room_id']; ?>">&larr; Edit stay details</a><span>/</span><span>Checkout</span></nav>
<ol class="checkout-steps" aria-label="Booking progress"><li class="is-complete"><span>&#10003;</span> Your stay</li><li aria-current="step"><span>2</span> Review &amp; payment</li><li><span>3</span> Confirmation</li></ol>
<header class="stay-heading"><p class="stay-eyebrow">ONE LAST LOOK BEFORE YOUR GETAWAY</p><h1>Everything look good?</h1><p>Review your stay and payment details, then make it official.</p></header>
<div class="stay-layout"><div>
<section class="booking-panel checkout-stay"><div class="checkout-panel-heading"><h2>Your reservation</h2><a href="booking-form.php?id=<?php echo (int) $draft['room_id']; ?>">Edit details</a></div><h3><?php echo $escape($draft['room_name']); ?></h3><dl class="booking-details-grid"><div><dt>Check-in</dt><dd><?php echo (new DateTimeImmutable($draft['check_in']))->format('D, d M Y'); ?></dd></div><div><dt>Check-out</dt><dd><?php echo (new DateTimeImmutable($draft['check_out']))->format('D, d M Y'); ?></dd></div><div><dt>Length of stay</dt><dd><?php echo (int) $draft['nights']; ?> nights</dd></div><div><dt>Guests</dt><dd><?php echo (int) $draft['guests']; ?></dd></div></dl><div class="checkout-guest"><strong><?php echo $escape($signedInAccount['full_name'] ?: $signedInAccount['username']); ?></strong><span><?php echo $escape($signedInAccount['email']); ?></span><span><?php echo $escape($draft['phone_number']); ?></span></div><?php if ($draft['mbox']): ?><p class="booking-details-notes"><?php echo nl2br($escape($draft['mbox'])); ?></p><?php endif; ?></section>
<section class="booking-panel"><h2>Payment</h2><div class="checkout-payment-option"><span class="checkout-payment-check" aria-hidden="true">&#10003;</span><div><strong>Pay at the property</strong><p>Settle your room total directly with the property. No online payment or card details are required.</p></div><i class="fa-solid fa-hotel" aria-hidden="true"></i></div><div class="checkout-payment-lines"><span>Pay now</span><strong>$0.00</strong><span>Due at the property</span><strong>$<?php echo number_format($total,2); ?> USD</strong></div></section>
<section class="booking-panel"><h2>A little flexibility, before your stay</h2><p class="booking-details-notes">You can edit dates or cancel online until 72 hours before check-in, in Dubai time. After that, contact our team for help.</p><p class="booking-details-notes">Your room is reserved when you confirm below. Special requests are subject to availability.</p></section>
</div><aside class="stay-reservation-card checkout-total">
<?php if ($draft['image'] !== '' && is_file(__DIR__.'/Assets/our rooms/'.$photo)): ?><img class="booking-summary-photo" src="Assets/our%20rooms/<?php echo rawurlencode($photo); ?>" alt="<?php echo $escape($draft['room_name']); ?>"><?php endif; ?>
<p class="stay-eyebrow">YOUR STAY TOTAL</p><h2><?php echo $escape($draft['room_name']); ?></h2><dl class="booking-price-lines"><div><dt>$<?php echo number_format($draft['price'],2); ?> × <?php echo (int)$draft['nights']; ?> nights</dt><dd>$<?php echo number_format($total,2); ?></dd></div><div class="booking-grand-total"><dt>Room total</dt><dd>$<?php echo number_format($total,2); ?></dd></div><div><dt>Due now</dt><dd>$0.00</dd></div></dl><p class="stay-muted">USD · Pay at the property. No online charge.</p>
<form id="confirm-booking-form" action="book.php" method="post"><input type="hidden" name="room_id" value="<?php echo (int)$draft['room_id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['booking_csrf']); ?>"><input type="hidden" name="checkout_token" value="<?php echo $escape($draft['token']); ?>"><button type="submit" name="book" value="1" class="stay-primary">Confirm booking <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button><p id="confirm-booking-status" role="status" class="stay-muted"></p></form><a class="stay-secondary" href="booking-form.php?id=<?php echo (int)$draft['room_id']; ?>">Back to your stay</a>
</aside></div></main><script src="JS/checkout.js" defer></script><?php include 'PHP/footer.php'; ?>
