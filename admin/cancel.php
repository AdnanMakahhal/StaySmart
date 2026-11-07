
<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
    
    if (isset($_POST['cancel_booking'])) {
        
        $room_id = $_POST['room_id'];

        include '../PHP/connect.php';

        $sql = "DELETE FROM rooms WHERE id = $room_id";
        if (mysqli_query($con, $sql)) {
            header('Location: addrooms.php');
        } else {
            echo "error";
        }

    }
