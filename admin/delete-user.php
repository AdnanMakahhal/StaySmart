<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
// Account deletion is handled by the permission-checked Manage Users form.
header('Location: users.php');
exit;
