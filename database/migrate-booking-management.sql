USE staysmart;
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
