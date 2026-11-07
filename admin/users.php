<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
require_once __DIR__ . '/../PHP/account-actions.php';
require_once __DIR__.'/../PHP/booking-management.php';
require_once __DIR__.'/../PHP/room-ratings.php';
staySmartEnsureBookingChanges($con);staySmartEnsureRoomRatingsTable($con);
$role = $_SESSION['admin_role'];
$actorId = (int) $_SESSION['admin_id'];
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
if (empty($_SESSION['admin_users_csrf'])) $_SESSION['admin_users_csrf'] = bin2hex(random_bytes(32));
$message = $_SESSION['accounts_message'] ?? '';
$error = $_SESSION['accounts_error'] ?? '';
unset($_SESSION['accounts_message'], $_SESSION['accounts_error']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['admin_users_csrf'], $token)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        try {
            $_SESSION['accounts_message'] = staySmartManageAccount($con, $actorId, $_POST);
            header('Location: users.php');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('StaySmart account action: ' . $exception->getMessage());
            $error = 'The account could not be updated. Check for a duplicate email or username and try again.';
        } catch (RuntimeException $exception) { $error = $exception->getMessage(); }
    }
}
$search = is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
$showDeleted = ($_GET['deleted'] ?? '') === '1';
$accounts = $con->query("SELECT id, username, full_name, email, is_blocked, deleted_at, last_login_at, 'user' AS account_type, 'user' AS account_role FROM users WHERE NOT EXISTS (SELECT 1 FROM account_links WHERE user_id = users.id AND current_type <> 'user')
    UNION ALL SELECT id, username, full_name, email, is_blocked, deleted_at, last_login_at, 'admin' AS account_type, role AS account_role FROM admin WHERE NOT EXISTS (SELECT 1 FROM account_links WHERE admin_id = admin.id AND current_type <> 'admin')
    ORDER BY account_role, username")->fetch_all(MYSQLI_ASSOC);
$counts = ['admin' => 0, 'manager' => 0, 'user' => 0];
$bookingCounts=[];
foreach($con->query('SELECT user_id,COUNT(*) AS total,SUM(is_sample) AS samples FROM (SELECT user_id,is_sample FROM bookings UNION ALL SELECT user_id,is_sample FROM archive_bookings UNION ALL SELECT user_id,is_sample FROM cancelled_bookings) b GROUP BY user_id')->fetch_all(MYSQLI_ASSOC) as $row)$bookingCounts[(int)$row['user_id']]=$row;
$reviewCounts=[];foreach($con->query('SELECT user_id,COUNT(*) AS total FROM room_reviews GROUP BY user_id')->fetch_all(MYSQLI_ASSOC)as$row)$reviewCounts[(int)$row['user_id']]=(int)$row['total'];
$staffLinks=[];foreach($con->query("SELECT admin_id,user_id FROM account_links WHERE current_type='admin'")->fetch_all(MYSQLI_ASSOC)as$link)$staffLinks[(int)$link['admin_id']]=(int)$link['user_id'];
$guestsWithBookings=0;
foreach($accounts as &$account){$bookingOwner=$account['account_type']==='user'?(int)$account['id']:($staffLinks[(int)$account['id']]??0);$account['booking_count']=(int)($bookingCounts[$bookingOwner]['total']??0);$account['sample_count']=(int)($bookingCounts[$bookingOwner]['samples']??0);$account['review_count']=$reviewCounts[$bookingOwner]??0;if(!$account['deleted_at']&&$account['booking_count']>0)$guestsWithBookings++;}unset($account);
foreach ($accounts as $account) if (!$account['deleted_at']) $counts[$account['account_role']]++;
$accounts = array_values(array_filter($accounts, static function ($account) use ($search, $showDeleted, $role) {
    return (!$account['deleted_at'] || $showDeleted)
        && ($role === 'admin' || $account['account_type'] === 'user')
        && ($search === '' || stripos(implode(' ', [$account['username'], $account['full_name'], $account['email'], $account['account_role']]), $search) !== false);
}));
if ($message || $error) $_SESSION['flash_alert'] = ['type' => $error ? 'error' : 'success', 'title' => $error ? 'Account unchanged' : 'Account updated', 'message' => $error ?: $message];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage users | StaySmart</title>
<?php include 'adminlinks.php'; ?>
<link rel="stylesheet" href="../CSS/accounts.css">
</head><body class="admin-users-page">
<?php include '../PHP/header.php'; include 'admin-header.php'; ?>
<main class="admin-users-main"><section class="admin-users-shell">
    <header class="admin-page-heading"><div><p class="admin-page-eyebrow">Accounts and access</p><h1>Manage users</h1><p>Create accounts, manage access and review booking and visit history.</p></div><span class="account-role-label">Signed in as <?php echo $escape(ucfirst($role)); ?></span></header>
    <div class="admin-user-role-summary">
        <?php foreach ($counts as $name => $count): ?><div><span><?php echo $escape(ucfirst($name)); ?>s</span><strong><?php echo $count; ?></strong></div><?php endforeach; ?>
        <div><span>Accounts with bookings</span><strong><?php echo $guestsWithBookings; ?></strong></div>
    </div>
    <p class="accounts-permissions"><?php echo $role === 'admin' ? 'Admins manage staff and users. Your own account and the last active admin are protected.' : 'Managers can create, block, unblock and delete user accounts, and view user histories. Staff accounts are managed by admins.'; ?></p>
    <details class="admin-add-manager" <?php echo $error && ($_POST['action'] ?? '') === 'create' ? 'open' : ''; ?>>
        <summary><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add an account</summary>
        <form class="admin-manager-form" method="post" action="users.php">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>"><input type="hidden" name="action" value="create">
            <div class="admin-user-field"><label for="account-full-name">Full name</label><input id="account-full-name" name="full_name" maxlength="150" autocomplete="name" required></div>
            <div class="admin-user-field"><label for="account-username">Username</label><input id="account-username" name="username" maxlength="100" autocomplete="off" required></div>
            <div class="admin-user-field"><label for="account-email">Email</label><input id="account-email" type="email" name="email" maxlength="255" autocomplete="off" required></div>
            <div class="admin-user-field"><label for="account-password">Password</label><input id="account-password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" required><small>10 to 72 characters.</small></div>
            <div class="admin-user-field"><label for="account-role">Account role</label><select id="account-role" name="role"><option value="user">User</option><?php if ($role === 'admin'): ?><option value="manager">Manager</option><option value="admin">Admin</option><?php endif; ?></select></div>
            <button class="admin-profile-save" type="submit">Create account</button>
        </form>
    </details>
    <section class="admin-users-list" aria-labelledby="accounts-list-title">
        <div class="admin-users-list-heading"><h2 id="accounts-list-title">Accounts <small>(<?php echo count($accounts); ?>)</small></h2>
            <form class="accounts-search" method="get"><label for="account-search">Search accounts</label><input type="search" name="search" id="account-search" value="<?php echo $escape($search); ?>" placeholder="Name, email or role"><label><input type="checkbox" name="deleted" value="1" <?php echo $showDeleted ? 'checked' : ''; ?>> Include deleted</label><button>Search</button></form>
        </div>
        <div class="admin-users-table-wrap"><table class="admin-users-table"><thead><tr><th>Account</th><th>Role</th><th>Status</th><th>Bookings / reviews</th><th>Last sign-in</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($accounts as $account):
            $canManage = staySmartCanManage($role, $account['account_type']) && !($account['account_type'] === 'admin' && (int) $account['id'] === $actorId) && !$account['deleted_at'];
        ?>
            <tr><td data-label="Account"><strong><?php echo $escape($account['full_name'] ?: $account['username']); ?></strong><small><?php echo $escape($account['email']); ?></small><small>@<?php echo $escape($account['username']); ?><?php echo str_ends_with($account['email'],'@staysmart.example')?' · Demo account':''; ?></small></td>
                <td data-label="Role">
                    <?php if ($role === 'admin' && $canManage): ?>
                    <form class="account-role-form" method="post" action="users.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="account_type" value="<?php echo $account['account_type']; ?>">
                        <input type="hidden" name="account_id" value="<?php echo (int) $account['id']; ?>">
                        <select name="role" aria-label="Role for <?php echo $escape($account['username']); ?>">
                            <?php foreach (['admin', 'manager', 'user'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $account['account_role'] === $option ? 'selected' : ''; ?>><?php echo ucfirst($option); ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit">Save role</button>
                    </form>
                    <?php else: ?><span class="account-pill"><?php echo $escape(ucfirst($account['account_role'])); ?></span><?php endif; ?>
                </td>
                <td data-label="Status"><span class="account-pill <?php echo $account['deleted_at'] || $account['is_blocked'] ? 'is-blocked' : 'is-active'; ?>"><?php echo $account['deleted_at'] ? 'Deleted' : ($account['is_blocked'] ? 'Blocked' : 'Active'); ?></span></td>
                <td data-label="Bookings / reviews"><strong><?php echo $account['booking_count']; ?> bookings</strong><small><?php echo $account['review_count']; ?> reviews<?php echo $account['sample_count']?' · '.$account['sample_count'].' sample bookings':''; ?></small></td>
                <td data-label="Last sign-in"><?php echo $escape(staySmartAccountTime($account['last_login_at'])); ?><small>Dubai time</small></td>
                <td data-label="Actions"><div class="account-actions">
                    <a class="account-history-link" href="../account-history.php?type=<?php echo $account['account_type']; ?>&amp;id=<?php echo (int) $account['id']; ?>">View history</a>
                    <?php if ($canManage): ?>
                    <form method="post" action="users.php"><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>"><input type="hidden" name="account_type" value="<?php echo $account['account_type']; ?>"><input type="hidden" name="account_id" value="<?php echo (int) $account['id']; ?>"><button name="action" value="<?php echo $account['is_blocked'] ? 'unblock' : 'block'; ?>"><?php echo $account['is_blocked'] ? 'Unblock' : 'Block'; ?></button></form>
                    <form method="post" action="users.php" data-confirm="Delete this account? Sign-in will be disabled. Booking and activity history will be retained."><input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['admin_users_csrf']); ?>"><input type="hidden" name="account_type" value="<?php echo $account['account_type']; ?>"><input type="hidden" name="account_id" value="<?php echo (int) $account['id']; ?>"><button class="account-delete" name="action" value="delete">Delete</button></form>
                    <?php endif; ?>
                </div></td></tr>
        <?php endforeach; ?>
        <?php if (!$accounts): ?><tr><td colspan="6" class="admin-empty-cell">No accounts match this search.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</section></main>
<dialog class="account-history-dialog" id="account-history-dialog" aria-labelledby="history-dialog-title"><header><h2 id="history-dialog-title">Account history</h2><button type="button" id="close-account-history" aria-label="Close account history">&times;</button></header><iframe title="Account booking and website visit history" id="account-history-frame" src="about:blank"></iframe></dialog>
<script src="../JS/accounts.js" defer></script>
<?php include '../PHP/footer.php'; ?>
