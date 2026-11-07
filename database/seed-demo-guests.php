<?php
// Fictional demo accounts and histories. CLI only; safe to rerun without duplicates.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../PHP/connect.php';
require __DIR__.'/../PHP/account-management.php';
require __DIR__.'/../PHP/booking-management.php';
require __DIR__.'/../PHP/room-ratings.php';
staySmartEnsureAccounts($con);staySmartEnsureBookingChanges($con);staySmartEnsureRoomRatingsTable($con);
$con->query("CREATE TABLE IF NOT EXISTS demo_guest_profiles(user_id INT UNSIGNED PRIMARY KEY,city VARCHAR(100) NOT NULL,country VARCHAR(100) NOT NULL,statement VARCHAR(500) NOT NULL,portrait_source VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$con->query("CREATE TABLE IF NOT EXISTS demo_guest_batches(batch_key VARCHAR(100) PRIMARY KEY,accounts_json TEXT NOT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$guests=[
 ['key'=>'maya','name'=>'Maya Rahman','username'=>'maya_rahman_demo','email'=>'maya.rahman@staysmart.example','city'=>'Dubai','country'=>'United Arab Emirates','statement'=>'Weekend escapes, quiet gardens and a good cup of coffee. Fictional demo profile.','review'=>'The garden outlook made this room feel peaceful from the moment I arrived. I loved the soft lighting and the little seating area.','rating'=>5],
 ['key'=>'omar','name'=>'Omar Haddad','username'=>'omar_haddad_demo','email'=>'omar.haddad@staysmart.example','city'=>'Abu Dhabi','country'=>'United Arab Emirates','statement'=>'City breaks, contemporary spaces and discovering a new view. Fictional demo profile.','review'=>'A comfortable base for a city break. The king bed and large windows were my favorite parts, and having a desk made catching up on work easy.','rating'=>4],
 ['key'=>'sophia','name'=>'Sophia Bennett','username'=>'sophia_bennett_demo','email'=>'sophia.bennett@staysmart.example','city'=>'London','country'=>'United Kingdom','statement'=>'Slow travel, natural textures and somewhere lovely to unwind. Fictional demo profile.','review'=>'The warm wood finishes and relaxed atmosphere made this suite a lovely place to unwind. Plenty of space, thoughtful details, and a very comfortable stay.','rating'=>5],
];
$key='demo-guests-v1';$con->begin_transaction();
function staySmartSaveDemoPortraits(array $accounts):void{
    foreach($accounts as$account){$target=dirname(__DIR__).'/Assets/profile-photos/user-'.(int)$account['user_id'].'.png';$source=dirname(__DIR__).'/'.$account['portrait_source'];if(!is_file($target)&&is_file($source)&&!copy($source,$target))throw new RuntimeException('Could not save a demo avatar.');}
}
try{
    $q=$con->prepare('SELECT accounts_json FROM demo_guest_batches WHERE batch_key=? FOR UPDATE');$q->bind_param('s',$key);$q->execute();$existing=$q->get_result()->fetch_assoc();
    if($existing){$con->commit();$accounts=json_decode($existing['accounts_json'],true);staySmartSaveDemoPortraits($accounts);echo json_encode($accounts,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;exit;}
    $rooms=$con->query('SELECT * FROM rooms WHERE is_visible=1 ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    if(count($rooms)<3)throw new RuntimeException('At least three rooms are required.');
    $generated=$con->query("SHOW COLUMNS FROM bookings LIKE 'total_price'")->fetch_assoc()['Extra'];
    $today=new DateTimeImmutable('today',new DateTimeZone('Asia/Dubai'));$now=gmdate('Y-m-d H:i:s');$output=[];
    foreach($guests as $index=>$guest){
        foreach(['users','admin']as$table){$q=$con->prepare("SELECT id FROM `$table` WHERE username=? OR email=? LIMIT 1");$q->bind_param('ss',$guest['username'],$guest['email']);$q->execute();if($q->get_result()->fetch_assoc())throw new RuntimeException('Demo email or username already exists; no existing account was changed.');}
        $guestPassword=bin2hex(random_bytes(18));$hash=password_hash($guestPassword,PASSWORD_DEFAULT);$q=$con->prepare('INSERT INTO users(username,full_name,email,password,created_at) VALUES(?,?,?,?,?)');$q->bind_param('sssss',$guest['username'],$guest['name'],$guest['email'],$hash,$now);$q->execute();$uid=(int)$con->insert_id;
        $source='Assets/demo-profiles/'.$guest['key'].'.png';if(!is_file(dirname(__DIR__).'/'.$source))throw new RuntimeException('Missing demo portrait: '.$source);
        $q=$con->prepare('INSERT INTO demo_guest_profiles(user_id,city,country,statement,portrait_source) VALUES(?,?,?,?,?)');$q->bind_param('issss',$uid,$guest['city'],$guest['country'],$guest['statement'],$source);$q->execute();
        $latestPastId=null;$latestRoomId=null;
        foreach(range(0,3)as$stay){
            $room=$rooms[($index+$stay)%count($rooms)];
            if($stay===2){$preferred=['Tropical Garden Deluxe Room','City Skyline King Room','Modern Executive Suite'][$index];foreach($rooms as$candidate)if($candidate['room_name']===$preferred)$room=$candidate;}
            $roomId=(int)$room['id'];$price=(float)$room['price'];$roomName=$room['room_name'];
            $start=$today->modify($stay===3?'+'.(12+$index*3).' days':'-'.(65-$stay*20+$index).' days');$end=$start->modify('+'.(2+$stay%2).' days');$in=$start->format('Y-m-d');$out=$end->format('Y-m-d');$date=$start->modify('-10 days')->format('Y-m-d');$booked=$start->modify('-10 days')->setTime(12,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$total=round($price*$start->diff($end)->days,2);$notes='Demo reservation for a fictional account; no real room inventory is reserved.';
            $q=$con->prepare("INSERT INTO bookings(user_id,room_id,user_name,room_name,check_in,check_out,guests,phone_number,message,booking_date,price,booked_at,is_sample) VALUES(?,?,?,?,?,?,1,'',?,?,?,?,1)");$q->bind_param('iissssssds',$uid,$roomId,$guest['username'],$roomName,$in,$out,$notes,$date,$price,$booked);$q->execute();$bid=(int)$con->insert_id;
            if(stripos($generated,'GENERATED')===false){$q=$con->prepare('UPDATE bookings SET total_price=? WHERE booking_id=?');$q->bind_param('di',$total,$bid);$q->execute();}
            if($stay<3){$con->query("INSERT INTO archive_bookings(booking_id,user_id,room_id,room_name,check_in,check_out,total_price,booked_at,is_sample) SELECT booking_id,user_id,room_id,room_name,check_in,check_out,total_price,booked_at,1 FROM bookings WHERE booking_id=$bid");$con->query("DELETE FROM bookings WHERE booking_id=$bid");$latestPastId=$bid;$latestRoomId=$roomId;}
            else{$q=$con->prepare("INSERT INTO booking_payments(booking_id,user_id,amount,created_at) VALUES(?,?,?,UTC_TIMESTAMP())");$q->bind_param('iid',$bid,$uid,$total);$q->execute();}
        }
        $q=$con->prepare('INSERT INTO room_reviews(booking_id,user_id,room_id,rating,review_text,is_sample,created_at) VALUES(?,?,?,?,?,1,UTC_TIMESTAMP())');$q->bind_param('iiiis',$latestPastId,$uid,$latestRoomId,$guest['rating'],$guest['review']);$q->execute();
        foreach([9,5,2]as$days){$time=$today->modify('-'.$days.' days')->setTime(10+$index,20)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$path='/StaySmart/rooms.php';$type='user';$q=$con->prepare('INSERT INTO account_visits(account_type,account_id,page_path,visited_at,is_sample) VALUES(?,?,?,?,1)');$q->bind_param('siss',$type,$uid,$path,$time);$q->execute();}
        staySmartAccountEvent($con,'user',$uid,'demo_account_created');
        $output[]=$guest+['password'=>$guestPassword,'user_id'=>$uid,'bookings'=>4,'completed'=>3,'upcoming'=>1,'reviews'=>1,'portrait_source'=>$source];
    }
    $metadata=$output;foreach($metadata as&$record)unset($record['password']);unset($record);
    $json=json_encode($metadata,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);$q=$con->prepare('INSERT INTO demo_guest_batches(batch_key,accounts_json,created_at) VALUES(?,?,UTC_TIMESTAMP())');$q->bind_param('ss',$key,$json);$q->execute();$con->commit();staySmartSaveDemoPortraits($output);echo json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;
}catch(Throwable$e){$con->rollback();fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
