USE staysmart;
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
