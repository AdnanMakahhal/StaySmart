<?php
session_start();require_once __DIR__.'/PHP/auth.php';require_once __DIR__.'/PHP/booking-identity.php';
$reviewUserId=staySmartBookingUserId($con);if($reviewUserId===null){header('Location: login.php');exit;}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Your reviews | StaySmart</title><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/footer.css"><link rel="stylesheet" href="CSS/stay.css"><link rel="stylesheet" href="CSS/reviews.css"></head><body class="stay-page"><?php include 'PHP/header.php'; ?><main class="stay-main"><nav class="stay-breadcrumb"><a href="mybooking.php">&larr; My bookings</a><span>/</span><span>Your reviews</span></nav><?php include 'PHP/my-reviews.php'; ?></main><?php include 'PHP/footer.php'; ?>
