
<?php
session_start();
require_once __DIR__ . '/../PHP/auth.php';
if (empty($_SESSION['booking_status_csrf'])) $_SESSION['booking_status_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings | StaySmart</title>
  <?php include '../PHP/head.php'; ?>
  <?php include 'adminlinks.php';?>

</head>

<body class="admin-status-page">

  <?php include '../PHP/header.php'; ?>
  <?php include 'admin-header.php';?>

  <main class="admin-records-main">
  <section class="roomstatuscontainer" aria-labelledby="bookings-admin-title">
  <header class="admin-page-heading">
    <div>
      <p class="admin-page-eyebrow">Reservation management</p>
      <h1 id="bookings-admin-title">Bookings</h1>
      <p>Review reservations and update guest check-in or booking status.</p>
    </div>
  </header>

  <div class="room-status">

  <table id="room-status-table">

    <thead>
      <tr>
        <th scope="col">Room ID</th>
        <th scope="col">Room name</th>
        <th scope="col">Check-in date</th>
        <th scope="col">Check-out date</th>
        <th scope="col">Status</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>

    <tbody>
  <?php
  include '../PHP/connect.php';
  $query = "SELECT bookings.*, status AS status FROM bookings WHERE status != 'available' ORDER BY booking_id DESC";
  $result = mysqli_query($con, $query);
  if ($result):
      while ($row = mysqli_fetch_assoc($result)):
  ?>
  
    <tr>

      <td data-label="Room ID"><?php echo htmlspecialchars((string) $row['room_id'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td data-label="Room name"><?php echo htmlspecialchars($row['room_name'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td data-label="Check-in"><?php echo htmlspecialchars($row['check_in'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td data-label="Check-out"><?php echo htmlspecialchars($row['check_out'], ENT_QUOTES, 'UTF-8'); ?></td>
      <td data-label="Status"><span class="admin-status-badge"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['status'])), ENT_QUOTES, 'UTF-8'); ?></span></td>

      <td data-label="Actions">

        <form class="admin-status-actions" action="update_status.php" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['booking_status_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

          <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars((string) $row['booking_id'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) $row['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="room_id" value="<?php echo htmlspecialchars((string) $row['room_id'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="room_name" value="<?php echo htmlspecialchars($row['room_name'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="check_in" value="<?php echo htmlspecialchars($row['check_in'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="check_out" value="<?php echo htmlspecialchars($row['check_out'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="total_price" value="<?php echo htmlspecialchars((string) $row['total_price'], ENT_QUOTES, 'UTF-8'); ?>">

          <button class="statbtn checkedin" type="submit" name="checkin_status">Check in</button>
          <button class="statbtn checkedout" type="submit" name="checkout_status">Check out</button>
          <button class="statbtn booked" type="submit" name="booked">Booked</button>
          <button class="statbtn available" type="submit" name="available">Available</button>

        </form>

      </td>

    </tr>

    <?php
      endwhile;
      if (mysqli_num_rows($result) === 0):
    ?>
      <tr><td class="admin-empty-cell" colspan="6">There are no active bookings to show.</td></tr>
    <?php
      endif;
  else:
    ?>
      <tr><td class="admin-empty-cell" colspan="6">Bookings could not be loaded.</td></tr>
    <?php endif; ?>

    </tbody>

  </table>

</div>

</section>
  </main>
  <?php include '../PHP/footer.php'; ?>

</body>
</html>
