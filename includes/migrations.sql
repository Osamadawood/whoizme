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