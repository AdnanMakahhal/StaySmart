<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}

include '../PHP/connect.php';
$adminId = (int) $_SESSION['admin_id'];

if (isset($_POST['update'])) {
    $username = trim($_POST['admin_name'] ?? '');
    $fullName = trim($_POST['admin_full_name'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');

    if ($username === '' || $fullName === '' || $email === '') {
        $_SESSION['admin_profile_error'] = 'Please complete all profile fields.';
    } else {
        $update = $con->prepare("UPDATE admin SET username = ?, full_name = ?, email = ? WHERE id = ?");
        $update->bind_param("sssi", $username, $fullName, $email, $adminId);

        if ($update->execute()) {
            $_SESSION['admin_name'] = $username;
            $_SESSION['admin_profile_message'] = 'Your admin profile has been updated.';
        } else {
            $_SESSION['admin_profile_error'] = 'We could not update your profile. Please try again.';
        }

        $update->close();
    }

    header("Location: admin.php");
    exit;
}

$adminQuery = $con->prepare("SELECT username, full_name, email FROM admin WHERE id = ?");
$adminQuery->bind_param("i", $adminId);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();
$admin = $adminResult->fetch_assoc();
$adminQuery->close();

if (!$admin) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$adminDisplayName = trim($admin['full_name'] ?? '') !== '' ? $admin['full_name'] : $admin['username'];
$adminMessage = $_SESSION['admin_profile_message'] ?? null;
$adminError = $_SESSION['admin_profile_error'] ?? null;
unset($_SESSION['admin_profile_message'], $_SESSION['admin_profile_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin profile | StaySmart</title>
    <?php include '../PHP/head.php'; ?>
    <?php include 'adminlinks.php'; ?>
</head>

<body class="admin-profile-page">
    <?php include '../PHP/header.php'; ?>
    <?php include 'admin-header.php'; ?>

    <main class="admin-profile-main">
        <section class="admin-profile-shell" aria-labelledby="admin-profile-title">
            <?php if ($adminError): ?>
                <div class="admin-profile-notice is-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($adminError, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php elseif ($adminMessage): ?>
                <div class="admin-profile-notice is-success" role="status">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($adminMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <header class="admin-profile-heading">
                <div>
                    <p class="admin-profile-eyebrow">Account settings</p>
                    <h1 id="admin-profile-title">Admin profile</h1>
                    <p>Manage your administrator details and account contact information.</p>
                </div>
                <span class="admin-profile-badge"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?php echo ($_SESSION['admin_role'] ?? 'admin') === 'manager' ? 'Manager' : 'Administrator'; ?></span>
            </header>

            <div class="admin-profile-layout">
                <section class="admin-profile-card" aria-label="Edit administrator profile">
                    <header class="admin-profile-card-heading">
                        <span class="admin-profile-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($adminDisplayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div>
                            <h2><?php echo htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p>Update your details below.</p>
                        </div>
                    </header>

                    <form class="admin-profile-form" action="admin.php" method="post">
                        <div class="admin-profile-field">
                            <label for="admin-full-name">Full name</label>
                            <input type="text" id="admin-full-name" name="admin_full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" maxlength="150" required>
                        </div>
                        <div class="admin-profile-field">
                            <label for="admin-username">Username</label>
                            <input type="text" id="admin-username" name="admin_name" value="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" required>
                        </div>
                        <div class="admin-profile-field">
                            <label for="admin-email">Email address</label>
                            <input type="email" id="admin-email" name="admin_email" value="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required>
                        </div>
                        <button class="admin-profile-save" type="submit" name="update">
                            Save changes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </section>

                <aside class="admin-profile-help">
                    <span class="admin-profile-help-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    <h2>Keep your account secure</h2>
                    <p>Use an email address you can access so you can stay up to date with important account information.</p>
                    <a href="../contact.php">Contact support <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </aside>
            </div>
        </section>
    </main>

    <?php include '../PHP/footer.php'; ?>
</body>

</html>
