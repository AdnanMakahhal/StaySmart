
<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Past bookings | StaySmart</title>
  <?php include '../PHP/head.php'; ?>
  <?php include 'adminlinks.php';?>
</head>

<body class="admin-history-page">

  <?php include '../PHP/header.php'; ?>
  <?php include 'admin-header.php';?>

  <?php

  include '../PHP/connect.php';

  $query = "SELECT * FROM archive_bookings";
  $result = mysqli_query($con, $query);

  ?>

  <main class="admin-records-main">
  <section class="checkouttablecontainer" aria-labelledby="past-bookings-title">
    <header class="admin-page-heading">
      <div>
        <p class="admin-page-eyebrow">Reservation history</p>
        <h1 id="past-bookings-title">Past bookings</h1>
        <p>Completed stays and their final booking totals.</p>
      </div>
    </header>

  <div class="admin-table-card">
  <table class="checkouttable">

    <thead>
      <tr>
        <th scope="col">Booking ID</th>
        <th scope="col">User ID</th>
        <th scope="col">Room ID</th>
        <th scope="col">Room name</th>
        <th scope="col">Check-in</th>
        <th scope="col">Check-out</th>
        <th scope="col">Total</th>
      </tr>
    </thead>
    <tbody>

  <?php

  if ($result && mysqli_num_rows($result) > 0):
    while ($row = mysqli_fetch_array($result)): ?>

      <tr>
        <td data-label="Booking ID">#<?php echo htmlspecialchars((string) $row['booking_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="User ID"><?php echo htmlspecialchars((string) $row['user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="Room ID"><?php echo htmlspecialchars((string) $row['room_id'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="Room name"><?php echo htmlspecialchars($row['room_name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="Check-in"><?php echo htmlspecialchars($row['check_in'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="Check-out"><?php echo htmlspecialchars($row['check_out'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td data-label="Total">$<?php echo htmlspecialchars((string) $row['total_price'], ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    
    <?php endwhile;
  else: ?>
      <tr><td class="admin-empty-cell" colspan="7">No completed bookings yet.</td></tr>
    <?php endif; ?>

</tbody>
</table>
</div>

  </section>
  </main>
  <?php include '../PHP/footer.php'; ?>

</body>

</html>