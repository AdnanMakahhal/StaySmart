<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';

include_once './PHP/connect.php';
include_once './PHP/room-image.php';
include_once './PHP/site-content.php';
include_once './PHP/room-ratings.php';
include_once './PHP/room-management.php';
staySmartEnsureRoomRatingsTable($con);
staySmartEnsureRoomManagementColumns($con);
$latestReviews = staySmartLatestReviews($con);

if (isset($_SESSION['sucessalert'])) {
    unset($_SESSION['sucessalert']);
    $bookingSuccess = true;
} else {
    $bookingSuccess = false;
}

$sql = "SELECT rooms.*,
    (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count
    FROM rooms WHERE is_visible = 1 ORDER BY id DESC LIMIT 3";
$rooms = mysqli_query($con, $sql);
$siteContent = staySmartLoadContent($con);
$escapeContent = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$bookingConfirmation = null;
if (isset($_SESSION['booking_success_id'])) {
    require_once __DIR__.'/PHP/booking-identity.php';
    $owner = staySmartBookingUserId($con);
    $confirmationId = (int)$_SESSION['booking_success_id'];
    unset($_SESSION['booking_success_id']);
    if ($owner !== null) {
        $confirmation = $con->prepare('SELECT b.booking_id,b.room_name,b.check_in,b.check_out,b.guests,b.total_price,p.status AS payment_status FROM bookings b INNER JOIN booking_payments p ON p.booking_id=b.booking_id WHERE b.booking_id=? AND b.user_id=?');
        $confirmation->bind_param('ii',$confirmationId,$owner);$confirmation->execute();$bookingConfirmation=$confirmation->get_result()->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './PHP/head.php'; ?>
</head>

<body class="home-page">
    <?php include './PHP/header.php'; ?>

    <?php if ($bookingSuccess): ?>
        <div class="home-alert" role="status">
            Booking successful! We'll call you shortly to confirm your reservation.
        </div>
    <?php endif; ?>

    <main>
        <section class="section-rooms" aria-labelledby="hero-title">
            <div class="image">
                <div class="image-intro">
                    <picture>
                        <img src="./<?php echo $escapeContent(staySmartContentImage($siteContent['home_hero_image'], 'Assets/mainhome.png')); ?>" alt="StaySmart featured destination" fetchpriority="high">
                    </picture>
                    <div class="image-main">
                        <p class="hero-eyebrow"><?php echo $escapeContent($siteContent['home_hero_eyebrow']); ?></p>
                        <h1 id="hero-title"><?php echo $escapeContent($siteContent['home_hero_title']); ?></h1>
                        <p><?php echo $escapeContent($siteContent['home_hero_description']); ?></p>
                        <a class="hero-link" href="rooms.php"><?php echo $escapeContent($siteContent['home_hero_link']); ?> <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>

                <form class="room-here" action="rooms.php" method="get">
                    <div class="book-room">
                        <label for="destination"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>Where</label>
                        <input type="search" id="destination" name="destination" placeholder="City, area or hotel" autocomplete="off">
                    </div>
                    <div class="book-room">
                        <label for="date"><i class="fa-regular fa-calendar" aria-hidden="true"></i>When</label>
                        <input type="date" id="date" name="date" aria-label="Select arrival date">
                    </div>
                    <button type="submit" class="search-btn" aria-label="Search rooms">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </section>

        <section class="body-services-provided" aria-labelledby="services-title">
            <div class="div-about-services-provided">
                <p class="section-kicker">The StaySmart difference</p>
                <h2 id="services-title">A stay made simple</h2>
                <p>From finding the right room to settling in, we make every part of your stay feel easy.</p>
            </div>

            <div class="div-section-services-provided">
                <article class="service-item">
                    <span class="icon-services"><i class="fa-solid fa-credit-card" aria-hidden="true"></i></span>
                    <h3>Easy booking</h3>
                    <p>Find your room and make a reservation in just a few simple steps.</p>
                </article>
                <article class="service-item">
                    <span class="icon-services"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                    <h3>Rooms for you</h3>
                    <p>Explore comfortable spaces for a quiet weekend or a longer getaway.</p>
                </article>
                <article class="service-item">
                    <span class="icon-services"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
                    <h3>Here to help</h3>
                    <p>Our team is ready to help make your visit a comfortable one.</p>
                </article>
                <article class="service-item">
                    <span class="icon-services"><i class="fa-solid fa-heart" aria-hidden="true"></i></span>
                    <h3>Thoughtful comfort</h3>
                    <p>Enjoy welcoming details and the little things that make a stay special.</p>
                </article>
            </div>
        </section>

        <section class="section-special-offers" aria-labelledby="rooms-title">
            <div class="section-heading">
                <p class="section-kicker">Find your next favorite</p>
                <h2 id="rooms-title">Special offers</h2>
                <p>Take a look at a few of the rooms waiting for you.</p>
            </div>

            <div class="room-grid">
                <?php if ($rooms && mysqli_num_rows($rooms) > 0): ?>
                    <?php while ($room = mysqli_fetch_assoc($rooms)): ?>
                        <?php
                        $roomName = htmlspecialchars($room['room_name'], ENT_QUOTES, 'UTF-8');
                        $roomImage = htmlspecialchars(staySmartRoomImage($room['image']), ENT_QUOTES, 'UTF-8');
                        $roomFacilities = htmlspecialchars($room['facilities'], ENT_QUOTES, 'UTF-8');
                        $roomPrice = htmlspecialchars($room['price'], ENT_QUOTES, 'UTF-8');
                        $roomBeds = htmlspecialchars($room['beds'], ENT_QUOTES, 'UTF-8');
                        $roomCapacity = htmlspecialchars($room['capacity'], ENT_QUOTES, 'UTF-8');
                        $reviewCount = (int) $room['review_count'];
                        $roomRating = $reviewCount > 0 ? number_format((float) $room['rating'], 1) : 'No ratings yet';
                        $roomUrl = 'room-details.php?id=' . (int) $room['id'];
                        ?>
                        <article class="room-card">
                            <a class="room-card-link" href="<?php echo htmlspecialchars($roomUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="room-card-image">
                                    <picture>
                                        <img src="Assets/our rooms/<?php echo $roomImage; ?>" alt="<?php echo $roomName; ?>" loading="lazy" decoding="async">
                                    </picture>
                                    <span class="room-rating"><?php if ($reviewCount > 0): ?><i class="fa-solid fa-star" aria-hidden="true"></i><?php endif; ?><?php echo htmlspecialchars($roomRating, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="room-card-content">
                                    <h3><?php echo $roomName; ?></h3>
                                    <p class="room-facilities"><?php echo $roomFacilities; ?></p>
                                    <p class="room-price">$<?php echo $roomPrice; ?> <span>/ night</span></p>
                                    <div class="room-meta">
                                        <span><i class="fa-solid fa-bed" aria-hidden="true"></i><?php echo $roomBeds; ?></span>
                                        <span><i class="fa-regular fa-user" aria-hidden="true"></i><?php echo $roomCapacity; ?> sleeps</span>
                                    </div>
                                    <p class="room-card-details">View room details <span aria-hidden="true">&rarr;</span></p>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-rooms">New rooms are on their way. Please check back soon.</p>
                <?php endif; ?>
            </div>

            <a class="section-button" href="rooms.php">View all rooms</a>
        </section>

        <section class="section-reviews" aria-labelledby="reviews-title">
            <div class="section-heading">
                <p class="section-kicker">Kind words from our guests</p>
                <h2 id="reviews-title">Room reviews</h2>
                <p class="reviews-intro">Reviews from guests after their completed stays. Find out what they thought of the rooms.</p>
            </div>

            <?php if (!$latestReviews): ?>
            <div class="reviews-empty"><i class="fa-regular fa-star" aria-hidden="true"></i><h3>No room reviews yet</h3><p>Guests can rate and review their room after checkout. Their reviews will appear here.</p><a href="<?php echo isset($_SESSION['user_id']) ? 'mybooking.php#completed-stays-title' : 'rooms.php'; ?>"><?php echo isset($_SESSION['user_id']) ? 'Go to my bookings' : 'Find your next stay'; ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            <?php else: ?>
            <div class="reviews-slider-shell">
                <div class="swiper reviews-slider" aria-label="Latest room reviews">
                    <div class="swiper-wrapper">
                    <?php foreach ($latestReviews as $review): ?>
                        <article class="swiper-slide review-card" data-review-booking="<?php echo (int) $review['booking_id']; ?>">
                            <div class="review-person"><img src="<?php echo $escapeContent(staySmartReviewAvatar((int) $review['user_id'])); ?>" alt="" class="review-avatar" width="64" height="64" loading="lazy" decoding="async"><div><h3><?php echo $escapeContent($review['guest_name']); ?></h3><span><i class="fa-solid <?php echo $review['is_sample'] ? 'fa-flask' : 'fa-circle-check'; ?>" aria-hidden="true"></i> <?php echo $review['is_sample'] ? 'Demo guest review' : 'Verified stay'; ?></span></div></div>
                            <div class="review-stars" aria-label="<?php echo (int) $review['rating']; ?> out of 5 stars"><?php for ($star = 1; $star <= 5; $star++): ?><i class="<?php echo $star <= (int) $review['rating'] ? 'fa-solid' : 'fa-regular'; ?> fa-star" aria-hidden="true"></i><?php endfor; ?><span><?php echo (int) $review['rating']; ?>/5</span></div>
                            <?php if ($review['review_text'] !== ''): ?><p><?php echo nl2br($escapeContent($review['review_text'])); ?></p><?php else: ?><p class="review-rating-only">A guest rating from a completed stay.</p><?php endif; ?>
                            <div class="review-stay"><span><?php echo $escapeContent($review['room_name']); ?></span><time datetime="<?php echo $escapeContent(substr($review['created_at'], 0, 10)); ?>"><?php echo (new DateTimeImmutable($review['created_at']))->format('M Y'); ?></time></div>
                        </article>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="reviews-slider-controls" hidden><button type="button" class="reviews-prev" aria-label="Previous guest review"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button><div class="reviews-pagination"></div><button type="button" class="reviews-next" aria-label="Next guest review"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></div>
                <p class="reviews-slider-note">Latest <?php echo count($latestReviews); ?> room reviews <span aria-hidden="true">&middot;</span> Swipe or use the arrows to explore<?php if(array_filter($latestReviews,static fn($r)=>(bool)$r['is_sample'])): ?><br><span>Demo guest profiles and sample reviews are labeled.</span><?php endif; ?></p>
            </div>
            <?php endif; ?>
        </section>

        <section class="section-posts" aria-labelledby="posts-title">
            <div class="section-heading">
                <p class="section-kicker"><?php echo $escapeContent($siteContent['home_ideas_eyebrow']); ?></p>
                <h2 id="posts-title"><?php echo $escapeContent($siteContent['home_ideas_title']); ?></h2>
            </div>

            <div class="post-grid">
                <?php for ($ideaNumber = 1; $ideaNumber <= 3; $ideaNumber++): ?>
                    <?php $ideaPrefix = 'home_idea_' . $ideaNumber . '_'; ?>
                    <article class="post-card">
                        <a href="rooms.php">
                            <img src="./<?php echo $escapeContent(staySmartContentImage($siteContent[$ideaPrefix . 'image'], 'Assets/mainhome.png')); ?>" alt="<?php echo $escapeContent($siteContent[$ideaPrefix . 'title']); ?>" loading="lazy" decoding="async">
                            <div class="post-meta"><span><?php echo $escapeContent($siteContent[$ideaPrefix . 'category']); ?></span><span><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php echo 3 + $ideaNumber; ?> min read</span></div>
                            <h3><?php echo $escapeContent($siteContent[$ideaPrefix . 'title']); ?></h3>
                            <p><?php echo $escapeContent($siteContent[$ideaPrefix . 'description']); ?></p>
                        </a>
                    </article>
                <?php endfor; ?>
            </div>
        </section>
    </main>
    <?php if ($bookingConfirmation): ?>
    <link rel="stylesheet" href="CSS/checkout.css">
    <dialog id="booking-success-dialog" class="booking-success-dialog" aria-labelledby="booking-success-title" aria-describedby="booking-success-description">
        <button type="button" id="close-booking-success" class="booking-success-close" aria-label="Close booking confirmation">&times;</button>
        <span class="booking-success-icon" aria-hidden="true">&#10003;</span><p class="stay-eyebrow">LET THE COUNTDOWN BEGIN</p><h2 id="booking-success-title">Your stay is booked!</h2><p id="booking-success-description">A little escape is officially on your calendar.</p>
        <div class="booking-success-summary"><span>Booking #<?php echo (int)$bookingConfirmation['booking_id']; ?></span><strong><?php echo $escapeContent($bookingConfirmation['room_name']); ?></strong><p><?php echo (new DateTimeImmutable($bookingConfirmation['check_in']))->format('d M Y'); ?> &rarr; <?php echo (new DateTimeImmutable($bookingConfirmation['check_out']))->format('d M Y'); ?> · <?php echo (int)$bookingConfirmation['guests']; ?> guests</p><div><span>Pay at the property</span><strong>$<?php echo number_format((float)$bookingConfirmation['total_price'],2); ?> USD</strong></div><small>No online payment was taken.</small></div>
        <a class="booking-success-primary" href="booking-details.php?id=<?php echo (int)$bookingConfirmation['booking_id']; ?>" autofocus>View my booking <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><a class="booking-success-secondary" href="payments.php?booking=<?php echo (int)$bookingConfirmation['booking_id']; ?>">View payment details</a><button type="button" id="continue-after-booking" class="booking-success-continue">Continue exploring Home</button>
    </dialog>
    <script src="JS/checkout.js" defer></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="JS/reviews.js" defer></script>
    <?php include './PHP/footer.php'; ?>
