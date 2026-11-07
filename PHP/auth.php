<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/account-management.php';
staySmartEnsureAccounts($con);
require_once __DIR__.'/data-flags.php';
staySmartEnsureSampleFlags($con);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$accountType = isset($_SESSION['admin_id']) ? 'admin' : (isset($_SESSION['user_id']) ? 'user' : null);
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAdminRequest = strpos($scriptPath, '/admin/') !== false;
if ($accountType !== null) {
    $accountId = (int) $_SESSION[$accountType === 'admin' ? 'admin_id' : 'user_id'];
    $table = staySmartAccountTable($accountType);
    $accountQuery = $con->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $accountQuery->bind_param('i', $accountId);
    $accountQuery->execute();
    $signedInAccount = $accountQuery->get_result()->fetch_assoc();
    if (!$signedInAccount || (int) $signedInAccount['is_blocked'] === 1 || $signedInAccount['deleted_at'] !== null) {
        $_SESSION = [];
        session_destroy();
        http_response_code(403);
        exit('This account is blocked or no longer active. Please contact the StaySmart team.');
    }
    if ($accountType === 'admin') {
        $_SESSION['admin_role'] = $signedInAccount['role'];
        $_SESSION['admin_name'] = $signedInAccount['username'];
    } else {
        $_SESSION['user_name'] = $signedInAccount['username'];
    }
    // Record only first-party page views, without query strings or browsing outside StaySmart.
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'
        && !in_array(basename($scriptPath), ['logout.php', 'login.php', 'register.php', 'room-action.php', 'cancel.php', 'update_status.php', 'delete-user.php'], true)) {
        staySmartRecordVisit($con, $accountType, $accountId, substr($scriptPath, 0, 255));
    }
}
if ($isAdminRequest && $accountType !== 'admin') {
    http_response_code(403);
    exit('Staff sign-in is required.');
}
