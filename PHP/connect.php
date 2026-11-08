<?php
if (!isset($con) || !($con instanceof mysqli)) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $dbHost = getenv('STAYSMART_DB_HOST') ?: 'localhost';
    $dbPort = getenv('STAYSMART_DB_PORT') ?: '3306';
    $dbUser = getenv('STAYSMART_DB_USER') ?: 'root';
    $dbPassword = getenv('STAYSMART_DB_PASSWORD');
    $dbName = getenv('STAYSMART_DB_NAME') ?: 'staysmart';

    $con = new mysqli(
        $dbHost,
        $dbUser,
        $dbPassword === false ? '' : $dbPassword,
        $dbName,
        (int) $dbPort
    );
    $con->set_charset('utf8mb4');
}
