<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once './PHP/connect.php';
require_once './PHP/room-image.php';
require_once './PHP/room-ratings.php';
require_once './PHP/room-management.php';
staySmartEnsureRoomRatingsTable($con);
staySmartEnsureRoomManagementColumns($con);
$con->set_charset('utf8mb4');
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$guests = filter_var($_GET['guests'] ?? 0, FILTER_VALIDATE_INT);
$guests = $guests !== false && $guests >= 0 && $guests <= 100 ? $guests : 0;
$budget = filter_var($_GET['budget'] ?? '', FILTER_VALIDATE_FLOAT);
$budget = $budget !== false && is_finite($budget) && $budget >= 0 ? $budget : null;
$availableOnly = ($_GET['available'] ?? '') === '1';
$sort = in_array($_GET['sort'] ?? '', ['newest', 'price-low', 'price-high', 'capacity'], true) ? $_GET['sort'] : 'newest';
$allRooms = $con->query('SELECT rooms.*, (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count FROM rooms WHERE is_visible = 1 ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
$rooms = array_values(array_filter($allRooms, static function ($room) use ($query, $guests, $budget, $availableOnly) {
    return ($query === '' || stripos($room['room_name'] . ' ' . $room['facilities'] . ' ' . ($room['description'] ?? ''), $query) !== false)
        && (int) $room['capacity'] >= $guests
        && ($budget === null || (float) $room['price'] <= $budget)
        && (!$availableOnly || ((int) $room['is_open'] === 1 && (int) $room['quantity'] > 0));
}));
if ($sort !== 'newest') {
    usort($rooms, static function ($a, $b) use ($sort) {
        $comparison = $sort === 'capacity' ? (int) $b['capacity'] <=> (int) $a['capacity']
            : ($sort === 'price-high' ? (float) $b['price'] <=> (float) $a['price'] : (float) $a['price'] <=> (float) $b['price']);
        return $comparison ?: (int) $b['id'] <=> (int) $a['id'];
    });
}
$maxGuests = $allRooms ? min(100, max(array_column($allRooms, 'capacity'))) : 1;
$hasFilters = $query !== '' || $guests > 0 || $budget !== null || $availableOnly;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore StaySmart rooms and suites, compare nightly rates, and choose a stay that fits your trip.">
    <title>Rooms &amp; suites | StaySmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./CSS/header.css">
    <link rel="stylesheet" href="./CSS/footer.css">
    <link rel="stylesheet" href="./CSS/rooms.css">
</head>
<body class="rooms-page">
    <?php include './PHP/header.php'; ?>
    <main class="rooms-main">
        <section class="rooms-hero" aria-labelledby="rooms-title">
            <div class="rooms-hero-copy">
                <p class="rooms-eyebrow">MAKE YOURSELF AT HOME</p>
                <h1 id="rooms-title">A little escape.<br>A stay that’s <em>you.</em></h1>
                <p>From quiet garden mornings to a skyline after sunset. Find a room that feels just right.</p>
                <a class="rooms-explore" href="#room-results">Explore our rooms <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
                <div class="rooms-hero-note"><span class="rooms-note-line"></span> Room to unwind. Space to make memories.</div>
            </div>
            <div class="rooms-hero-image">
                <img src="Assets/our%20rooms/our-room-5.jpg" alt="A warm room with natural wood finishes and a garden outlook" fetchpriority="high" width="720" height="520">
                <div class="rooms-image-caption"><span>STAY A LITTLE LONGER</span><strong>Wake up somewhere wonderful.</strong></div>
            </div>
        </section>
        <section class="rooms-discovery" aria-label="Find a room">
            <form id="room-filters" class="rooms-filters" action="rooms.php#room-results" method="get">
                <div class="rooms-filter-field rooms-search-field">
                    <label for="room-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Your kind of stay</label>
                    <input id="room-search" name="q" type="search" value="<?php echo $escape($query); ?>" placeholder="Try garden, city or suite">
                </div>
                <div class="rooms-filter-field">
                    <label for="room-guests"><i class="fa-regular fa-user" aria-hidden="true"></i> Guests</label>
                    <select id="room-guests" name="guests">
                        <option value="0">Any group size</option>
                        <?php for ($count = 1; $count <= max((int) $maxGuests, $guests); $count++): ?>
                            <option value="<?php echo $count; ?>" <?php echo $guests === $count ? 'selected' : ''; ?>><?php echo $count; ?> <?php echo $count === 1 ? 'guest' : 'guests'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="rooms-filter-field">
                    <label for="room-budget"><i class="fa-solid fa-wallet" aria-hidden="true"></i> Budget per night</label>
                    <input id="room-budget" type="number" name="budget" min="0" step="0.01" value="<?php echo $budget === null ? '' : $escape($budget); ?>" placeholder="Max price in USD">
                </div>
                <input type="hidden" name="sort" value="<?php echo $escape($sort); ?>">
                <button class="rooms-find" type="submit">Find my stay <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                <div class="rooms-filter-options">
                    <label><input type="checkbox" name="available" value="1" <?php echo $availableOnly ? 'checked' : ''; ?>> Only rooms accepting bookings</label>
                    <?php if ($hasFilters): ?><a href="rooms.php#room-results">Clear filters <span aria-hidden="true">&times;</span></a><?php endif; ?>
                </div>
            </form>
        </section>
        <section id="room-results" class="rooms-results" aria-labelledby="room-results-title">
            <header class="rooms-results-heading">
                <div><p class="rooms-eyebrow">FIND YOUR NEXT FAVORITE PLACE</p><h2 id="room-results-title">Rooms &amp; suites <span><?php echo count($rooms); ?></span></h2><p><?php echo $hasFilters ? count($rooms) . ' of ' . count($allRooms) . ' rooms match your search.' : 'Thoughtful spaces for every kind of getaway.'; ?></p></div>
                <form class="rooms-sort" action="rooms.php#room-results" method="get">
                    <input type="hidden" name="q" value="<?php echo $escape($query); ?>">
                    <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                    <input type="hidden" name="budget" value="<?php echo $budget === null ? '' : $escape($budget); ?>">
                    <?php if ($availableOnly): ?><input type="hidden" name="available" value="1"><?php endif; ?>
                    <label for="room-sort">Sort by</label>
                    <select id="room-sort" name="sort">
                        <?php foreach (['newest' => 'Newest rooms', 'price-low' => 'Price: low to high', 'price-high' => 'Price: high to low', 'capacity' => 'Guest capacity'] as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select><button type="submit">Apply</button>
                </form>
            </header>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room):
                    $canBook = (int) $room['is_open'] === 1 && (int) $room['quantity'] > 0;
                    $image = staySmartRoomImage($room['image']);
                    $hasImage = $room['image'] !== '' && is_file(__DIR__ . '/Assets/our rooms/' . $image);
                    $facilities = array_values(array_filter(array_map('trim', explode(',', $room['facilities']))));
                    $bookingUrl = 'room-details.php?id=' . (int) $room['id'];
                ?>
                <article class="stay-card" data-room-id="<?php echo (int) $room['id']; ?>" aria-labelledby="stay-title-<?php echo (int) $room['id']; ?>">
                    <div class="stay-photo">
                        <a class="stay-photo-link" href="<?php echo $escape($bookingUrl); ?>" aria-label="View <?php echo $escape($room['room_name']); ?>"><?php if ($hasImage): ?><img src="Assets/our%20rooms/<?php echo rawurlencode($image); ?>" alt="" loading="lazy" decoding="async" width="640" height="430"><?php else: ?><div class="stay-no-photo"><i class="fa-solid fa-bed" aria-hidden="true"></i><span>A comfortable stay awaits</span></div><?php endif; ?></a>
                        <span class="stay-rating"><?php if ((int) $room['review_count'] > 0): ?><i class="fa-solid fa-star" aria-hidden="true"></i> <?php echo number_format((float) $room['rating'], 1); ?><span>(<?php echo (int) $room['review_count']; ?>)</span><?php else: ?>No ratings yet<?php endif; ?></span>
                        <?php if (!$canBook): ?><span class="stay-unavailable"><?php echo (int) $room['is_open'] !== 1 ? 'Bookings closed' : 'Fully booked'; ?></span><?php endif; ?>
                    </div>
                    <div class="stay-content">
                        <div class="stay-meta"><span><i class="fa-regular fa-user" aria-hidden="true"></i> Up to <?php echo (int) $room['capacity']; ?> guests</span><span><i class="fa-solid fa-expand" aria-hidden="true"></i> <?php echo $escape($room['size']); ?></span></div>
                        <h3 id="stay-title-<?php echo (int) $room['id']; ?>"><a href="<?php echo $escape($bookingUrl); ?>"><?php echo $escape($room['room_name']); ?></a></h3>
                        <p class="stay-description"><?php echo $escape(trim($room['description'] ?? '') ?: 'Settle into a comfortable space with thoughtful amenities for your stay.'); ?></p>
                        <p class="stay-beds"><i class="fa-solid fa-bed" aria-hidden="true"></i> <?php echo $escape($room['beds']); ?></p>
                        <ul class="stay-amenities" aria-label="Room amenities"><?php foreach (array_slice($facilities, 0, 3) as $facility): ?><li><?php echo $escape($facility); ?></li><?php endforeach; ?></ul>
                        <?php if (count($facilities) > 3): ?><details class="stay-more-amenities"><summary>+<?php echo count($facilities) - 3; ?> more amenities</summary><ul><?php foreach (array_slice($facilities, 3) as $facility): ?><li><?php echo $escape($facility); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
                        <div class="stay-card-bottom"><div class="stay-price"><strong>$<?php echo number_format((float) $room['price'], 2); ?></strong><span>/ night</span><small>USD · per room</small></div>
                            <a class="stay-book" href="<?php echo $escape($bookingUrl); ?>" aria-label="View details of <?php echo $escape($room['room_name']); ?>">View details <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$rooms): ?><div class="rooms-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h3><?php echo $hasFilters ? 'A different search, a new possibility.' : 'New stays are on the way.'; ?></h3><p><?php echo $hasFilters ? 'Try fewer filters or a higher budget to find your next stay.' : 'Check back soon, or contact our team for help planning your visit.'; ?></p><a href="<?php echo $hasFilters ? 'rooms.php#room-results' : 'contact.php'; ?>"><?php echo $hasFilters ? 'See all rooms' : 'Contact our team'; ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div><?php endif; ?>
        </section>
        <aside class="rooms-help"><span class="rooms-help-icon"><i class="fa-regular fa-comments" aria-hidden="true"></i></span><div><h2>Need a hand choosing?</h2><p>Tell us a little about your trip. We’ll help you find your fit.</p></div><a href="contact.php">Let’s talk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></aside>
    </main>
    <?php include './PHP/footer.php'; ?>
