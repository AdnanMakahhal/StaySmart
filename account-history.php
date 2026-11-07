<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once __DIR__ . '/PHP/booking-management.php';
staySmartEnsureBookingChanges($con);
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$isStaff = isset($_SESSION['admin_id']);
if (!$isStaff && !isset($_SESSION['user_id'])) { http_response_code(403); exit('Sign in to view account history.'); }
$type = $isStaff ? ($_GET['type'] ?? '') : 'user';
$id = $isStaff ? filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) : (int) $_SESSION['user_id'];
if (!is_string($type) || !in_array($type, ['admin', 'user'], true) || !$id || $id < 1) { http_response_code(400); exit('A valid account is required.'); }
$linkColumn = $type === 'admin' ? 'admin_id' : 'user_id';
$linkQuery = $con->prepare("SELECT * FROM account_links WHERE `$linkColumn` = ?");
$linkQuery->bind_param('i', $id);
$linkQuery->execute();
$accountLink = $linkQuery->get_result()->fetch_assoc();
if ($accountLink && $accountLink['current_type'] !== $type) {
    $type = $accountLink['current_type'];
    $id = (int) $accountLink[$type === 'admin' ? 'admin_id' : 'user_id'];
}
if ($isStaff && !staySmartCanManage($_SESSION['admin_role'], $type)) { http_response_code(403); exit('You do not have permission to view staff histories.'); }
$table = staySmartAccountTable($type);
$stmt = $con->prepare("SELECT id, username, full_name, email, created_at, last_login_at, deleted_at, is_blocked FROM `$table` WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
if (!$account) { http_response_code(404); exit('Account not found.'); }
$pageInput = filter_var($_GET['visits_page'] ?? 1, FILTER_VALIDATE_INT);
$visitPage = max(1, min(100000, $pageInput ?: 1));
$offset = ($visitPage - 1) * 50;
$visitsQuery = $con->prepare('SELECT MIN(visited_at) AS visited_at, is_sample FROM account_visits WHERE account_type = ? AND account_id = ? GROUP BY DATE(DATE_ADD(visited_at, INTERVAL 4 HOUR)), is_sample ORDER BY visited_at DESC LIMIT 51 OFFSET ?');
$visitsQuery->bind_param('sii', $type, $id, $offset);
$visitsQuery->execute();
$visits = $visitsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$hasNext = count($visits) > 50;
$visits = array_slice($visits, 0, 50);
$eventsQuery = $con->prepare('SELECT event_type, occurred_at FROM account_events WHERE account_type = ? AND account_id = ? ORDER BY occurred_at DESC, id DESC LIMIT 100');
$eventsQuery->bind_param('si', $type, $id);
$eventsQuery->execute();
$events = $eventsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$bookings = [];
$bookingUserId = $type === 'user' ? $id : ($accountLink ? (int) $accountLink['user_id'] : null);
if ($bookingUserId !== null) {
    $bookingsQuery = $con->prepare("SELECT booking_id, room_name, check_in, check_out, total_price, status AS status, booked_at, booking_date AS original_date, is_sample FROM bookings WHERE user_id = ?
        UNION ALL SELECT booking_id, room_name, check_in, check_out, total_price, 'completed' AS status, booked_at, NULL AS original_date, is_sample FROM archive_bookings WHERE user_id = ?
        UNION ALL SELECT booking_id, room_name, check_in, check_out, total_price, 'cancelled' AS status, booked_at, NULL AS original_date, is_sample FROM cancelled_bookings WHERE user_id = ? ORDER BY check_in DESC");
    $bookingsQuery->bind_param('iii', $bookingUserId, $bookingUserId, $bookingUserId);
    $bookingsQuery->execute();
    $bookings = $bookingsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}
$popup = ($_GET['popup'] ?? '') === '1';
$baseUrl = 'account-history.php?type=' . $type . '&id=' . $id . ($popup ? '&popup=1' : '');
$reviewHistory=[];
if($bookingUserId!==null){$q=$con->prepare('SELECT r.rating,r.review_text,r.created_at,r.is_sample,a.room_name FROM room_reviews r LEFT JOIN archive_bookings a ON a.booking_id=r.booking_id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 100');$q->bind_param('i',$bookingUserId);$q->execute();$reviewHistory=$q->get_result()->fetch_all(MYSQLI_ASSOC);}
$completedCount=count(array_filter($bookings,static fn($b)=>$b['status']==='completed'));
$upcomingCount=count(array_filter($bookings,static fn($b)=>$b['status']==='booked'&&$b['check_in']>=(new DateTimeImmutable('today',new DateTimeZone('Asia/Dubai')))->format('Y-m-d')));
$sampleCount=count(array_filter($bookings,static fn($b)=>(bool)$b['is_sample']));
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Account history | StaySmart</title><link rel="stylesheet" href="CSS/header.css"><link rel="stylesheet" href="CSS/accounts.css"></head><body class="account-history-page">
<main class="account-history-main">
    <?php if (!$popup): ?><a href="<?php echo $isStaff ? 'admin/users.php' : 'profile.php'; ?>">&larr; Back to <?php echo $isStaff ? 'accounts' : 'profile'; ?></a><?php endif; ?>
    <header><p class="history-eyebrow">ACCOUNT HISTORY</p><h1><?php echo $escape($account['full_name'] ?: $account['username']); ?></h1><p><?php echo $escape($account['email']); ?> · @<?php echo $escape($account['username']); ?></p><span class="account-pill"><?php echo $account['deleted_at'] ? 'Deleted' : ($account['is_blocked'] ? 'Blocked' : 'Active'); ?></span></header>
    <p class="accounts-permissions">All times are shown in Dubai time (UTC+4). Website visits cover StaySmart pages only, starting when tracking was enabled. Earlier timestamps are shown as “Not recorded”.</p>
    <dl class="history-stats"><div><dt>Account created</dt><dd><?php echo $escape(staySmartAccountTime($account['created_at'])); ?></dd></div><div><dt>Last sign-in</dt><dd><?php echo $escape(staySmartAccountTime($account['last_login_at'])); ?></dd></div><div><dt>Total bookings</dt><dd><?php echo count($bookings); ?><?php echo $sampleCount?' ('.$sampleCount.' sample)':''; ?></dd></div><div><dt>Completed stays</dt><dd><?php echo $completedCount; ?></dd></div><div><dt>Upcoming stays</dt><dd><?php echo $upcomingCount; ?></dd></div><div><dt>Ratings &amp; reviews</dt><dd><?php echo count($reviewHistory); ?></dd></div></dl>
    <section><h2>Booking history</h2>
        <div class="history-table-wrap"><table><thead><tr><th>Booking / room</th><th>Booked at</th><th>Stay dates</th><th>Total</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($bookings as $booking): ?><tr><td><strong>#<?php echo (int) $booking['booking_id']; ?></strong><small><?php echo $escape($booking['room_name']); ?><?php echo $booking['is_sample'] ? ' (Sample)' : ''; ?></small></td><td><?php echo $escape(staySmartAccountTime($booking['booked_at'])); ?><?php if (!$booking['booked_at'] && $booking['original_date']): ?><small>Date: <?php echo $escape($booking['original_date']); ?> (time unavailable)</small><?php endif; ?></td><td><?php echo $escape($booking['check_in']); ?><small>to <?php echo $escape($booking['check_out']); ?></small></td><td>$<?php echo number_format((float) $booking['total_price'], 2); ?></td><td><?php echo $escape(ucwords(str_replace('_', ' ', $booking['status']))); ?></td></tr><?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="5"><?php echo $type === 'admin' ? 'Staff accounts do not have guest bookings.' : 'No bookings recorded.'; ?></td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section><h2>Ratings &amp; reviews</h2><div class="history-table-wrap"><table><thead><tr><th>Room</th><th>Rating</th><th>Review</th><th>Added</th></tr></thead><tbody><?php foreach($reviewHistory as$r): ?><tr><td><?php echo $escape($r['room_name']); ?><?php echo $r['is_sample']?' (Demo)':''; ?></td><td><?php echo (int)$r['rating']; ?>/5</td><td><?php echo $escape($r['review_text']?:'Star rating only'); ?></td><td><?php echo $escape(staySmartAccountTime($r['created_at'])); ?></td></tr><?php endforeach; ?><?php if(!$reviewHistory): ?><tr><td colspan="4">No ratings yet.</td></tr><?php endif; ?></tbody></table></div></section>
    <section><h2>Website visits</h2><p>One website visit per 24 hours. Older page entries are grouped by day.</p>
        <div class="history-table-wrap"><table><thead><tr><th>Activity</th><th>Visit time</th></tr></thead><tbody>
        <?php foreach ($visits as $visit): ?><tr><td>Visited StaySmart<?php echo $visit['is_sample'] ? ' (Sample history)' : ''; ?></td><td><?php echo $escape(staySmartAccountTime($visit['visited_at'])); ?></td></tr><?php endforeach; ?>
        <?php if (!$visits): ?><tr><td colspan="2">No website visits recorded yet.</td></tr><?php endif; ?>
        </tbody></table></div>
        <nav class="history-pagination" aria-label="Visit history pages"><?php if ($visitPage > 1): ?><a href="<?php echo $escape($baseUrl . '&visits_page=' . ($visitPage - 1)); ?>">&larr; Newer visits</a><?php endif; ?><?php if ($hasNext): ?><a href="<?php echo $escape($baseUrl . '&visits_page=' . ($visitPage + 1)); ?>">Older visits &rarr;</a><?php endif; ?></nav>
    </section>
    <section><h2>Account activity</h2><p>Most recent 100 sign-in, access and account events.</p><div class="history-table-wrap"><table><thead><tr><th>Event</th><th>Time</th></tr></thead><tbody>
        <?php foreach ($events as $event): ?><tr><td><?php echo $escape(ucfirst($event['event_type'])); ?></td><td><?php echo $escape(staySmartAccountTime($event['occurred_at'])); ?></td></tr><?php endforeach; ?>
        <?php if (!$events): ?><tr><td colspan="2">No account activity recorded yet.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</main>
<?php if ($popup): ?><script>document.addEventListener('keydown',event=>{if(event.key==='Escape')parent.postMessage({type:'staysmart-history-close'},location.origin);});</script><?php endif; ?>
</body></html>
