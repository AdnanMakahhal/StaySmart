<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';

if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
    header('Location: ./index.php');
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
        <section class="auth-shell container" aria-labelledby="register-title">
            <div class="auth-card auth-card-register">
                <div class="auth-content">
                    <a class="auth-back-link" href="./index.php">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to home
                    </a>
                    <p class="auth-eyebrow">Join StaySmart</p>
                    <h1 id="register-title">Create your account</h1>
                    <p class="auth-intro">Create an account to make booking and managing your stays easier.</p>

                    <form class="auth-form" action="./PHP/register.php" method="post">
                        <div class="auth-field">
                            <label for="username">Username</label>
                            <div class="auth-input-wrap">
                                <i class="fa-regular fa-user" aria-hidden="true"></i>
                                <input type="text" name="username" id="username" placeholder="Choose a username" autocomplete="username" required>
                            </div>
                        </div>

                        <div class="auth-field">
                            <label for="full_name">Full name</label>
                            <div class="auth-input-wrap">
                                <i class="fa-regular fa-id-card" aria-hidden="true"></i>
                                <input type="text" name="full_name" id="full_name" placeholder="Enter your full name" autocomplete="name" maxlength="150" required>
                            </div>
                        </div>

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
                                <input type="password" name="password" id="password" placeholder="Create a password (10–72 characters)" autocomplete="new-password" minlength="10" maxlength="72" required>
                            </div>
                        </div>

                        <button class="auth-submit" type="submit" name="register">
                            Create account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="auth-switch">Already have an account? <a href="./login.php">Sign in</a></p>
                    <p class="auth-help">Need a hand? <a href="./contact.php">Contact us</a></p>
                </div>

                <div class="auth-visual auth-register-visual">
                    <div class="auth-visual-copy">
                        <span class="auth-visual-mark"><i class="fa-solid fa-heart" aria-hidden="true"></i></span>
                        <p>A warm welcome awaits</p>
                        <h2>Find a stay that feels right.</h2>
                        <span>Start planning your next favorite trip.</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include './PHP/footer.php'; ?>
