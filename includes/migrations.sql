-- QR codes
CREATE TABLE IF NOT EXISTS qr_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  type ENUM('url','vcard','text','email','wifi','pdf','app','image') NOT NULL,
  payload TEXT NOT NULL,
  style_json TEXT NOT NULL,       -- ألوان/خلفية/مقاس/قالب...
  is_dynamic TINYINT(1) DEFAULT 0,
  short_code VARCHAR(16) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY (user_id),
  UNIQUE KEY uq_short_code (short_code)
);

-- scans (للديناميك فقط)
CREATE TABLE IF NOT EXISTS qr_scans (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  qr_id INT NOT NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip VARBINARY(16) NULL,
  ua VARCHAR(255) NULL,
  country CHAR(2) NULL,
  city VARCHAR(80) NULL,
  FOREIGN KEY (qr_id) REFERENCES qr_codes(id) ON DELETE CASCADE
);

-- Events table (Phase 2 unified schema)
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_type ENUM('link','qr','page') NOT NULL,
  item_id INT NOT NULL,
  type ENUM('click','scan','open','create') NOT NULL,
  label VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Ensure indexes exist (idempotent guards)
-- created_at index
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='events' AND INDEX_NAME='idx_created_at'
);
SET @sql := IF(@idx_exists=0, 'ALTER TABLE events ADD INDEX idx_created_at (created_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- user_id index
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='events' AND INDEX_NAME='idx_user_id'
);
SET @sql := IF(@idx_exists=0, 'ALTER TABLE events ADD INDEX idx_user_id (user_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- composite (item_type, item_id)
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='events' AND INDEX_NAME='idx_item'
);
SET @sql := IF(@idx_exists=0, 'ALTER TABLE events ADD INDEX idx_item (item_type, item_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- compound (user_id, created_at) for timeseries queries
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='events' AND INDEX_NAME='idx_events_user_created'
);
SET @sql := IF(@idx_exists=0, 'ALTER TABLE events ADD INDEX idx_events_user_created (user_id, created_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
