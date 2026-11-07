
<?php

    session_start();
require_once __DIR__ . '/../PHP/auth.php';

    $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    $sendJson = static function (array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    };

    if (!isset($_SESSION['admin_id'])) {
        if ($isAjax) {
            $sendJson(['success' => false, 'message' => 'Please sign in as an administrator again.'], 401);
        }
        header("Location: ../index.php");
        exit;
    }

    try {
    include '../PHP/connect.php';
    require_once '../PHP/room-ratings.php';
    require_once '../PHP/room-management.php';
    staySmartEnsureRoomRatingsTable($con);
    staySmartEnsureRoomManagementColumns($con);
    } catch (mysqli_sql_exception $error) {
        error_log('StaySmart room database unavailable: ' . $error->getMessage());
        if ($isAjax) {
            $sendJson(['success' => false, 'message' => 'Room storage is unavailable. Check the database connection and schema.'], 503);
        }
        http_response_code(503);
        exit('Room storage is unavailable. Check the database connection and schema.');
    }
    if (empty($_SESSION['admin_room_csrf'])) {
        $_SESSION['admin_room_csrf'] = bin2hex(random_bytes(32));
    }

    $roomImageLibrary = [
        ['image' => 'bright-lounge-room.png', 'name' => 'Classic Lounge Suite', 'facilities' => 'Separate lounge area, television, bright windows', 'beds' => '1 double bed'],
        ['image' => 'coastal-blue-bedroom.png', 'name' => 'Coastal Blue Double Room', 'facilities' => 'Blue accent decor, bedside table, reading light', 'beds' => '1 double bed'],
        ['image' => 'garden-view-living-suite.png', 'name' => 'Garden View Living Suite', 'facilities' => 'Open-plan lounge, dining area, garden view', 'beds' => '1 king bed'],
        ['image' => 'contemporary-lounge-suite.png', 'name' => 'Contemporary Lounge Suite', 'facilities' => 'Spacious seating area, modern interior', 'beds' => '1 king bed'],
        ['image' => 'neutral-double-bedroom.png', 'name' => 'Soft Neutral Double Room', 'facilities' => 'Neutral decor, window view, bedside lighting', 'beds' => '1 double bed'],
        ['image' => 'modern-king-bedroom.png', 'name' => 'Modern King Room', 'facilities' => 'Modern decor, bedside lighting, sitting area', 'beds' => '1 king bed'],
        ['image' => 'sunlit-deluxe-bedroom.png', 'name' => 'Sunlit Deluxe Room', 'facilities' => 'Bright interior, large window, modern furnishings', 'beds' => '1 king bed'],
        ['image' => 'bright-garden-bedroom.png', 'name' => 'Bright Garden Suite', 'facilities' => 'Bright bedroom, garden view, sitting area', 'beds' => '1 king bed'],
        ['image' => 'warm-accent-bedroom.png', 'name' => 'Warm Accent Double Room', 'facilities' => 'Warm accent decor, bedside lighting, city view', 'beds' => '1 double bed'],
        ['image' => 'minimalist-bedroom-suite.png', 'name' => 'Minimalist Garden Suite', 'facilities' => 'Minimalist decor, garden view, seating area', 'beds' => '1 king bed'],
        ['image' => 'twin-comfort-bedroom.png', 'name' => 'Twin Comfort Room', 'facilities' => 'Two separate beds, bright interior', 'beds' => '2 single beds'],
        ['image' => 'open-living-suite.png', 'name' => 'Open Living Suite', 'facilities' => 'Separate lounge, large windows, seating area', 'beds' => '1 king bed'],
        ['image' => 'glass-dome-retreat.png', 'name' => 'Glass Dome Retreat', 'facilities' => 'Panoramic glass ceiling, unique interior design', 'beds' => '1 double bed'],
        ['image' => 'city-view-king-bedroom.png', 'name' => 'City View King Room', 'facilities' => 'Floor-to-ceiling windows, city view, bedside lighting', 'beds' => '1 king bed'],
        ['image' => 'compact-city-bedroom.png', 'name' => 'Compact City Double Room', 'facilities' => 'City view, television, bedside lighting', 'beds' => '1 double bed'],
        ['image' => 'contemporary-bedroom-suite.png', 'name' => 'Contemporary Bedroom Suite', 'facilities' => 'Modern bedroom, lounge area, large windows', 'beds' => '1 king bed'],
        ['image' => 'warm-classic-suite.png', 'name' => 'Warm Classic Suite', 'facilities' => 'Warm decor, bedside lighting, sitting area', 'beds' => '1 king bed'],
        ['image' => 'woodland-cabin-bedroom.png', 'name' => 'Woodland Cabin Suite', 'facilities' => 'Wood interior, garden access, warm lighting', 'beds' => '1 queen bed'],
    ];

    $formValues = [
        'room_name' => '',
        'price' => '',
        'size' => '',
        'beds' => '',
        'capacity' => '2',
        'adult' => '2',
        'kids' => '0',
        'facilities' => '',
        'quantity' => '1',
        'catalog_image' => '',
    ];
    $roomFormError = '';
    $roomFormMessage = $_SESSION['admin_room_message'] ?? '';
    unset($_SESSION['admin_room_message']);
    $postValue = static function ($key) {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newUploadPath = '';
        try {
        $submittedToken = $_POST['csrf_token'] ?? '';
        if (!is_string($submittedToken) || !hash_equals($_SESSION['admin_room_csrf'], $submittedToken)) {
            $roomFormError = 'Your session expired. Reload the page and try again.';
        }
        foreach ($formValues as $field => $defaultValue) {
            $formValues[$field] = $postValue($field);
        }

        $roomName = $formValues['room_name'];
        $roomSize = $formValues['size'];
        $bedConfig = $formValues['beds'];
        $roomFacilities = $formValues['facilities'];
        $catalogImage = basename($formValues['catalog_image']);
        $price = filter_var($formValues['price'], FILTER_VALIDATE_FLOAT);
        $capacity = filter_var($formValues['capacity'], FILTER_VALIDATE_INT);
        $adult = filter_var($formValues['adult'], FILTER_VALIDATE_INT);
        $kids = filter_var($formValues['kids'], FILTER_VALIDATE_INT);
        $rating = 0.0;
        $quantity = filter_var($formValues['quantity'], FILTER_VALIDATE_INT);
        $catalogImageNames = array_column($roomImageLibrary, 'image');
        $uploadedImage = $_FILES['image'] ?? null;

        if (
            $roomFormError !== '' ||
            $roomName === '' || strlen($roomName) > 150 ||
            $roomSize === '' || strlen($roomSize) > 50 ||
            $bedConfig === '' || strlen($bedConfig) > 100 ||
            $roomFacilities === '' || strlen($roomFacilities) > 5000 ||
            $price === false || $price < 0 ||
            $capacity === false || $capacity < 1 ||
            $adult === false || $adult < 1 ||
            $kids === false || $kids < 0 ||
            $quantity === false || $quantity < 0
        ) {
            if ($roomFormError === '') {
                $roomFormError = 'Check the room details and enter valid values for every field.';
            }
        } elseif (!in_array($catalogImage, $catalogImageNames, true) && (!$uploadedImage || $uploadedImage['error'] === UPLOAD_ERR_NO_FILE)) {
            $roomFormError = 'Choose a room photo from the gallery or upload your own image.';
        } elseif ($uploadedImage && $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE && $uploadedImage['error'] !== UPLOAD_ERR_OK) {
            $roomFormError = 'The image upload did not complete. Please try a smaller image.';
        } else {
            $roomCheck = $con->prepare('SELECT id FROM rooms WHERE room_name = ? LIMIT 1');
            $roomCheck->bind_param('s', $roomName);
            $roomCheck->execute();
            $roomExists = $roomCheck->get_result()->fetch_assoc();
            $roomCheck->close();

            if ($roomExists) {
                $roomFormError = 'A room with that name already exists.';
            } else {
                $roomImage = $catalogImage;
                $newUploadPath = '';

                if ($uploadedImage && $uploadedImage['error'] === UPLOAD_ERR_OK) {
                    if ($uploadedImage['size'] > 5 * 1024 * 1024 || !is_uploaded_file($uploadedImage['tmp_name'])) {
                        $roomFormError = 'Choose an image smaller than 5 MB.';
                    } else {
                        $imageInfo = getimagesize($uploadedImage['tmp_name']);
                        $mimeToExtension = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                        ];
                        $imageMime = $imageInfo['mime'] ?? '';

                        if (!$imageInfo || !isset($mimeToExtension[$imageMime])) {
                            $roomFormError = 'Upload a valid JPEG, PNG, or WebP room photo.';
                        } else {
                            $roomImage = 'room-' . bin2hex(random_bytes(12)) . '.' . $mimeToExtension[$imageMime];
                            $newUploadPath = __DIR__ . '/../Assets/our rooms/' . $roomImage;

                            if (!move_uploaded_file($uploadedImage['tmp_name'], $newUploadPath)) {
                                $roomFormError = 'The image could not be saved. Check the room photo folder permissions.';
                            }
                        }
                    }
                }

                if ($roomFormError === '') {
                    $insertRoom = $con->prepare(
                        'INSERT INTO rooms (room_name, price, size, beds, capacity, image, facilities, rating, quantity, adult, kids)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insertRoom->bind_param(
                        'sdssissdiii',
                        $roomName,
                        $price,
                        $roomSize,
                        $bedConfig,
                        $capacity,
                        $roomImage,
                        $roomFacilities,
                        $rating,
                        $quantity,
                        $adult,
                        $kids
                    );

                    if ($insertRoom->execute()) {
                        $insertRoom->close();
                        if ($isAjax) {
                            $sendJson(['success' => true, 'message' => 'Room added to your inventory.', 'room_id' => (int) $con->insert_id]);
                        }
                        $_SESSION['admin_room_message'] = 'Room added to your inventory.';
                        header('Location: addrooms.php');
                        exit;
                    }

                    if ($newUploadPath !== '' && is_file($newUploadPath)) {
                        unlink($newUploadPath);
                    }
                    $roomFormError = 'The room could not be added. Please review the details and try again.';
                    $insertRoom->close();
                }
            }
        }
        } catch (mysqli_sql_exception $error) {
            if ($newUploadPath !== '' && is_file($newUploadPath)) {
                unlink($newUploadPath);
            }
            error_log('StaySmart room creation failed: ' . $error->getMessage());
            $roomFormError = (int) $error->getCode() === 1062
                ? 'A room with that name already exists.'
                : 'The room could not be saved. Check that the StaySmart database schema has been imported and try again.';
        }
        if ($isAjax) {
            $sendJson(['success' => false, 'message' => $roomFormError], 422);
        }
    }
?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage rooms | StaySmart</title>
    <?php include '../PHP/head.php'; ?>
    <?php include 'adminlinks.php'; ?>
    <link rel="stylesheet" href="../CSS/admin-inventory.css">

</head>

<body class="admin-rooms-page">

    <?php include '../PHP/header.php'; ?>
    <?php include 'admin-header.php'; ?>
    <main class="admin-rooms-main">
        <header class="admin-page-heading">
            <div>
                <p class="admin-page-eyebrow">Room inventory</p>
                <h1>Manage rooms</h1>
                <p>Add a room and keep your available inventory up to date.</p>
            </div>
        </header>
    <div class="addroom-container">

        <section class="insertroom">
            <h2>Add a room</h2>
            <p id="room-form-notice" class="room-management-notice" role="status" aria-live="polite" hidden></p>
            <form action="addrooms.php" class="insertroomform" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_room_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="admin-form-field">
                    <label for="room_name">Room name</label>
                    <input type="text" id="room_name" name="room_name" required>
                </div>
                <div class="admin-form-field">
                    <label for="price">Price per night</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                </div>
                <div class="admin-form-field">
                    <label for="size">Area</label>
                    <input type="text" id="size" name="size" required>
                </div>
                <div class="admin-form-field">
                    <label for="capacity">Total capacity</label>
                    <input type="number" id="capacity" name="capacity" min="1" required>
                </div>
                <div class="admin-form-field">
                    <label for="adult">Adults</label>
                    <input type="number" id="adult" name="adult" min="1" required>
                </div>
                <div class="admin-form-field">
                    <label for="kids">Kids</label>
                    <input type="number" id="kids" name="kids" min="0" required>
                </div>
                <div class="admin-form-field">
                    <label for="facilities">Facilities</label>
                    <input type="text" id="facilities" name="facilities" required>
                </div>
                <div class="admin-form-field">
                    <label for="beds">Bed configuration</label>
                    <input type="text" id="beds" name="beds" required>
                </div>
                <div class="admin-form-field">
                    <label for="rating">Guest rating</label>
                    <input type="text" id="rating" value="0.00" readonly>
                </div>
                <div class="admin-form-field">
                    <label for="quantity">Room quantity</label>
                    <input type="number" id="quantity" name="quantity" min="0" required>
                </div>
                <div class="admin-form-field admin-form-field-wide">
                    <label for="image">Room photo</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="room-photo-help" required>
                    <small id="room-photo-help">JPEG, PNG or WebP, up to 5 MB.</small>
                </div>
                <button type="submit" name="adminsubmit" class="addroombtn">Add room <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </form>
        </section>

        <section class="roomsindex" aria-labelledby="inventory-title">

            <div class="admin-section-heading">
                <div>
                    <p class="admin-page-eyebrow">Current inventory</p>
                    <h2 id="inventory-title">Rooms</h2>
                    <p class="inventory-intro">Your rooms, rates and availability at a glance.</p>
                </div>
            </div>
            <?php if ($roomFormMessage !== ''): ?>
                <p class="room-management-notice is-success" role="status"><?php echo htmlspecialchars($roomFormMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if ($roomFormError !== ''): ?>
                <p class="room-management-notice is-error" role="alert"><?php echo htmlspecialchars($roomFormError, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php
            $selectrooms = "SELECT rooms.*,
                (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count
                FROM rooms ORDER BY rooms.id DESC";
            $showrooms = mysqli_query($con, $selectrooms);
            $inventory = $showrooms->fetch_all(MYSQLI_ASSOC);
            $accepting = count(array_filter($inventory, static function ($room) {
                return (int) $room['is_visible'] === 1 && (int) $room['is_open'] === 1 && (int) $room['quantity'] > 0;
            }));
            require_once '../PHP/room-image.php';
            ?>

            <div class="inventory-summary">
                <div><span>Room types</span><strong data-inventory-total><?php echo count($inventory); ?></strong></div>
                <div><span>Accepting bookings</span><strong data-inventory-open><?php echo $accepting; ?></strong></div>
                <div><span>Units available</span><strong data-inventory-units><?php echo array_sum(array_column($inventory, 'quantity')); ?></strong></div>
            </div>
            <div class="inventory-toolbar">
                <div class="inventory-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <label class="sr-only" for="inventory-search">Search rooms by name or ID</label>
                    <input id="inventory-search" type="search" placeholder="Search room name or ID" autocomplete="off">
                </div>
                <label class="sr-only" for="inventory-filter">Filter room status</label>
                <select id="inventory-filter">
                    <option value="all">All rooms</option>
                    <option value="accepting">Accepting bookings</option>
                    <option value="hidden">Hidden</option>
                    <option value="closed">Bookings closed</option>
                    <option value="soldout">No units available</option>
                </select>
            </div>
            <p class="inventory-result-count" id="inventory-results" aria-live="polite"><?php echo count($inventory); ?> room types</p>
            <div class="table-container">
                <table class="rooms-table">
                    <caption class="sr-only">Room inventory with nightly rates, available units and management actions</caption>
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Nightly rate</th>
                            <th scope="col">Inventory</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $roomrow) : ?>
                            <tr data-room-row data-room-search="<?php echo htmlspecialchars($roomrow['room_name'] . ' ' . $roomrow['id'], ENT_QUOTES, 'UTF-8'); ?>" data-visible="<?php echo (int) $roomrow['is_visible']; ?>" data-open="<?php echo (int) $roomrow['is_open']; ?>" data-quantity="<?php echo (int) $roomrow['quantity']; ?>">
                                <td data-label="Room" class="inventory-room-cell">
                                    <div class="inventory-room">
                                        <?php if ($roomrow['image'] !== '' && is_file(__DIR__ . '/../Assets/our rooms/' . staySmartRoomImage($roomrow['image']))): ?>
                                            <img src="../Assets/our%20rooms/<?php echo rawurlencode(staySmartRoomImage($roomrow['image'])); ?>" alt="" width="56" height="64" loading="lazy">
                                        <?php endif; ?>
                                        <div class="inventory-room-copy">
                                            <span class="inventory-room-name"><?php echo htmlspecialchars($roomrow['room_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="inventory-room-meta">#<?php echo (int) $roomrow['id']; ?> &middot; <?php echo (int) $roomrow['capacity']; ?> guests &middot; <?php echo htmlspecialchars($roomrow['size'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="inventory-rating">
                                    <?php if ((int) $roomrow['review_count'] > 0): ?>
                                        <span class="fa-solid fa-star" aria-hidden="true"></span>
                                        <?php echo number_format((float) $roomrow['rating'], 1); ?>
                                        (<?php echo (int) $roomrow['review_count']; ?>)
                                    <?php else: ?>
                                        No reviews yet
                                    <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Nightly rate"><strong class="inventory-price">$<?php echo number_format((float) $roomrow['price'], 2); ?></strong><span class="inventory-cell-note">per night</span></td>
                                <td data-label="Inventory"><strong class="inventory-quantity <?php echo (int) $roomrow['quantity'] === 0 ? 'is-empty' : ''; ?>"><?php echo (int) $roomrow['quantity']; ?></strong><span class="inventory-cell-note">units available</span></td>
                                <td data-label="Status">
                                  <div class="inventory-statuses">
                                    <span class="room-management-badge <?php echo (int) $roomrow['is_visible'] === 1 ? 'is-visible' : 'is-hidden'; ?>">
                                        <?php echo (int) $roomrow['is_visible'] === 1 ? 'Visible' : 'Hidden'; ?>
                                    </span>
                                    <span class="room-management-badge <?php echo (int) $roomrow['is_open'] === 1 ? 'is-open' : 'is-closed'; ?>">
                                        <?php echo (int) $roomrow['is_open'] === 1 ? 'Bookings open' : 'Bookings closed'; ?>
                                    </span>
                                  </div>
                                </td>
                                <td data-label="Action" class="room-management-actions-cell">
                                    <div class="room-management-actions">
                                      <details class="inventory-more">
                                        <summary aria-label="More actions for <?php echo htmlspecialchars($roomrow['room_name'], ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-ellipsis" aria-hidden="true"></i><span>More</span></summary>
                                        <div class="inventory-more-menu">
                                        <a class="room-action-button is-view" href="room.php?id=<?php echo (int) $roomrow['id']; ?>&amp;action=view">
                                            <i class="fa-regular fa-eye" aria-hidden="true"></i> View
                                        </a>
                                        <a class="room-action-button is-edit" href="room.php?id=<?php echo (int) $roomrow['id']; ?>&amp;action=edit">
                                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Edit
                                        </a>
                                        <form action="room-action.php" method="post" data-confirm="Delete this room? Rooms with bookings or reviews cannot be deleted.">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_room_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="room_id" value="<?php echo (int) $roomrow['id']; ?>">
                                            <button class="room-action-button is-delete" type="submit" name="action" value="delete">
                                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i> Delete
                                            </button>
                                        </form>
                                        </div>
                                      </details>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($inventory) === 0): ?>
                            <tr><td class="admin-empty-cell" colspan="5"><i class="fa-solid fa-bed" aria-hidden="true"></i><strong>Your inventory starts here</strong><span>Add your first room using the form to see it here.</span></td></tr>
                        <?php endif; ?>
                        <tr id="inventory-no-results" hidden><td class="admin-empty-cell" colspan="5"><strong>No matching rooms</strong><span>Try a different name or status.</span><button type="button" id="inventory-clear">Clear filters</button></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        
    </div>
    </main>
    <dialog id="room-dialog" class="room-dialog" aria-labelledby="room-dialog-title">
        <header class="room-dialog-header">
            <h2 id="room-dialog-title">Room details</h2>
            <button type="button" id="room-dialog-close" aria-label="Close room window">&times;</button>
        </header>
        <p id="room-dialog-loading" role="status">Loading room...</p>
        <iframe id="room-dialog-frame" title="Room information and booking details" src="about:blank"></iframe>
    </dialog>
    <?php include '../PHP/footer.php'; ?>
    <script src="../JS/admin-rooms.js" defer></script>
</body>
</html>
