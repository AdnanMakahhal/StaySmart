<?php
session_start();
require_once __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }
$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
foreach (['admin', 'user'] as $type) {
    $table = staySmartAccountTable($type);
    $linkColumn = $type === 'admin' ? 'admin_id' : 'user_id';
    $stmt = $con->prepare("SELECT *, password AS login_password FROM `$table` WHERE email = ? AND NOT EXISTS (SELECT 1 FROM account_links WHERE `$linkColumn` = `$table`.id AND current_type <> '$type') LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    if (!$account) continue;
    $stored = (string) $account['login_password'];
    $isHash = !empty(password_get_info($stored)['algo']);
    $valid = $isHash ? password_verify($password, $stored) : hash_equals($stored, $password);
    if (!$valid) continue;
    if ((int) $account['is_blocked'] === 1 || $account['deleted_at'] !== null) break;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now = gmdate('Y-m-d H:i:s');
    $update = $con->prepare("UPDATE `$table` SET password = ?, last_login_at = ? WHERE id = ? AND is_blocked = 0 AND deleted_at IS NULL");
    $update->bind_param('ssi', $hash, $now, $account['id']);
    $update->execute();
    if ($update->affected_rows !== 1) break;
    session_regenerate_id(true);
    $returnTo = $_SESSION['after_login'] ?? '';
    $_SESSION = [];
    $_SESSION[$type === 'admin' ? 'admin_id' : 'user_id'] = (int) $account['id'];
    $_SESSION[$type === 'admin' ? 'admin_name' : 'user_name'] = $account['username'];
    if ($type === 'admin') $_SESSION['admin_role'] = $account['role'];
    staySmartAccountEvent($con, $type, (int) $account['id'], 'login');
    $safeReturn = is_string($returnTo) && preg_match('/^(?:booking-form|room-details)\.php\?id=[1-9][0-9]*$/', $returnTo);
    header('Location: ../' . ($safeReturn ? $returnTo : 'index.php'));
    exit;
}
$_SESSION['flash_alert'] = ['type' => 'error', 'title' => 'Could not sign in', 'message' => 'Check your email and password, or contact the team if your account is disabled.'];
header('Location: ../login.php');
exit;
