<?php
// Quantity is total physical inventory. Reservations consume units only on their stay dates.
function staySmartEnsureDateInventory(mysqli $db): void
{
    static $done = false;
    if ($done) return;
    $db->query("CREATE TABLE IF NOT EXISTS inventory_migrations (name VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
    $db->begin_transaction();
    try {
        $db->query("INSERT IGNORE INTO inventory_migrations (name) VALUES ('date_inventory_v1')");
        if ($db->affected_rows === 1) {
            $db->query("UPDATE rooms r LEFT JOIN (SELECT room_id, COUNT(*) AS reserved FROM bookings WHERE is_sample = 0 GROUP BY room_id) b ON b.room_id=r.id SET r.quantity=r.quantity+COALESCE(b.reserved,0)");
            $db->query("UPDATE rooms SET availability=IF(quantity>0,'available','not available')");
        }
        $db->commit();
        $done = true;
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}

function staySmartAvailableUnits(mysqli $db, int $roomId, string $in, string $out, int $exclude = 0): int
{
    $q = $db->prepare('SELECT quantity FROM rooms WHERE id=?');
    $q->bind_param('i', $roomId); $q->execute();
    $quantity = (int)($q->get_result()->fetch_assoc()['quantity'] ?? 0);
    $q = $db->prepare("SELECT check_in,check_out FROM bookings WHERE room_id=? AND booking_id<>? AND is_sample=0 AND status IN ('booked','checked_in') AND check_in<? AND check_out>?");
    $q->bind_param('iiss', $roomId, $exclude, $out, $in); $q->execute();
    $events = [];
    foreach ($q->get_result() as $b) {
        $start = max($in,$b['check_in']); $end = min($out,$b['check_out']);
        $events[$start] = ($events[$start] ?? 0)+1;
        $events[$end] = ($events[$end] ?? 0)-1;
    }
    ksort($events); $used=0; $peak=0;
    foreach ($events as $delta) { $used+=$delta; $peak=max($peak,$used); }
    return max(0,$quantity-$peak);
}

function staySmartRequireAvailability(mysqli $db, int $roomId, string $in, string $out, int $exclude = 0): void
{
    if (staySmartAvailableUnits($db,$roomId,$in,$out,$exclude)<1) throw new RuntimeException('These dates are locked: all units are reserved for part of this stay. Please choose different dates.');
}
