<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/public-room.php';
$amenities = $room ? array_filter(array_map('trim', explode(',', $room['facilities']))) : [];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $room ? $escape($room['room_name']) : 'Room unavailable'; ?> | StaySmart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css">
</head><body class="stay-page">
<?php include 'PHP/header.php'; ?>
<main class="stay-main">
<nav class="stay-breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span aria-hidden="true">/</span><a href="rooms.php">Rooms</a><span aria-hidden="true">/</span><span>Room details</span></nav>
<?php if (!$room): ?>
<section class="stay-empty"><h1>This room is unavailable.</h1><p>Explore our other rooms to find your next stay.</p><a class="stay-primary" href="rooms.php">Browse rooms</a></section>
<?php else: ?>
<header class="stay-heading"><p class="stay-eyebrow">A PLACE TO MAKE YOUR OWN</p><h1><?php echo $escape($room['room_name']); ?></h1><div class="stay-facts"><span><i class="fa-regular fa-user" aria-hidden="true"></i> Up to <?php echo (int) $room['capacity']; ?> guests</span><span><i class="fa-solid fa-expand" aria-hidden="true"></i> <?php echo $escape($room['size']); ?></span><span><i class="fa-solid fa-bed" aria-hidden="true"></i> <?php echo $escape($room['beds']); ?></span><span class="stay-review"><?php if ((int) $room['review_count'] > 0): ?><i class="fa-solid fa-star" aria-hidden="true"></i> <?php echo number_format((float) $room['rating'], 1); ?> · <?php echo (int) $room['review_count']; ?> reviews<?php else: ?>No ratings yet &middot; Be among our first guests<?php endif; ?></span></div></header>
<div class="stay-layout">
    <div class="stay-detail-content">
        <div class="stay-main-photo"><?php if ($hasPhoto): ?><img src="Assets/our%20rooms/<?php echo rawurlencode($roomPhoto); ?>" alt="<?php echo $escape($room['room_name']); ?>" width="1000" height="670" fetchpriority="high"><?php else: ?><div class="stay-photo-message"><i class="fa-solid fa-bed" aria-hidden="true"></i><span>Your next comfortable stay</span></div><?php endif; ?></div>
        <section class="stay-section"><h2>A little more about your stay</h2><p><?php echo nl2br($escape($room['description'] ?: 'A comfortable space with thoughtful amenities, ready for your next getaway.')); ?></p><div class="stay-occupancy"><span><strong><?php echo (int) $room['adult']; ?></strong> adults</span><span><strong><?php echo (int) $room['kids']; ?></strong> children</span><span><strong><?php echo $escape($room['size']); ?></strong> room area</span></div></section>
        <section class="stay-section"><h2>What this room offers</h2><ul class="stay-amenity-list"><?php foreach ($amenities as $amenity): ?><li><i class="fa-solid fa-check" aria-hidden="true"></i><?php echo $escape($amenity); ?></li><?php endforeach; ?></ul></section>
    </div>
    <aside class="stay-reservation-card" aria-label="Room rate and booking">
        <p class="stay-eyebrow">YOUR NEXT GETAWAY</p><div class="stay-rate"><strong>$<?php echo number_format((float) $room['price'], 2); ?></strong><span> / night</span></div><p class="stay-muted">USD · per room</p>
        <div class="stay-availability <?php echo $canBook ? '' : 'is-unavailable'; ?>"><span aria-hidden="true"></span><?php echo $canBook ? 'Accepting reservations' : ((int) $room['is_open'] !== 1 ? 'Bookings are currently closed' : 'Fully booked right now'); ?></div>
        <p class="stay-card-copy">Choose your dates and guest count on the next step. Your stay total updates as you plan.</p>
        <?php if ($canBook): ?><a class="stay-primary" href="booking-form.php?id=<?php echo (int) $room['id']; ?>">Choose dates &amp; book <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><?php else: ?><a class="stay-primary" href="rooms.php">Explore other rooms</a><?php endif; ?>
        <div class="stay-card-help"><i class="fa-regular fa-comments" aria-hidden="true"></i><span>Have a question?<br><a href="contact.php">We’re happy to help.</a></span></div>
    </aside>
</div>
<?php endif; ?>
</main>
<?php include 'PHP/footer.php'; ?>
