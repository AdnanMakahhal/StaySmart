<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';

include '../PHP/connect.php';

$periodOptions = [
    'all' => 'All time',
    'this_year' => 'This year',
    'last_year' => 'Previous year',
    'six_months' => 'Last 6 months',
    'month' => 'This month',
    'week' => 'This week',
    'day' => 'Today',
    'custom' => 'Custom range',
];
$requestedPeriod = $_GET['period'] ?? 'all';
$selectedPeriod = is_string($requestedPeriod) && isset($periodOptions[$requestedPeriod]) ? $requestedPeriod : 'all';
$periodStart = null;
$periodEnd = null;
$periodDescription = 'All recorded bookings';
$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Dubai'));
$rangeError = '';
$startInput = is_string($_GET['start'] ?? null) ? $_GET['start'] : $today->modify('first day of this month')->format('Y-m-d');
$endInput = is_string($_GET['end'] ?? null) ? $_GET['end'] : $today->format('Y-m-d');
$parseDate = static function (string $value): ?DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Dubai'));
    return $date && $date->format('Y-m-d') === $value && $date->format('Y') >= '1000' && $date->format('Y') < '9999' ? $date : null;
};

switch ($selectedPeriod) {
    case 'custom':
        $startDate = $parseDate($startInput);
        $endDate = $parseDate($endInput);
        if (!$startDate || !$endDate) {
            $rangeError = 'Enter a valid start date and end date.';
        } elseif ($endDate < $startDate) {
            $rangeError = 'End date must be on or after the start date.';
        } else {
            $periodStart = $startDate;
            $periodEnd = $endDate->modify('+1 day');
            $periodDescription = $startDate->format('M j, Y') . ' – ' . $endDate->format('M j, Y');
        }
        break;
    case 'this_year':
        $periodStart = $today->setDate((int) $today->format('Y'), 1, 1);
        $periodEnd = $periodStart->modify('+1 year');
        $periodDescription = 'January 1 – December 31, ' . $today->format('Y');
        break;
    case 'last_year':
        $periodStart = $today->setDate((int) $today->format('Y') - 1, 1, 1);
        $periodEnd = $periodStart->modify('+1 year');
        $periodDescription = $periodStart->format('Y');
        break;
    case 'six_months':
        $periodStart = $today->modify('-6 months');
        $periodEnd = $today->modify('+1 day');
        $periodDescription = $periodStart->format('M j, Y') . ' – ' . $today->format('M j, Y');
        break;
    case 'month':
        $periodStart = $today->modify('first day of this month');
        $periodEnd = $periodStart->modify('+1 month');
        $periodDescription = $today->format('F Y');
        break;
    case 'week':
        $periodStart = $today->modify('monday this week');
        $periodEnd = $periodStart->modify('+1 week');
        $periodDescription = $periodStart->format('M j') . ' – ' . $periodEnd->modify('-1 day')->format('M j, Y');
        break;
    case 'day':
        $periodStart = $today;
        $periodEnd = $today->modify('+1 day');
        $periodDescription = $today->format('F j, Y');
        break;
}

$periodStartValue = $periodStart ? $periodStart->format('Y-m-d') : null;
$periodEndValue = $periodEnd ? $periodEnd->format('Y-m-d') : null;

$getPeriodTotal = static function (?string $dateColumn, string $table, string $expression, string $condition = '') use ($con, $periodStartValue, $periodEndValue): float {
    $clauses = [];
    if (in_array($table, ['bookings','archive_bookings'], true)) $clauses[] = 'is_sample = 0';
    if ($condition !== '') {
        $clauses[] = $condition;
    }
    if ($dateColumn !== null && $periodStartValue !== null && $periodEndValue !== null) {
        $clauses[] = "`$dateColumn` >= ? AND `$dateColumn` < ?";
    }

    $sql = "SELECT $expression AS total FROM `$table`" . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '');
    $statement = $con->prepare($sql);
    if ($dateColumn !== null && $periodStartValue !== null && $periodEndValue !== null) {
        $statement->bind_param('ss', $periodStartValue, $periodEndValue);
    }
    $statement->execute();
    $result = $statement->get_result();
    $total = (float) $result->fetch_assoc()['total'];
    $statement->close();

    return $total;
};

$totalRooms = (int) $getPeriodTotal(null, 'rooms', 'COALESCE(SUM(quantity), 0)');
$totalBookings = (int) (
    $getPeriodTotal('booking_date', 'bookings', 'COUNT(*)') +
    $getPeriodTotal('check_out', 'archive_bookings', 'COUNT(*)')
);
$checkedIn = (int) $getPeriodTotal('booking_date', 'bookings', 'COUNT(*)', "status = 'checked_in'");
$checkedOut = (int) $getPeriodTotal('check_out', 'archive_bookings', 'COUNT(*)');
$totalEarnings = $getPeriodTotal('check_out', 'archive_bookings', 'COALESCE(SUM(total_price), 0)');
$totalUsers = (int) $getPeriodTotal(null, 'users', 'COUNT(*)', 'deleted_at IS NULL');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard | StaySmart</title>
    <?php include '../PHP/head.php'; ?>
    <?php include 'adminlinks.php'; ?>
    <link rel="stylesheet" href="../CSS/dashboard-filters.css">
</head>

<body class="admin-dashboard-page">
    <?php include '../PHP/header.php'; ?>
    <?php include 'admin-header.php'; ?>

    <main class="admin-dashboard-main">
        <section class="dashboard-section" aria-labelledby="admin-dashboard-title">
            <header class="admin-page-heading">
                <div>
                    <p class="admin-page-eyebrow">Overview</p>
                    <h1 id="admin-dashboard-title">Dashboard</h1>
                    <p>A quick overview of your StaySmart activity.</p>
                </div>
                <p class="dashboard-date">
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    <time datetime="<?php echo $today->format('Y-m-d'); ?>"><?php echo $today->format('l, F j, Y'); ?></time>
                </p>
            </header>

            <form class="dashboard-filter dashboard-report-filter" id="dashboard-report-filter" method="get" action="dashboard.php#dashboard-report-filter">
                <div class="dashboard-filter-intro">
                    <span class="dashboard-filter-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>
                    <span class="dashboard-filter-copy">
                        <strong>Reporting period</strong>
                        <small>Choose a preset or set your own date range.</small>
                    </span>
                </div>
                <fieldset class="report-presets">
                    <legend>Choose a time period</legend>
                    <?php foreach (['day', 'week', 'month', 'six_months', 'this_year', 'last_year', 'all', 'custom'] as $periodKey): ?>
                        <label><input type="radio" name="period" value="<?php echo $periodKey; ?>" <?php echo $selectedPeriod === $periodKey ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($periodOptions[$periodKey], ENT_QUOTES, 'UTF-8'); ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="report-date-row" id="report-date-row">
                    <div><label for="report-start">Start date</label><input id="report-start" type="date" name="start" min="1000-01-01" max="9998-12-31" value="<?php echo htmlspecialchars($startInput, ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="report-date-help<?php echo $rangeError ? ' report-range-error' : ''; ?>"></div>
                    <span class="report-date-arrow" aria-hidden="true">&rarr;</span>
                    <div><label for="report-end">End date</label><input id="report-end" type="date" name="end" min="1000-01-01" max="9998-12-31" value="<?php echo htmlspecialchars($endInput, ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="report-date-help<?php echo $rangeError ? ' report-range-error' : ''; ?>"></div>
                    <p id="report-date-help">For a custom range, both dates are included.</p>
                </div>
                <?php if ($rangeError): ?><p id="report-range-error" class="report-range-error" role="alert"><?php echo htmlspecialchars($rangeError, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                <div class="report-filter-footer">
                <p class="dashboard-filter-period" aria-live="polite">
                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                    <span><?php echo $rangeError ? 'Select valid dates to show your report.' : 'Showing '; ?><?php if (!$rangeError): ?><strong><?php echo htmlspecialchars($periodDescription, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?></span>
                </p>
                <div class="report-filter-actions"><a href="dashboard.php#dashboard-report-filter">Reset</a><button class="dashboard-filter-submit" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>Apply period</span></button></div>
                </div>
            </form>

            <?php if (!$rangeError): ?>
            <div class="dashboard-container">
                <article class="dashboard-item-container total_rooms">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Total rooms</p>
                        <h2><?php echo $totalRooms; ?></h2>
                    </div>
                </article>
                <article class="dashboard-item-container total_bookings">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Bookings</p>
                        <h2><?php echo $totalBookings; ?></h2>
                    </div>
                </article>
                <article class="dashboard-item-container total_checkin">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Checked in</p>
                        <h2><?php echo $checkedIn; ?></h2>
                    </div>
                </article>
                <article class="dashboard-item-container total_checkout">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Checked out</p>
                        <h2><?php echo $checkedOut; ?></h2>
                    </div>
                </article>
                <article class="dashboard-item-container total_earnings">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Total earnings</p>
                        <h2>$<?php echo number_format($totalEarnings, 2); ?></h2>
                    </div>
                </article>
                <article class="dashboard-item-container users">
                    <span class="dashboard-card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                    <div class="dashboard-metric-copy">
                        <p>Users</p>
                        <h2><?php echo $totalUsers; ?></h2>
                    </div>
                </article>
            </div>
            <p class="dashboard-metrics-note">Booking, check-in, checkout, and earnings use the selected period. Room and user cards show current totals.</p>
            <?php endif; ?>

            <section class="dashboard-quick-links" aria-labelledby="dashboard-quick-links-title">
                <div class="dashboard-quick-links-heading">
                    <div>
                        <h2 id="dashboard-quick-links-title">Quick actions</h2>
                        <p>Go straight to the tasks you use most.</p>
                    </div>
                </div>
                <div class="dashboard-quick-links-grid">
                    <a class="dashboard-quick-link" href="addrooms.php">
                        <span class="dashboard-quick-link-icon"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
                        <span class="dashboard-quick-link-copy"><strong>Manage rooms</strong><small>Update room inventory</small></span>
                        <i class="fa-solid fa-arrow-right dashboard-quick-link-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="dashboard-quick-link" href="status.php">
                        <span class="dashboard-quick-link-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
                        <span class="dashboard-quick-link-copy"><strong>View bookings</strong><small>Review guest reservations</small></span>
                        <i class="fa-solid fa-arrow-right dashboard-quick-link-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="dashboard-quick-link" href="checkedout.php">
                        <span class="dashboard-quick-link-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
                        <span class="dashboard-quick-link-copy"><strong>Stay history</strong><small>See completed stays</small></span>
                        <i class="fa-solid fa-arrow-right dashboard-quick-link-arrow" aria-hidden="true"></i>
                    </a>
                    <a class="dashboard-quick-link" href="users.php">
                        <span class="dashboard-quick-link-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                        <span class="dashboard-quick-link-copy"><strong>Manage users</strong><small>View accounts and roles</small></span>
                        <i class="fa-solid fa-arrow-right dashboard-quick-link-arrow" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        </section>
    </main>

    <script src="../JS/dashboard-filters.js" defer></script>
    <?php include '../PHP/footer.php'; ?>
</body>

</html>
