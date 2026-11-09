# StaySmart

Discover a comfortable stay with StaySmart, a hotel-room booking platform built with PHP and MySQL. Browse rooms, manage reservations, and share reviews after checkout—all backed by guest accounts and an admin dashboard.

## Live deployment

Visit [StaySmart](https://staysmart.wasmer.app/).

## Features

- Browse and filter rooms, check availability, and book a stay.
- Manage reservations, payments, profile details, and booking history.
- Rate rooms and share reviews after completed stays.
- Manage rooms, bookings, guest accounts, and homepage content from the admin panel.
- Responsive layouts for desktop and mobile.

## Local setup

1. Import `database/staysmart.sql` into MySQL.
2. The local MySQL connection defaults to `localhost:3306` with user `root`, an empty password, and database `staysmart`. Override these values with the `STAYSMART_DB_HOST`, `STAYSMART_DB_PORT`, `STAYSMART_DB_USER`, `STAYSMART_DB_PASSWORD`, and `STAYSMART_DB_NAME` environment variables when needed.
3. Create the first administrator from PowerShell after setting the four `STAYSMART_ADMIN_*` environment variables:

   ```powershell
   $env:STAYSMART_ADMIN_USERNAME = "admin"
   $env:STAYSMART_ADMIN_FULL_NAME = "StaySmart Administrator"
   $env:STAYSMART_ADMIN_EMAIL = "admin@example.com"
   $env:STAYSMART_ADMIN_PASSWORD = Read-Host "Choose an administrator password"
   php database/create-admin.php
   ```

4. Open the project through your local PHP/Apache server.

For the Wasmer deployment, configure the `STAYSMART_DB_*` variables with the hostname, port, username, password, and database name of a reachable MySQL server. The deployment URL is not a database host.

## InfinityFree setup

1. Copy `PHP/db-config.example.php` to `PHP/db-config.php` and verify the database name against the MySQL Databases page in the InfinityFree control panel.
2. Set the `password` value in `PHP/db-config.php` to your current MySQL password. Keep this file private; it is excluded from Git.
3. Upload the project contents to `htdocs` for `staysmart.infinityfree.me`. Upload `PHP/db-config.php` separately through the hosting file manager or FTP; it is intentionally not tracked by Git.
4. Import `database/staysmart.sql` into the hosting database using the InfinityFree database tools. Apply the other migration SQL files only if their changes are needed and are not already present.

The app also accepts the `STAYSMART_DB_*` environment variables, which take precedence over `PHP/db-config.php`. Do not put database passwords in tracked files.

Demo guest passwords are generated only when `database/seed-demo-guests.php` is first run. Save the command's output securely; later runs do not reveal or reset existing passwords.
