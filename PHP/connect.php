<?php
if (!isset($con) || !($con instanceof mysqli)) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $localConfigPath = __DIR__ . '/db-config.php';
    $localConfig = [];
    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (!is_array($loadedConfig)) {
            throw new RuntimeException('The local database configuration must return an array.');
        }
        $localConfig = $loadedConfig;
    }

    $dbHost = getenv('STAYSMART_DB_HOST');
    $dbPort = getenv('STAYSMART_DB_PORT');
    $dbUser = getenv('STAYSMART_DB_USER');
    $dbPassword = getenv('STAYSMART_DB_PASSWORD');
    $dbName = getenv('STAYSMART_DB_NAME');

    $dbHost = $dbHost !== false ? $dbHost : ($localConfig['host'] ?? 'localhost');
    $dbPort = $dbPort !== false ? $dbPort : ($localConfig['port'] ?? '3306');
    $dbUser = $dbUser !== false ? $dbUser : ($localConfig['username'] ?? 'root');
    $dbPassword = $dbPassword !== false ? $dbPassword : ($localConfig['password'] ?? '');
    $dbName = $dbName !== false ? $dbName : ($localConfig['database'] ?? 'staysmart');

    $con = new mysqli(
        $dbHost,
        $dbUser,
        $dbPassword,
        $dbName,
        (int) $dbPort
    );
    $con->set_charset('utf8mb4');
}
