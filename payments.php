<?php
session_start();
require_once __DIR__.'/PHP/auth.php';
require_once __DIR__.'/PHP/booking-identity.php';
require_once __DIR__.'/PHP/booking-management.php';
staySmartEnsureBookingChanges($con);
$userId = staySmartBookingUserId($con);
if ($userId === null) { $_SESSION['flash_alert']=['type'=>'error','title'=>'Please sign in first','message'=>'Sign in to view your payment details.']; header('Location: login.php'); exit; }
$escape = static fn($v) => htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$bookingId = filter_var($_GET['booking'] ?? null,FILTER_VALIDATE_INT);
$q=$con->prepare("SELECT b.*,p.method,p.status AS payment_status,p.amount,p.currency FROM (
    SELECT booking_id,user_id,room_name,check_in,check_out,total_price,is_sample,'active' AS booking_state FROM bookings WHERE user_id=?
    UNION ALL SELECT booking_id,user_id,room_name,check_in,check_out,total_price,is_sample,'completed' FROM archive_bookings WHERE user_id=?
    UNION ALL SELECT booking_id,user_id,room_name,check_in,check_out,total_price,is_sample,'cancelled' FROM cancelled_bookings WHERE user_id=?
    ) b LEFT JOIN booking_payments p ON p.booking_id=b.booking_id AND p.user_id=b.user_id ORDER BY b.booking_id DESC");
$q->bind_param('iii',$userId,$userId,$userId);$q->execute();$payments=$q->get_result()->fetch_all(MYSQLI_ASSOC);
if ($bookingId && $bookingId>0) $payments=array_values(array_filter($payments,static fn($p)=>(int)$p['booking_id']===$bookingId));
if ($bookingId && !$payments) http_response_code(404);
$due=0;foreach($payments as $p)if(empty($p['is_sample']) && $p['payment_status']==='unpaid' && $p['booking_state']!=='cancelled')$due+=(float)$p['amount'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payment details | StaySmart</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css"><link rel="stylesheet" href="CSS/checkout.css"></head><body class="stay-page">
<?php include 'PHP/header.php'; ?><main class="stay-main"><nav class="stay-breadcrumb"><a href="mybooking.php">&larr; My bookings</a><span>/</span><span>Payments</span></nav><header class="stay-heading"><p class="stay-eyebrow">YOUR RESERVATIONS, CLEARLY ORGANIZED</p><h1>Payment details</h1><p>See your room totals and what is due at the property.</p></header>
<div class="payment-overview"><span class="payment-overview-icon"><i class="fa-solid fa-hotel" aria-hidden="true"></i></span><div><span>Recorded amount due at the property</span><strong>$<?php echo number_format($due,2); ?> <small>USD</small></strong></div><p>Pay directly at the property.<br>No online payment is collected.</p></div>
<div class="payment-list"><?php foreach($payments as $p): ?><article class="booking-panel payment-card"><div class="checkout-panel-heading"><div><p class="stay-eyebrow">BOOKING #<?php echo (int)$p['booking_id']; ?><?php echo $p['is_sample'] ? ' (SAMPLE)' : ''; ?></p><h2><?php echo $escape($p['room_name']); ?></h2></div><span class="payment-status <?php echo $p['booking_state']==='cancelled'?'is-cancelled':''; ?>"><?php echo $p['booking_state']==='cancelled'?'Cancelled':($p['payment_status']==='paid'?'Paid':($p['payment_status']==='unpaid'?'Pay at property':'Status not recorded')); ?></span></div><p class="stay-muted"><?php echo $escape($p['check_in']); ?> &rarr; <?php echo $escape($p['check_out']); ?></p><div class="booking-submit-total"><span>Room total</span><strong>$<?php echo number_format((float)$p['total_price'],2); ?> USD</strong></div><p class="booking-details-notes"><?php echo $p['booking_state']==='cancelled'?'This reservation was cancelled. No online payment was collected.':($p['payment_status']==='unpaid'?'Payment is due at the property. No payment has been taken online.':($p['payment_status']==='paid'?'Payment has been recorded for this reservation.':'No payment status was recorded for this older reservation. Contact the property to check its balance.')); ?></p><a class="payment-detail-link" href="booking-details.php?id=<?php echo (int)$p['booking_id']; ?>">View booking details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></article><?php endforeach; ?><?php if(!$payments): ?><section class="stay-empty"><h2>No payment details to show</h2><p>Your own reservations will appear here after booking.</p><a class="stay-primary" href="rooms.php">Explore rooms</a></section><?php endif; ?></div></main><?php include 'PHP/footer.php'; ?>
