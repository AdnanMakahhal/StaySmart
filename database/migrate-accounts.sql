-- Existing XAMPP / MariaDB database upgrade. Existing data is preserved.
USE staysmart;
CREATE TABLE IF NOT EXISTS account_links (
  admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  current_type ENUM('admin','user') NOT NULL,
  UNIQUE KEY uq_account_link_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE admin
  ADD COLUMN IF NOT EXISTS role ENUM('admin','manager') NOT NULL DEFAULT 'admin',
  ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL DEFAULT NULL;
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS booked_at DATETIME NULL DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS message TEXT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE archive_bookings ADD COLUMN IF NOT EXISTS booked_at DATETIME NULL DEFAULT NULL;
CREATE TABLE IF NOT EXISTS account_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('admin','user') NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  page_path VARCHAR(255) NOT NULL,
  visited_at DATETIME NOT NULL,
  KEY idx_visits_account (account_type, account_id, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS account_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_type ENUM('admin','user') NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  event_type VARCHAR(30) NOT NULL,
  actor_admin_id INT UNSIGNED NULL,
  occurred_at DATETIME NOT NULL,
  KEY idx_events_account (account_type, account_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
