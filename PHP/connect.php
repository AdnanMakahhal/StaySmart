<?php
if (!isset($con) || !($con instanceof mysqli)) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $con = new mysqli('localhost', 'root', '', 'staysmart');
    $con->set_charset('utf8mb4');
}
