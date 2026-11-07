<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Administrator sign-in is required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addrooms.php');
    exit;
}

require_once '../PHP/connect.php';
require_once '../PHP/room-management.php';
staySmartEnsureRoomManagementColumns($con);

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['admin_room_csrf'] ?? '';
$action = $_POST['action'] ?? '';
$roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
$message = '';
$messageType = 'error';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    $message = 'Your session expired. Reload the room page and try again.';
} elseif (!in_array($action, ['hide', 'show', 'close', 'open', 'delete'], true) || $roomId === false || $roomId < 1) {
    $message = 'The room action was invalid.';
} else {
    try {
        $con->begin_transaction();
        $roomQuery = $con->prepare('SELECT id FROM rooms WHERE id = ? FOR UPDATE');
        $roomQuery->bind_param('i', $roomId);
        $roomQuery->execute();
        $roomExists = $roomQuery->get_result()->fetch_assoc();
        $roomQuery->close();

        if (!$roomExists) {
            $con->rollback();
            $message = 'That room could not be found.';
        } elseif ($action === 'delete') {
            $references = $con->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM bookings WHERE room_id = ?) +
                    (SELECT COUNT(*) FROM archive_bookings WHERE room_id = ?) +
                    (SELECT COUNT(*) FROM room_reviews WHERE room_id = ?) AS reference_count'
            );
            $references->bind_param('iii', $roomId, $roomId, $roomId);
            $references->execute();
            $referenceCount = (int) $references->get_result()->fetch_assoc()['reference_count'];
            $references->close();

            if ($referenceCount > 0) {
                $con->rollback();
                $message = 'This room has bookings or reviews, so it cannot be deleted. Hide it instead.';
            } else {
                $deleteRoom = $con->prepare('DELETE FROM rooms WHERE id = ?');
                $deleteRoom->bind_param('i', $roomId);
                $deleteRoom->execute();
                $deleteRoom->close();
                $con->commit();
                $message = 'Room deleted.';
                $messageType = 'success';
            }
        } else {
            $column = in_array($action, ['hide', 'show'], true) ? 'is_visible' : 'is_open';
            $value = in_array($action, ['show', 'open'], true) ? 1 : 0;
            $updateRoom = $con->prepare("UPDATE rooms SET `$column` = ? WHERE id = ?");
            $updateRoom->bind_param('ii', $value, $roomId);
            $updateRoom->execute();
            $updateRoom->close();
            $con->commit();
            $messages = [
                'hide' => 'Room hidden from public listings.',
                'show' => 'Room is visible on public listings.',
                'close' => 'New bookings are closed for this room.',
                'open' => 'New bookings are open for this room.',
            ];
            $message = $messages[$action];
            $messageType = 'success';
        }
    } catch (mysqli_sql_exception $exception) {
        $con->rollback();
        error_log('StaySmart room action failed: ' . $exception->getMessage());
        $message = 'The room could not be updated. Please try again.';
    }
}

$_SESSION['admin_room_message'] = $message;
$_SESSION['admin_room_message_type'] = $messageType;
header('Location: addrooms.php');
exit;
