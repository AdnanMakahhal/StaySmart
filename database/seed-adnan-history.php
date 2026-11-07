<?php
// Run once from the CLI. Every example is explicitly marked as sample data.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../PHP/connect.php';
require __DIR__.'/../PHP/account-management.php';
require __DIR__.'/../PHP/booking-management.php';
require __DIR__.'/../PHP/room-ratings.php';
staySmartEnsureAccounts($con);
staySmartEnsureBookingChanges($con);
staySmartEnsureRoomRatingsTable($con);
$con->query("CREATE TABLE IF NOT EXISTS sample_account_seeds (seed_key VARCHAR(100) PRIMARY KEY,user_id INT UNSIGNED NOT NULL,past_booking_id INT UNSIGNED NOT NULL,upcoming_booking_id INT UNSIGNED NOT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB");
$key='adnan-history-v1';
$con->begin_transaction();
try {
    $email='adnan.pls2003@gmail.com';
    $q=$con->prepare('SELECT id,username FROM users WHERE email=? FOR UPDATE');$q->bind_param('s',$email);$q->execute();$user=$q->get_result()->fetch_assoc();
    if(!$user)throw new RuntimeException('The requested account was not found.');
    $userId=(int)$user['id'];
    $q=$con->prepare('SELECT * FROM sample_account_seeds WHERE seed_key=?');$q->bind_param('s',$key);$q->execute();$existing=$q->get_result()->fetch_assoc();
    if($existing){$con->commit();echo json_encode(['already_added'=>true]+$existing,JSON_PRETTY_PRINT),PHP_EOL;exit;}
    $room=$con->query("SELECT * FROM rooms ORDER BY (room_name='Desert Panorama Suite') DESC,id LIMIT 1")->fetch_assoc();
    if(!$room)throw new RuntimeException('Add a room before creating sample bookings.');
    $roomId=(int)$room['id'];$price=(float)$room['price'];$roomName=$room['room_name'];
    $zone=new DateTimeZone('Asia/Dubai');$today=new DateTimeImmutable('today',$zone);$ids=[];
    $extra=$con->query("SHOW COLUMNS FROM bookings LIKE 'total_price'")->fetch_assoc()['Extra'];
    foreach(['past','upcoming'] as $kind){
        $start=$today->modify($kind==='past'?'-20 days':'+10 days');$end=$start->modify($kind==='past'?'+3 days':'+2 days');
        $in=$start->format('Y-m-d');$out=$end->format('Y-m-d');$date=$today->modify('-30 days')->format('Y-m-d');$booked=$today->modify('-30 days')->setTime(9,15)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $notes='Sample booking for testing account history and reviews. This is not a real reservation.';
        $q=$con->prepare("INSERT INTO bookings(user_id,room_id,user_name,room_name,check_in,check_out,guests,phone_number,message,booking_date,price,booked_at,is_sample) VALUES (?, ?, ?, ?, ?, ?, 1, '', ?, ?, ?, ?, 1)");
        $q->bind_param('iissssssds',$userId,$roomId,$user['username'],$roomName,$in,$out,$notes,$date,$price,$booked);$q->execute();$id=(int)$con->insert_id;$ids[$kind]=$id;
        $total=round($price*$start->diff($end)->days,2);
        if(stripos($extra,'GENERATED')===false){$q=$con->prepare('UPDATE bookings SET total_price=? WHERE booking_id=?');$q->bind_param('di',$total,$id);$q->execute();}
        if($kind==='past'){
            $con->query("INSERT INTO archive_bookings(booking_id,user_id,room_id,room_name,check_in,check_out,total_price,booked_at,is_sample) SELECT booking_id,user_id,room_id,room_name,check_in,check_out,total_price,booked_at,is_sample FROM bookings WHERE booking_id=$id");
            $con->query("DELETE FROM bookings WHERE booking_id=$id");
        } else {
            $q=$con->prepare("INSERT INTO booking_payments(booking_id,user_id,amount,created_at) VALUES(?,?,?,UTC_TIMESTAMP())");$q->bind_param('iid',$id,$userId,$total);$q->execute();
        }
    }
    $pastId=$ids['past'];$upcomingId=$ids['upcoming'];
    $reviewText='Sample review added to preview the reviews feature. This is example content, not a real guest testimonial.';
    $q=$con->prepare('INSERT INTO room_reviews(booking_id,user_id,room_id,rating,review_text,is_sample,created_at) VALUES(?,?,?,5,?,1,UTC_TIMESTAMP())');$q->bind_param('iiis',$pastId,$userId,$roomId,$reviewText);$q->execute();
    $type='user';$activityId=$userId;
    $link=$con->query("SELECT admin_id FROM account_links WHERE user_id=$userId AND current_type='admin'")->fetch_assoc();
    if($link){$type='admin';$activityId=(int)$link['admin_id'];}
    $path='/StaySmart/index.php';
    foreach([14,7,2]as$days){$time=$today->modify('-'.$days.' days')->setTime(10,30)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$q=$con->prepare('INSERT INTO account_visits(account_type,account_id,page_path,visited_at,is_sample) VALUES(?,?,?,?,1)');$q->bind_param('siss',$type,$activityId,$path,$time);$q->execute();}
    staySmartAccountEvent($con,$type,$activityId,'sample_history_added');
    $q=$con->prepare('INSERT INTO sample_account_seeds(seed_key,user_id,past_booking_id,upcoming_booking_id,created_at) VALUES(?,?,?,?,UTC_TIMESTAMP())');$q->bind_param('siii',$key,$userId,$pastId,$upcomingId);$q->execute();
    $con->commit();
    echo json_encode(['added'=>true,'user_id'=>$userId,'past_booking_id'=>$pastId,'upcoming_booking_id'=>$upcomingId,'sample_reviews'=>1,'sample_visits'=>3],JSON_PRETTY_PRINT),PHP_EOL;
} catch(Throwable $error){$con->rollback();fwrite(STDERR,$error->getMessage().PHP_EOL);exit(1);}
