<?php
function staySmartEnsureSampleFlags(mysqli $db): void
{
    $found = [];
    $result = $db->query("SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name IN ('bookings','archive_bookings','cancelled_bookings','room_reviews','account_visits')");
    while ($row=$result->fetch_assoc()) $found[$row['TABLE_NAME']][$row['COLUMN_NAME']]=true;
    foreach ($found as $table=>$columns) {
        if (!isset($columns['is_sample'])) {
            try { $db->query("ALTER TABLE `$table` ADD COLUMN is_sample TINYINT(1) NOT NULL DEFAULT 0"); }
            catch (mysqli_sql_exception $error) { if ((int)$error->getCode()!==1060) throw $error; }
        }
    }
}
