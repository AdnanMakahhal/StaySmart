<?php
session_start();
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/account-management.php';
staySmartEnsureAccounts($con);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../register.php'); exit; }
$value = static fn($key) => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
$username = $value('username');
$fullName = $value('full_name');
$email = $value('email');
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$message = 'User registered successfully.';
$target = '../login.php';
try {
    if ($username === '' || strlen($username) > 100 || $fullName === '' || strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || strlen($password) < 10 || strlen($password) > 72) {
        throw new RuntimeException('Enter valid account details and a password of 10 to 72 characters.');
    }
    foreach (['users', 'admin'] as $table) {
        $check = $con->prepare("SELECT id FROM `$table` WHERE email = ? OR username = ? LIMIT 1");
        $check->bind_param('ss', $email, $username);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) throw new RuntimeException('Email or username already exists.');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now = gmdate('Y-m-d H:i:s');
    $stmt = $con->prepare('INSERT INTO users (username, full_name, email, password, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $username, $fullName, $email, $hash, $now);
    $stmt->execute();
    staySmartAccountEvent($con, 'user', (int) $con->insert_id, 'registered');
} catch (mysqli_sql_exception $error) {
    $message = 'Account could not be created. Check whether the email or username is already used.';
    $target = '../register.php';
} catch (RuntimeException $error) {
    $message = $error->getMessage();
    $target = '../register.php';
}
$_SESSION['flash_alert'] = ['type' => $target === '../login.php' ? 'success' : 'error', 'title' => $target === '../login.php' ? 'Account created' : 'Check your details', 'message' => $message];
header('Location: ' . $target);
exit;
