<?php
session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/booking-identity.php';
$bookingUserId = staySmartBookingUserId($con);

if ($bookingUserId === null) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../mybooking.php');
    exit;
}

require_once 'connect.php';
require_once 'room-ratings.php';
staySmartEnsureRoomRatingsTable($con);

$userId = $bookingUserId;
$bookingIdInput = $_POST['booking_id'] ?? null;
$ratingInput = $_POST['rating'] ?? null;
$bookingId = is_string($bookingIdInput) || is_int($bookingIdInput)
    ? filter_var((string) $bookingIdInput, FILTER_VALIDATE_INT)
    : false;
$rating = is_string($ratingInput) || is_int($ratingInput)
    ? filter_var((string) $ratingInput, FILTER_VALIDATE_INT)
    : false;
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['room_rating_csrf'] ?? '';
$message = '';
$messageType = 'error';
$reviewText = is_string($_POST['review_text'] ?? null) ? trim($_POST['review_text']) : '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    $message = 'Your session expired. Refresh your bookings and try again.';
} elseif ($bookingId === false || $bookingId < 1 || $rating === false || $rating < 1 || $rating > 5) {
    $message = 'Choose a rating from 1 to 5 stars.';
} elseif (preg_match_all('/./us', $reviewText) === false || preg_match_all('/./us', $reviewText) > 1000) {
    $message = 'Keep your review under 1,000 characters.';
} else {
    try {
        $con->begin_transaction();
        $bookingQuery = $con->prepare(
            'SELECT room_id, is_sample FROM archive_bookings
            WHERE booking_id = ? AND user_id = ? AND check_out <= ?
            FOR UPDATE'
        );
        $today = (new DateTimeImmutable('today', new DateTimeZone('Asia/Dubai')))->format('Y-m-d');
        $bookingQuery->bind_param('iis', $bookingId, $userId, $today);
        $bookingQuery->execute();
        $completedBooking = $bookingQuery->get_result()->fetch_assoc();
        $bookingQuery->close();

        if (!$completedBooking) {
            $con->rollback();
            $message = 'You can rate a stay after its checkout date.';
        } else {
            $roomId = (int) $completedBooking['room_id'];
            $roomLock = $con->prepare('SELECT id FROM rooms WHERE id = ? FOR UPDATE');
            $roomLock->bind_param('i', $roomId);
            $roomLock->execute();
            $roomLock->get_result()->fetch_assoc();
            $review = $con->prepare(
                'INSERT INTO room_reviews (booking_id, user_id, room_id, rating, review_text, created_at, is_sample)
                VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)'
            );
            $sample = (int)$completedBooking['is_sample'];
            $review->bind_param('iiiisi', $bookingId, $userId, $roomId, $rating, $reviewText, $sample);
            $review->execute();
            $review->close();

            $updateRoomRating = $con->prepare(
                'UPDATE rooms
                SET rating = (
                    SELECT COALESCE(AVG(rating), 0)
                    FROM room_reviews
                    WHERE room_id = ? AND is_sample = 0
                )
                WHERE id = ?'
            );
            $updateRoomRating->bind_param('ii', $roomId, $roomId);
            $updateRoomRating->execute();
            $updateRoomRating->close();

            $con->commit();
            $message = 'Thanks for rating your stay!';
            $messageType = 'success';
        }
    } catch (mysqli_sql_exception $exception) {
        $con->rollback();
        if ((int) $exception->getCode() === 1062) {
            $message = 'You have already rated this stay.';
        } else {
            error_log('StaySmart room rating save failed: ' . $exception->getMessage());
            $message = 'Your rating could not be saved. Please try again.';
        }
    }
}

$_SESSION['flash_alert'] = ['type' => $messageType, 'title' => $messageType === 'success' ? 'Thanks for your rating' : 'Rating not saved', 'message' => $message];
header('Location: ../mybooking.php');
exit;
