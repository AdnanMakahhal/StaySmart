<?php
if (!isset($con) || !($con instanceof mysqli)) { http_response_code(404); exit; }
require_once __DIR__ . '/room-image.php';
require_once __DIR__ . '/room-management.php';
require_once __DIR__ . '/room-ratings.php';
staySmartEnsureRoomManagementColumns($con);
staySmartEnsureRoomRatingsTable($con);
$roomIdInput = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$roomNameInput = is_string($_GET['roomname'] ?? null) ? $_GET['roomname'] : '';
if ($roomIdInput && $roomIdInput > 0) {
    $roomQuery = $con->prepare('SELECT rooms.*, (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count FROM rooms WHERE id = ? AND is_visible = 1 LIMIT 1');
    $roomQuery->bind_param('i', $roomIdInput);
} else {
    $roomQuery = $con->prepare('SELECT rooms.*, (SELECT COUNT(*) FROM room_reviews WHERE is_sample = 0 AND room_id = rooms.id) AS review_count FROM rooms WHERE room_name = ? AND is_visible = 1 LIMIT 1');
    $roomQuery->bind_param('s', $roomNameInput);
}
$roomQuery->execute();
$room = $roomQuery->get_result()->fetch_assoc();
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
if (!$room) http_response_code(404);
$canBook = $room && (int) $room['is_open'] === 1 && (int) $room['quantity'] > 0;
$roomPhoto = $room ? staySmartRoomImage($room['image']) : '';
$hasPhoto = $room && $room['image'] !== '' && is_file(dirname(__DIR__) . '/Assets/our rooms/' . $roomPhoto);
