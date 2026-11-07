USE staysmart;
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
