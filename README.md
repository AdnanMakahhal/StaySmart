# StaySmart

StaySmart is a PHP and MySQL hotel-room booking website.

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
