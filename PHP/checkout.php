<?php
require_once __DIR__ . '/room-management.php';

function staySmartCheckoutDraft(mysqli $db, array $input): array
{
    staySmartEnsureRoomManagementColumns($db);
    $value = static fn($key) => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    $roomId = filter_var($input['room_id'] ?? null, FILTER_VALIDATE_INT);
    $guests = filter_var($input['guests'] ?? null, FILTER_VALIDATE_INT);
    $phone = $value('phone_number');
    $notes = $value('mbox');
    if (!$roomId || $roomId < 1 || !$guests || $guests < 1 || $phone === '' || strlen($phone) > 30 || strlen($notes) > 2000) throw new RuntimeException('Check the guest count, phone number and special requests.');
    $in = $value('check_in'); $out = $value('check_out');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $in) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $out)) throw new RuntimeException('Choose valid check-in and check-out dates.');
    $zone = new DateTimeZone('Asia/Dubai');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $in, $zone);
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', $out, $zone);
    if (!$start || !$end || $start->format('Y-m-d') !== $in || $end->format('Y-m-d') !== $out || $end <= $start) throw new RuntimeException('Choose valid dates. Check-out must be after check-in.');
    if ($start < new DateTimeImmutable('today', $zone)) throw new RuntimeException('Check-in cannot be in the past. Choose today or a future date.');
    $q = $db->prepare('SELECT * FROM rooms WHERE id = ? AND is_visible = 1 AND is_open = 1 AND quantity > 0');
    $q->bind_param('i', $roomId); $q->execute(); $room = $q->get_result()->fetch_assoc();
    if (!$room) throw new RuntimeException('This room is no longer accepting reservations. Please choose another room.');
    if ($guests > (int) $room['capacity']) throw new RuntimeException('The guest count exceeds the room capacity.');
    staySmartRequireAvailability($db, $roomId, $in, $out);
    return ['room_id' => $roomId, 'room_name' => $room['room_name'], 'check_in' => $in, 'check_out' => $out, 'guests' => $guests, 'phone_number' => $phone, 'mbox' => $notes, 'price' => (float) $room['price'], 'nights' => $start->diff($end)->days, 'image' => $room['image']];
}
