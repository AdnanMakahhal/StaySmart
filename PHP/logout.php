
<?php

    session_start();
    require_once __DIR__ . '/account-management.php';
    require_once __DIR__ . '/connect.php';
    staySmartEnsureAccounts($con);
    if (isset($_SESSION['admin_id'])) staySmartAccountEvent($con, 'admin', (int) $_SESSION['admin_id'], 'logout');
    elseif (isset($_SESSION['user_id'])) staySmartAccountEvent($con, 'user', (int) $_SESSION['user_id'], 'logout');
    $_SESSION = [];
    session_destroy();
    header('location: ../index.php');
