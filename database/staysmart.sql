-- Fresh database setup for the current StaySmart PHP application.
-- Import using phpMyAdmin > Import, or paste into its SQL tab.
-- Existing tables are left unchanged; this is not an existing-schema migration.
CREATE DATABASE IF NOT EXISTS staysmart
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staysmart;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  full_name VARCHAR(150) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  last_login_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  full_name VARCHAR(150) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'manager') NOT NULL DEFAULT 'admin',
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL DEFAULT NULL,
  created_at DATETIME NULL DEFAULT NULL,
  last_login_at DATETIME NULL DEFAULT NULL,
  UNIQUE KEY uq_admin_username (username),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  room_name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  size VARCHAR(50) NOT NULL DEFAULT '',
  beds VARCHAR(100) NOT NULL DEFAULT '',
  capacity INT UNSIGNED NOT NULL DEFAULT 1,
  image VARCHAR(255) NOT NULL DEFAULT '',
  facilities TEXT NOT NULL,
  description TEXT NULL,
  rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  adult INT UNSIGNED NOT NULL DEFAULT 1,
  kids INT UNSIGNED NOT NULL DEFAULT 0,
  availability ENUM('available', 'not available') NOT NULL DEFAULT 'available',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_rooms_name (room_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
  booking_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  room_name VARCHAR(150) NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  guests INT UNSIGNED NOT NULL,
  phone_number VARCHAR(30) NOT NULL,
  message TEXT NOT NULL,
  booking_date DATE NOT NULL,
  booked_at DATETIME NULL DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(12,2)
    GENERATED ALWAYS AS (DATEDIFF(check_out, check_in) * price) STORED,
  status ENUM('booked', 'checked_in', 'checked_out', 'available')
    NOT NULL DEFAULT 'booked',
  KEY idx_bookings_user (user_id),
  KEY idx_bookings_room (room_id),
  KEY idx_bookings_date (booking_date),
  CONSTRAINT chk_booking_dates CHECK (check_out > check_in),
  CONSTRAINT chk_booking_guests CHECK (guests >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archive_bookings (
  booking_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  room_name VARCHAR(150) NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  booked_at DATETIME NULL DEFAULT NULL,
  KEY idx_archive_user (user_id),
  KEY idx_archive_room (room_id),
  KEY idx_archive_checkout (check_out)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_reviews (
  booking_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review_text VARCHAR(1000) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_room_reviews_room_id (room_id),
  KEY idx_room_reviews_user_id (user_id),
  CONSTRAINT chk_room_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_content (
  content_key VARCHAR(100) NOT NULL PRIMARY KEY,
  content_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the first administrator with database/create-admin.php.
-- Add rooms through the Manage rooms page after logging in.
-- The PHP application seeds default site_content values on first page load.
CREATE TABLE IF NOT EXISTS account_links (
  admin_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  current_type ENUM('admin','user') NOT NULL,
  UNIQUE KEY uq_account_link_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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


CREATE TABLE IF NOT EXISTS cancelled_bookings (
  booking_id INT UNSIGNED PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  room_name VARCHAR(150) NOT NULL,
  user_name VARCHAR(100) NOT NULL,
  check_in DATE NOT NULL,
  check_out DATE NOT NULL,
  guests INT UNSIGNED NOT NULL,
  phone_number VARCHAR(30) NOT NULL,
  message TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  booked_at DATETIME NULL,
  cancelled_at DATETIME NOT NULL,
  KEY idx_cancelled_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS booking_payments (
  booking_id INT UNSIGNED NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  method ENUM('pay_at_property') NOT NULL DEFAULT 'pay_at_property',
  status ENUM('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  amount DECIMAL(12,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'USD',
  created_at DATETIME NOT NULL,
  KEY idx_booking_payments_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE bookings ADD COLUMN IF NOT EXISTS is_sample TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE archive_bookings ADD COLUMN IF NOT EXISTS is_sample TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE cancelled_bookings ADD COLUMN IF NOT EXISTS is_sample TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE room_reviews ADD COLUMN IF NOT EXISTS is_sample TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE account_visits ADD COLUMN IF NOT EXISTS is_sample TINYINT(1) NOT NULL DEFAULT 0;


CREATE TABLE IF NOT EXISTS demo_guest_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  city VARCHAR(100) NOT NULL,
  country VARCHAR(100) NOT NULL,
  statement VARCHAR(500) NOT NULL,
  portrait_source VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS demo_guest_batches (
  batch_key VARCHAR(100) PRIMARY KEY,
  accounts_json TEXT NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
