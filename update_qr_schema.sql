-- Update QR codes table schema
-- Run this in your MySQL database to add missing columns

USE whoiz;

-- Add missing columns if they don't exist
ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS title VARCHAR(120) NOT NULL DEFAULT 'Untitled QR Code';
ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;
ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE qr_codes MODIFY COLUMN style_json TEXT DEFAULT '{}';

-- Update existing records to have proper titles if they don't have them
UPDATE qr_codes SET title = CONCAT('QR #', id) WHERE title = 'Untitled QR Code' OR title IS NULL;

-- Set all existing records as active if is_active is NULL
UPDATE qr_codes SET is_active = 1 WHERE is_active IS NULL;
