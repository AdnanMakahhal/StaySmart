<?php
session_start();
require_once __DIR__.'/auth.php';
require_once __DIR__.'/booking-identity.php';
require_once __DIR__.'/room-ratings.php';
staySmartEnsureRoomRatingsTable($con);
$userId=staySmartBookingUserId($con);
if($userId===null){http_response_code(403);exit('Sign in to manage your reviews.');}
$target=isset($_SESSION['admin_id'])?'../reviews.php':'../profile.php#my-reviews';
try{
    $token=$_POST['csrf_token']??'';$id=filter_var($_POST['booking_id']??null,FILTER_VALIDATE_INT);
    if($_SERVER['REQUEST_METHOD']!=='POST'||!is_string($token)||empty($_SESSION['delete_review_csrf'])||!hash_equals($_SESSION['delete_review_csrf'],$token))throw new RuntimeException('Your session expired. Refresh your reviews and try again.');
    if(!$id||$id<1)throw new RuntimeException('Choose a valid review.');
    $con->begin_transaction();
    $q=$con->prepare('SELECT room_id FROM room_reviews WHERE booking_id=? AND user_id=?');$q->bind_param('ii',$id,$userId);$q->execute();$review=$q->get_result()->fetch_assoc();
    if(!$review)throw new RuntimeException('This review is unavailable or does not belong to your account.');
    $roomId=(int)$review['room_id'];
    $q=$con->prepare('SELECT id FROM rooms WHERE id=? FOR UPDATE');$q->bind_param('i',$roomId);$q->execute();$q->get_result()->fetch_assoc();
    $q=$con->prepare('DELETE FROM room_reviews WHERE booking_id=? AND user_id=?');$q->bind_param('ii',$id,$userId);$q->execute();
    $q=$con->prepare('UPDATE rooms SET rating=(SELECT COALESCE(AVG(rating),0) FROM room_reviews WHERE room_id=? AND is_sample=0) WHERE id=?');$q->bind_param('ii',$roomId,$roomId);$q->execute();
    staySmartBookingEvent($con,$userId,'deleted review #'.$id);
    $con->commit();
    $_SESSION['flash_alert']=['type'=>'success','title'=>'Review deleted','message'=>'Your room rating and review have been removed.'];
}catch(mysqli_sql_exception $e){$con->rollback();error_log('Review deletion: '.$e->getMessage());$_SESSION['flash_alert']=['type'=>'error','title'=>'Review unchanged','message'=>'Please refresh and try again.'];}
catch(RuntimeException $e){$con->rollback();$_SESSION['flash_alert']=['type'=>'error','title'=>'Review unchanged','message'=>$e->getMessage()];}
header('Location: '.$target);exit;
