<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/booking-identity.php';
$bookingUserId = staySmartBookingUserId($con);
require_once __DIR__ . '/PHP/booking-management.php';
staySmartEnsureBookingChanges($con);
if (empty($_SESSION['cancel_booking_csrf'])) $_SESSION['cancel_booking_csrf'] = bin2hex(random_bytes(32));

if ($bookingUserId === null) {
    header("Location: login.php");
    exit;
}

include './PHP/connect.php';
require_once './PHP/room-ratings.php';
staySmartEnsureRoomRatingsTable($con);
$userId = $bookingUserId;
$bookingQuery = $con->prepare("SELECT booking_id, room_id, room_name, check_in, check_out, guests, total_price, status AS status, is_sample FROM bookings WHERE user_id = ? ORDER BY booking_id DESC");
$bookingQuery->bind_param("i", $userId);
$bookingQuery->execute();
$bookings = $bookingQuery->get_result();
$completedQuery = $con->prepare(
    'SELECT archive_bookings.booking_id, archive_bookings.room_id, archive_bookings.room_name,
        archive_bookings.check_in, archive_bookings.check_out, archive_bookings.total_price, archive_bookings.is_sample,
        room_reviews.rating AS user_rating, room_reviews.review_text AS user_review
    FROM archive_bookings
    LEFT JOIN room_reviews ON room_reviews.booking_id = archive_bookings.booking_id
        AND room_reviews.user_id = archive_bookings.user_id
    WHERE archive_bookings.user_id = ?
    ORDER BY archive_bookings.booking_id DESC'
);
$completedQuery->bind_param('i', $userId);
$completedQuery->execute();
$completedBookings = $completedQuery->get_result();
$cancelledQuery = $con->prepare('SELECT booking_id, room_name, cancelled_at FROM cancelled_bookings WHERE user_id = ? ORDER BY cancelled_at DESC');
$cancelledQuery->bind_param('i', $userId);
$cancelledQuery->execute();
$cancelledBookings = $cancelledQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$todayDubai=(new DateTimeImmutable('today',new DateTimeZone('Asia/Dubai')))->format('Y-m-d');
$upcomingQuery=$con->prepare("SELECT booking_id,room_name,check_in,check_out,guests,is_sample FROM bookings WHERE user_id=? AND status='booked' AND check_in>=? ORDER BY check_in,booking_id LIMIT 3");
$upcomingQuery->bind_param('is',$userId,$todayDubai);$upcomingQuery->execute();$upcomingBookings=$upcomingQuery->get_result()->fetch_all(MYSQLI_ASSOC);
if (empty($_SESSION['room_rating_csrf'])) {
    $_SESSION['room_rating_csrf'] = bin2hex(random_bytes(32));
}
$ratingMessage = $_SESSION['room_rating_message'] ?? '';
$bookingMessage = $_SESSION['booking_message'] ?? '';
unset($_SESSION['booking_message']);
$ratingMessageType = $_SESSION['room_rating_message_type'] ?? 'success';
unset($_SESSION['room_rating_message'], $_SESSION['room_rating_message_type']);

function bookingDate($value)
{
    $timestamp = strtotime($value);
    return $timestamp ? date('M j, Y', $timestamp) : $value;
}

function bookingStatusClass($status)
{
    if ($status === 'booked') {
        return 'is-booked';
    }
    if ($status === 'checked_in') {
        return 'is-checked-in';
    }
    if ($status === 'checked_out') {
        return 'is-checked-out';
    }
    return 'is-other';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './PHP/head.php'; ?>
    <link rel="stylesheet" href="./CSS/mybooking.css">
</head>

<body class="my-bookings-page">
    <?php include './PHP/header.php'; ?>

    <main class="mybooking-main">
        <section class="mybooking-shell" aria-labelledby="bookings-title">
            <header class="mybooking-heading">
                <div>
                    <p class="mybooking-eyebrow">Your trips</p>
                    <h1 id="bookings-title">My bookings</h1>
                    <p>Review your reservations and keep your stay details close at hand.</p>
                </div>
                <div class="mybooking-heading-actions"><a class="find-room-link" href="payments.php"><i class="fa-solid fa-wallet" aria-hidden="true"></i> Payments</a><a class="find-room-link" href="./rooms.php"><i class="fa-solid fa-plus" aria-hidden="true"></i> Find a room</a></div>
            </header>

            <?php if ($bookingMessage): ?><p class="profile-notice" role="status"><?php echo htmlspecialchars($bookingMessage, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            <?php if($upcomingBookings): ?><section class="upcoming-stays" aria-labelledby="upcoming-stays-title"><div class="upcoming-stays-heading"><div><p>Your next getaway</p><h2 id="upcoming-stays-title">Coming up</h2></div><a href="reviews.php">Your reviews &rarr;</a></div><div class="upcoming-stay-grid"><?php foreach($upcomingBookings as $nextStay): ?><a class="upcoming-stay-card" href="booking-details.php?id=<?php echo (int)$nextStay['booking_id']; ?>"><span class="upcoming-stay-date"><?php echo (new DateTimeImmutable($nextStay['check_in']))->format('d M'); ?></span><div><strong><?php echo htmlspecialchars($nextStay['room_name'],ENT_QUOTES,'UTF-8'); ?></strong><span><?php echo htmlspecialchars($nextStay['check_in'].' to '.$nextStay['check_out'],ENT_QUOTES,'UTF-8'); ?></span><small><?php echo (int)$nextStay['guests']; ?> guests<?php echo $nextStay['is_sample']?' · Sample booking':''; ?></small></div><span aria-hidden="true">&rarr;</span></a><?php endforeach; ?></div></section><?php endif; ?>
            <div class="mybooking-card">
                <?php if ($bookings && $bookings->num_rows > 0): ?>
                    <div class="booking-table-wrap">
                        <table class="booking-table">
                            <thead>
                                <tr>
                                    <th scope="col">Booking</th>
                                    <th scope="col">Room</th>
                                    <th scope="col">Stay dates</th>
                                    <th scope="col">Guests</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <?php
                                    $status = (string) $booking['status'];
                                    $safeStatus = htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td data-label="Booking"><span class="booking-id">#<?php echo htmlspecialchars((string) $booking['booking_id'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td data-label="Room"><strong class="booking-room"><?php echo htmlspecialchars($booking['room_name'], ENT_QUOTES, 'UTF-8'); ?></strong><?php if($booking['is_sample']): ?><small class="sample-booking-label">Sample booking</small><?php endif; ?></td>
                                        <td data-label="Stay dates">
                                            <span class="booking-dates">
                                                <span><?php echo htmlspecialchars(bookingDate($booking['check_in']), ENT_QUOTES, 'UTF-8'); ?></span>
                                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                <span><?php echo htmlspecialchars(bookingDate($booking['check_out']), ENT_QUOTES, 'UTF-8'); ?></span>
                                            </span>
                                        </td>
                                        <td data-label="Guests"><?php echo htmlspecialchars((string) $booking['guests'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td data-label="Total"><strong class="booking-price">$<?php echo htmlspecialchars((string) $booking['total_price'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td data-label="Status"><span class="booking-status <?php echo bookingStatusClass($status); ?>"><?php echo $safeStatus; ?></span></td>
                                        <td data-label="Action" class="booking-action">
                                            <a class="booking-details-link" href="booking-details.php?id=<?php echo (int) $booking['booking_id']; ?>">View details<?php echo staySmartCanChangeBooking($booking) ? ' / Edit' : ''; ?></a>
                                            <?php if (staySmartCanChangeBooking($booking)): ?>
                                                <form action="PHP/cancel.php" method="POST" data-confirm="Cancel this booking? The booking will stay in your history.">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['cancel_booking_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) $booking['booking_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars((string) $booking['room_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button class="cancel-booking-btn" name="cancel_booking" type="submit">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="booking-empty">
                        <span class="booking-empty-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                        <h2>No bookings yet</h2>
                        <p>Your reservations will appear here when you book a room.</p>
                        <a class="find-room-link" href="./rooms.php">Explore rooms <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($ratingMessage !== ''): ?>
                <p class="booking-rating-notice <?php echo $ratingMessageType === 'success' ? 'is-success' : 'is-error'; ?>" role="<?php echo $ratingMessageType === 'success' ? 'status' : 'alert'; ?>">
                    <i class="fa-solid <?php echo $ratingMessageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($ratingMessage, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <section class="completed-stays-section" aria-labelledby="completed-stays-title">
                <?php if ($cancelledBookings): ?><div class="cancelled-booking-list"><h2>Cancelled bookings</h2><?php foreach ($cancelledBookings as $cancelled): ?><a href="booking-details.php?id=<?php echo (int) $cancelled['booking_id']; ?>"><strong><?php echo htmlspecialchars($cancelled['room_name'], ENT_QUOTES, 'UTF-8'); ?></strong><span>#<?php echo (int) $cancelled['booking_id']; ?> · Cancelled <?php echo htmlspecialchars(staySmartAccountTime($cancelled['cancelled_at']), ENT_QUOTES, 'UTF-8'); ?></span><span>View details &rarr;</span></a><?php endforeach; ?></div><?php endif; ?>
                <header class="completed-stays-heading">
                    <div>
                        <p class="mybooking-eyebrow">Your finished trips</p>
                        <h2 id="completed-stays-title">Completed stays</h2>
                    </div>
                    <p>Share a room rating after your stay is complete.</p>
                </header>
                <div class="mybooking-card">
                    <?php if ($completedBookings->num_rows > 0): ?>
                        <div class="booking-table-wrap">
                            <table class="booking-table completed-booking-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Booking</th>
                                        <th scope="col">Room</th>
                                        <th scope="col">Stay dates</th>
                                        <th scope="col">Your rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($completedBooking = $completedBookings->fetch_assoc()): ?>
                                        <?php
                                        $isRateable = $completedBooking['check_out'] <= (new DateTimeImmutable('today', new DateTimeZone('Asia/Dubai')))->format('Y-m-d');
                                        $savedRating = $completedBooking['user_rating'] !== null ? (int) $completedBooking['user_rating'] : null;
                                        ?>
                                        <tr>
                                            <td data-label="Booking"><a class="booking-id" href="booking-details.php?id=<?php echo (int) $completedBooking['booking_id']; ?>">#<?php echo htmlspecialchars((string) $completedBooking['booking_id'], ENT_QUOTES, 'UTF-8'); ?> · View</a></td>
                                            <td data-label="Room"><strong class="booking-room"><?php echo htmlspecialchars($completedBooking['room_name'], ENT_QUOTES, 'UTF-8'); ?></strong><?php if($completedBooking['is_sample']): ?><small class="sample-booking-label">Sample booking</small><?php endif; ?></td>
                                            <td data-label="Stay dates">
                                                <span class="booking-dates">
                                                    <span><?php echo htmlspecialchars(bookingDate($completedBooking['check_in']), ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                    <span><?php echo htmlspecialchars(bookingDate($completedBooking['check_out']), ENT_QUOTES, 'UTF-8'); ?></span>
                                                </span>
                                            </td>
                                            <td data-label="Your rating">
                                                <?php if ($savedRating !== null): ?>
                                                    <span class="booking-saved-rating" aria-label="You rated this room <?php echo $savedRating; ?> out of 5">
                                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                        <?php echo $savedRating; ?>/5 <small>Thanks for sharing</small>
                                                    </span>
                                                    <?php if ($completedBooking['user_review']): ?><p class="booking-review-copy"><?php echo htmlspecialchars($completedBooking['user_review'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                                                <?php elseif ($isRateable): ?>
                                                    <form class="booking-rating-form" action="PHP/rate-room.php" method="post">
                                                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) $completedBooking['booking_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['room_rating_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <label class="visually-hidden" for="room-rating-<?php echo (int) $completedBooking['booking_id']; ?>">Rate the room from 1 to 5 stars</label>
                                                        <select id="room-rating-<?php echo (int) $completedBooking['booking_id']; ?>" name="rating" required>
                                                            <option value="">Rate room</option>
                                                            <?php for ($stars = 5; $stars >= 1; $stars--): ?>
                                                                <option value="<?php echo $stars; ?>"><?php echo $stars; ?> star<?php echo $stars === 1 ? '' : 's'; ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                        <label class="review-text-label" for="review-text-<?php echo (int) $completedBooking['booking_id']; ?>">Your review (optional)</label>
                                                        <textarea id="review-text-<?php echo (int) $completedBooking['booking_id']; ?>" name="review_text" rows="3" maxlength="1000" placeholder="Tell us about the room or hotel"></textarea>
                                                        <small>Your name, profile photo and room review may appear on our homepage.</small>
                                                        <button type="submit"><i class="fa-solid fa-star" aria-hidden="true"></i> Share rating</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="booking-rating-pending">Available after checkout</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="booking-empty completed-stays-empty">
                            <span class="booking-empty-icon"><i class="fa-regular fa-star" aria-hidden="true"></i></span>
                            <h3>No completed stays yet</h3>
                            <p>After checkout, you can leave a rating for your room.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>

    <?php
    $bookingQuery->close();
    $completedQuery->close();
    include './PHP/footer.php';
    ?>
</body>

</html>
