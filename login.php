<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
$returnTo = is_string($_GET['next'] ?? null) ? $_GET['next'] : '';
if (preg_match('/^(?:booking-form|room-details)\.php\?id=[1-9][0-9]*$/', $returnTo)) $_SESSION['after_login'] = $returnTo;

if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
    header('Location: ./' . ($_SESSION['after_login'] ?? 'index.php'));
    unset($_SESSION['after_login']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include './PHP/head.php'; ?>
    <link rel="stylesheet" href="./CSS/auth.css">
</head>

<body class="auth-page">
    <?php include './PHP/header.php'; ?>

    <main class="auth-main">
        <section class="auth-shell container" aria-labelledby="login-title">
            <div class="auth-card">
                <div class="auth-content">
                    <a class="auth-back-link" href="./index.php">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to home
                    </a>
                    <p class="auth-eyebrow">Welcome back</p>
                    <h1 id="login-title">Sign in to StaySmart</h1>
                    <p class="auth-intro">Sign in to manage your profile and keep your bookings in one place.</p>

                    <form class="auth-form" action="./PHP/login.php" method="post">
                        <div class="auth-field">
                            <label for="email">Email address</label>
                            <div class="auth-input-wrap">
                                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                                <input type="email" name="email" id="email" placeholder="you@example.com" autocomplete="email" required>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <div class="auth-input-wrap">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required>
                            </div>
                        </div>

                        <button class="auth-submit" type="submit" name="login">
                            Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="auth-switch">New to StaySmart? <a href="./register.php">Create an account</a></p>
                    <p class="auth-help">Need a hand? <a href="./contact.php">Contact us</a></p>
                </div>

                <div class="auth-visual auth-login-visual">
                    <div class="auth-visual-copy">
                        <span class="auth-visual-mark"><i class="fa-solid fa-sun" aria-hidden="true"></i></span>
                        <p>Your next stay starts here</p>
                        <h2>Make yourself at home.</h2>
                        <span>Comfortable rooms. Thoughtful stays.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include './PHP/footer.php'; ?>
