<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
$embedded = ($_GET['popup'] ?? '') === '1';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../PHP/connect.php';
require_once '../PHP/room-management.php';
require_once '../PHP/room-ratings.php';
staySmartEnsureRoomManagementColumns($con);
staySmartEnsureRoomRatingsTable($con);

$roomId = filter_var($_GET['id'] ?? $_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
$requestedAction = $_GET['action'] ?? 'view';
$action = $requestedAction === 'edit' ? 'edit' : 'view';
if ($roomId === false || $roomId < 1) {
    http_response_code(400);
    exit('A valid room ID is required.');
}

if (empty($_SESSION['admin_room_csrf'])) {
    $_SESSION['admin_room_csrf'] = bin2hex(random_bytes(32));
}

$roomQuery = $con->prepare(
    'SELECT rooms.*,
        (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count
    FROM rooms WHERE id = ? LIMIT 1'
);
$roomQuery->bind_param('i', $roomId);
$roomQuery->execute();
$room = $roomQuery->get_result()->fetch_assoc();
$roomQuery->close();
if (!$room) {
    http_response_code(404);
    exit('Room not found.');
}

$errorMessage = '';
$successMessage = $_SESSION['admin_room_edit_message'] ?? '';
unset($_SESSION['admin_room_edit_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room'])) {
    $newImagePath = '';
    try {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['admin_room_csrf'], $submittedToken)) {
        $errorMessage = 'Your session expired. Reload the page and try again.';
    } else {
        $roomName = is_string($_POST['room_name'] ?? null) ? trim($_POST['room_name']) : '';
        $roomSize = is_string($_POST['size'] ?? null) ? trim($_POST['size']) : '';
        $roomBeds = is_string($_POST['beds'] ?? null) ? trim($_POST['beds']) : '';
        $roomFacilities = is_string($_POST['facilities'] ?? null) ? trim($_POST['facilities']) : '';
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
        $adult = filter_var($_POST['adult'] ?? null, FILTER_VALIDATE_INT);
        $kids = filter_var($_POST['kids'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        $isVisible = ($_POST['is_visible'] ?? '') === '1' ? 1 : 0;
        $isOpen = ($_POST['is_open'] ?? '') === '1' ? 1 : 0;

        if (
            $roomName === '' || strlen($roomName) > 150 ||
            $roomSize === '' || strlen($roomSize) > 50 ||
            $roomBeds === '' || strlen($roomBeds) > 100 ||
            $roomFacilities === '' || strlen($roomFacilities) > 5000 ||
            $price === false || $price < 0 ||
            $capacity === false || $capacity < 1 ||
            $adult === false || $adult < 1 ||
            $kids === false || $kids < 0 ||
            $quantity === false || $quantity < 0
        ) {
            $errorMessage = 'Review the room details and enter valid values for every field.';
        } else {
            $nameCheck = $con->prepare('SELECT id FROM rooms WHERE room_name = ? AND id <> ? LIMIT 1');
            $nameCheck->bind_param('si', $roomName, $roomId);
            $nameCheck->execute();
            $duplicateName = $nameCheck->get_result()->fetch_assoc();
            $nameCheck->close();
            if ($duplicateName) {
                $errorMessage = 'Another room already uses that name.';
            }
        }

        $newImagePath = '';
        $roomImage = (string) $room['image'];
        $upload = $_FILES['image'] ?? null;
        if ($errorMessage === '' && $upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024 || !is_uploaded_file($upload['tmp_name'])) {
                $errorMessage = 'Choose a room image under 5 MB.';
            } else {
                $imageInfo = getimagesize($upload['tmp_name']);
                $mimeToExtension = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                $imageMime = $imageInfo['mime'] ?? '';
                if (!$imageInfo || !isset($mimeToExtension[$imageMime])) {
                    $errorMessage = 'Upload a valid JPEG, PNG, or WebP room image.';
                } else {
                    $roomImage = 'room-' . bin2hex(random_bytes(12)) . '.' . $mimeToExtension[$imageMime];
                    $newImagePath = __DIR__ . '/../Assets/our rooms/' . $roomImage;
                    if (!move_uploaded_file($upload['tmp_name'], $newImagePath)) {
                        $newImagePath = '';
                        $errorMessage = 'The room image could not be saved. Check folder permissions.';
                    }
                }
            }
        }

        if ($errorMessage === '') {
            $updateRoom = $con->prepare(
                'UPDATE rooms
                SET room_name = ?, price = ?, size = ?, beds = ?, capacity = ?, image = ?,
                    facilities = ?, quantity = ?, adult = ?, kids = ?, is_visible = ?, is_open = ?
                WHERE id = ?'
            );
            $updateRoom->bind_param(
                'sdssissiiiiii',
                $roomName,
                $price,
                $roomSize,
                $roomBeds,
                $capacity,
                $roomImage,
                $roomFacilities,
                $quantity,
                $adult,
                $kids,
                $isVisible,
                $isOpen,
                $roomId
            );
            if ($updateRoom->execute()) {
                $updateRoom->close();
                $_SESSION['admin_room_edit_message'] = 'Room details saved.';
                header('Location: room.php?id=' . $roomId . '&action=view' . ($embedded ? '&popup=1' : ''));
                exit;
            }
            $updateRoom->close();
            if ($newImagePath !== '' && is_file($newImagePath)) {
                unlink($newImagePath);
            }
            $errorMessage = 'Room details could not be saved. Please try again.';
        } elseif ($newImagePath !== '' && is_file($newImagePath)) {
            unlink($newImagePath);
        }

        if ($errorMessage !== '') {
            $room['room_name'] = $roomName;
            $room['price'] = $price === false ? $room['price'] : $price;
            $room['size'] = $roomSize;
            $room['beds'] = $roomBeds;
            $room['capacity'] = $capacity === false ? $room['capacity'] : $capacity;
            $room['facilities'] = $roomFacilities;
            $room['quantity'] = $quantity === false ? $room['quantity'] : $quantity;
            $room['adult'] = $adult === false ? $room['adult'] : $adult;
            $room['kids'] = $kids === false ? $room['kids'] : $kids;
            $room['is_visible'] = $isVisible;
            $room['is_open'] = $isOpen;
            $action = 'edit';
        }
    }
    } catch (mysqli_sql_exception $exception) {
        if ($newImagePath !== '' && is_file($newImagePath)) unlink($newImagePath);
        error_log('StaySmart room edit failed: ' . $exception->getMessage());
        $errorMessage = (int) $exception->getCode() === 1062 ? 'Another room already uses that name.' : 'Room details could not be saved. Please try again.';
        $action = 'edit';
    }
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$storedImage = basename((string) $room['image']);
$imagePath = __DIR__ . '/../Assets/our rooms/' . $storedImage;
$imageUrl = is_file($imagePath) ? '../Assets/our rooms/' . rawurlencode($storedImage) : '';
$bookingRows = [];
if ($action === 'view') {
    $bookingQuery = $con->prepare(
        "SELECT booking_id, user_name AS guest, check_in, check_out, guests, phone_number,
            total_price, status AS status FROM bookings WHERE room_id = ? ORDER BY check_in DESC"
    );
    $bookingQuery->bind_param('i', $roomId);
    $bookingQuery->execute();
    $bookingRows = $bookingQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $historyQuery = $con->prepare(
        "SELECT a.booking_id, COALESCE(u.full_name, u.username, 'Former guest') AS guest,
            a.check_in, a.check_out, a.total_price
        FROM archive_bookings a LEFT JOIN users u ON u.id = a.user_id
        WHERE a.room_id = ? ORDER BY a.check_out DESC"
    );
    $historyQuery->bind_param('i', $roomId);
    $historyQuery->execute();
    $historyRows = $historyQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'edit' ? 'Edit' : 'View'; ?> room | StaySmart</title>
    <?php include '../PHP/head.php'; ?>
    <?php include 'adminlinks.php'; ?>
    <link rel="stylesheet" href="../CSS/admin-inventory.css">
</head>

<body class="admin-rooms-page<?php echo $embedded ? ' room-popup-page' : ''; ?>">
    <?php if (!$embedded): ?>
    <?php include '../PHP/header.php'; ?>
    <?php include 'admin-header.php'; ?>
    <?php endif; ?>

    <main class="admin-room-detail-main">
        <div class="admin-room-detail-shell">
            <header class="admin-page-heading">
                <div>
                    <p class="admin-page-eyebrow">Room inventory</p>
                    <h1><?php echo $action === 'edit' ? 'Edit room' : 'Room details'; ?></h1>
                    <p><?php echo $escape((string) $room['room_name']); ?></p>
                </div>
                <div class="admin-room-detail-actions">
                    <?php if (!$embedded): ?><a class="room-action-button is-view" href="addrooms.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to rooms</a><?php endif; ?>
                    <?php if ($action === 'view'): ?>
                        <a class="room-action-button is-edit" href="room.php?id=<?php echo $roomId; ?>&amp;action=edit<?php echo $embedded ? '&amp;popup=1' : ''; ?>"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Edit room</a>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($successMessage !== ''): ?>
                <p class="room-management-notice is-success" role="status"><?php echo $escape($successMessage); ?></p>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?>
                <p class="room-management-notice is-error" role="alert"><?php echo $escape($errorMessage); ?></p>
            <?php endif; ?>

            <?php if ($action === 'edit'): ?>
                <section class="admin-room-detail-card">
                    <form class="admin-room-edit-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="room_id" value="<?php echo $roomId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_room_csrf']); ?>">
                        <?php if ($imageUrl !== ''): ?>
                            <div class="admin-room-edit-image">
                                <img src="<?php echo $escape($imageUrl); ?>" alt="Current photo of <?php echo $escape($room['room_name']); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="admin-form-field">
                            <label for="room_name">Room name</label>
                            <input id="room_name" name="room_name" maxlength="150" value="<?php echo $escape($room['room_name']); ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="price">Price per night</label>
                            <input id="price" name="price" type="number" min="0" step="0.01" value="<?php echo $escape((string) $room['price']); ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="size">Area</label>
                            <input id="size" name="size" maxlength="50" value="<?php echo $escape($room['size']); ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="beds">Bed configuration</label>
                            <input id="beds" name="beds" maxlength="100" value="<?php echo $escape($room['beds']); ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="capacity">Total capacity</label>
                            <input id="capacity" name="capacity" type="number" min="1" value="<?php echo (int) $room['capacity']; ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="adult">Adults</label>
                            <input id="adult" name="adult" type="number" min="1" value="<?php echo (int) $room['adult']; ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="kids">Kids</label>
                            <input id="kids" name="kids" type="number" min="0" value="<?php echo (int) $room['kids']; ?>" required>
                        </div>
                        <div class="admin-form-field">
                            <label for="quantity">Room quantity</label>
                            <input id="quantity" name="quantity" type="number" min="0" value="<?php echo (int) $room['quantity']; ?>" required>
                        </div>
                        <div class="admin-form-field admin-form-field-wide">
                            <label for="facilities">Facilities</label>
                            <textarea id="facilities" name="facilities" maxlength="5000" rows="4" required><?php echo $escape((string) $room['facilities']); ?></textarea>
                        </div>
                        <div class="admin-form-field admin-form-field-wide">
                            <label for="image">Replace room photo</label>
                            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="admin-form-field">
                            <label>Guest rating</label>
                            <input value="<?php echo (int) $room['review_count'] > 0 ? number_format((float) $room['rating'], 1) . ' (' . (int) $room['review_count'] . ' reviews)' : '0.00'; ?>" readonly>
                        </div>
                        <div class="admin-room-edit-submit">
                            <fieldset class="room-publishing-settings">
                                <legend>Visibility and bookings</legend>
                                <label for="room-visibility">Public listing</label>
                                <select id="room-visibility" name="is_visible">
                                    <option value="1" <?php echo (int) $room['is_visible'] === 1 ? 'selected' : ''; ?>>Visible — show on the website</option>
                                    <option value="0" <?php echo (int) $room['is_visible'] === 0 ? 'selected' : ''; ?>>Hidden — hide from the website</option>
                                </select>
                                <label for="room-bookings">New bookings</label>
                                <select id="room-bookings" name="is_open">
                                    <option value="1" <?php echo (int) $room['is_open'] === 1 ? 'selected' : ''; ?>>Open — accept new bookings</option>
                                    <option value="0" <?php echo (int) $room['is_open'] === 0 ? 'selected' : ''; ?>>Closed — stop new bookings</option>
                                </select>
                                <small>Existing reservations stay in place when you hide a room or close bookings.</small>
                            </fieldset>
                            <button class="addroombtn" type="submit" name="save_room"><i class="fa-solid fa-check" aria-hidden="true"></i> Save room</button>
                        </div>
                    </form>
                </section>
            <?php else: ?>
                <section class="admin-room-detail-card">
                    <?php if ($imageUrl !== ''): ?>
                        <img class="admin-room-detail-image" src="<?php echo $escape($imageUrl); ?>" alt="<?php echo $escape($room['room_name']); ?>">
                    <?php endif; ?>
                    <div class="admin-room-detail-grid">
                        <div><small>Room name</small><strong><?php echo $escape($room['room_name']); ?></strong></div>
                        <div><small>Price per night</small><strong>$<?php echo $escape((string) $room['price']); ?></strong></div>
                        <div><small>Area</small><strong><?php echo $escape($room['size']); ?></strong></div>
                        <div><small>Bed configuration</small><strong><?php echo $escape($room['beds']); ?></strong></div>
                        <div><small>Capacity</small><strong><?php echo (int) $room['capacity']; ?> guests</strong></div>
                        <div><small>Quantity</small><strong><?php echo (int) $room['quantity']; ?></strong></div>
                        <div><small>Visibility</small><strong><?php echo (int) $room['is_visible'] === 1 ? 'Visible' : 'Hidden'; ?></strong></div>
                        <div><small>Bookings</small><strong><?php echo (int) $room['is_open'] === 1 ? 'Open' : 'Closed'; ?></strong></div>
                        <div><small>Guest rating</small><strong><?php echo (int) $room['review_count'] > 0 ? number_format((float) $room['rating'], 1) . ' / 5 (' . (int) $room['review_count'] . ' reviews)' : '0.00'; ?></strong></div>
                        <div class="admin-room-detail-wide"><small>Facilities</small><strong><?php echo nl2br($escape((string) $room['facilities'])); ?></strong></div>
                        <?php if (!empty($room['description'])): ?><div class="admin-room-detail-wide"><small>Description</small><strong><?php echo nl2br($escape((string) $room['description'])); ?></strong></div><?php endif; ?>
                    </div>
                </section>
                <section class="admin-room-detail-card room-bookings-card" aria-labelledby="room-bookings-title">
                    <h2 id="room-bookings-title">Booking details</h2>
                    <p>Active reservations for this room type.</p>
                    <div class="room-bookings-scroll"><table class="room-bookings-table">
                        <thead><tr><th>Booking / guest</th><th>Stay</th><th>Guests</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookingRows as $booking): ?>
                            <tr><td><strong>#<?php echo (int) $booking['booking_id']; ?> · <?php echo $escape((string) $booking['guest']); ?></strong><small><?php echo $escape((string) $booking['phone_number']); ?></small></td><td><?php echo $escape($booking['check_in']); ?><small>to <?php echo $escape($booking['check_out']); ?></small></td><td><?php echo (int) $booking['guests']; ?></td><td>$<?php echo number_format((float) $booking['total_price'], 2); ?></td><td><?php echo $escape(ucwords(str_replace('_', ' ', $booking['status']))); ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$bookingRows): ?><tr><td colspan="5">No active bookings for this room.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                    <h3>Completed stays</h3>
                    <div class="room-bookings-scroll"><table class="room-bookings-table">
                        <thead><tr><th>Booking / guest</th><th>Check-in</th><th>Check-out</th><th>Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($historyRows as $booking): ?>
                            <tr><td>#<?php echo (int) $booking['booking_id']; ?> · <?php echo $escape((string) $booking['guest']); ?></td><td><?php echo $escape($booking['check_in']); ?></td><td><?php echo $escape($booking['check_out']); ?></td><td>$<?php echo number_format((float) $booking['total_price'], 2); ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$historyRows): ?><tr><td colspan="4">No completed stays yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table></div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <?php if (!$embedded) include '../PHP/footer.php'; ?>
    <?php if ($embedded && $successMessage !== ''): ?>
        <script>window.parent.postMessage({ type: 'staysmart-room-saved' }, window.location.origin);</script>
    <?php endif; ?>
    <?php if ($embedded): ?>
        <script>
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') window.parent.postMessage({ type: 'staysmart-room-close' }, window.location.origin);
            });
        </script>
    <?php endif; ?>
</body>

</html>
