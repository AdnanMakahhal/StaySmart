<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../PHP/connect.php';

$username = getenv('STAYSMART_ADMIN_USERNAME');
$fullName = getenv('STAYSMART_ADMIN_FULL_NAME');
$email = getenv('STAYSMART_ADMIN_EMAIL');
$password = getenv('STAYSMART_ADMIN_PASSWORD');

if (
    !is_string($username) || trim($username) === '' ||
    !is_string($fullName) || trim($fullName) === '' ||
    !is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
    !is_string($password) || strlen($password) < 14
) {
    fwrite(STDERR, "Set STAYSMART_ADMIN_USERNAME, STAYSMART_ADMIN_FULL_NAME, STAYSMART_ADMIN_EMAIL, and a STAYSMART_ADMIN_PASSWORD of at least 14 characters.\n");
    exit(1);
}

$existingAdmins = $con->query('SELECT COUNT(*) AS admin_count FROM admin')->fetch_assoc();
if ((int) $existingAdmins['admin_count'] > 0) {
    fwrite(STDERR, "An administrator account already exists; no account was created.\n");
    exit(1);
}

$role = 'admin';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$createdAt = gmdate('Y-m-d H:i:s');
$query = $con->prepare('INSERT INTO admin (username, full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$query->bind_param('ssssss', $username, $fullName, $email, $passwordHash, $role, $createdAt);
$query->execute();

fwrite(STDOUT, "Administrator account created. Sign in with the username and password you provided.\n");
