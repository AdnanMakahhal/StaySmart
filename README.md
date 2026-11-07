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
2. Configure the local MySQL connection in `PHP/connect.php`.
3. Create the first administrator from PowerShell after setting the four `STAYSMART_ADMIN_*` environment variables:

   ```powershell
   $env:STAYSMART_ADMIN_USERNAME = "admin"
   $env:STAYSMART_ADMIN_FULL_NAME = "StaySmart Administrator"
   $env:STAYSMART_ADMIN_EMAIL = "admin@example.com"
   $env:STAYSMART_ADMIN_PASSWORD = Read-Host "Choose an administrator password"
   php database/create-admin.php
   ```

4. Open the project through your local PHP/Apache server.

Demo guest passwords are generated only when `database/seed-demo-guests.php` is first run. Save the command's output securely; later runs do not reveal or reset existing passwords.
