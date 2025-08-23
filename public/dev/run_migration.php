<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';

echo "Running QR codes table migration...\n";

try {
    // Add short_code column if it doesn't exist
    $sql = "ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS short_code VARCHAR(16) DEFAULT NULL";
    $pdo->exec($sql);
    echo "✓ Added short_code column\n";
    
    // Add unique index for short_code if it doesn't exist
    $sql = "CREATE UNIQUE INDEX IF NOT EXISTS uq_short_code ON qr_codes (short_code)";
    $pdo->exec($sql);
    echo "✓ Added unique index for short_code\n";
    
    // Add style_json column if it doesn't exist
    $sql = "ALTER TABLE qr_codes ADD COLUMN IF NOT EXISTS style_json TEXT DEFAULT '{}'";
    $pdo->exec($sql);
    echo "✓ Added style_json column\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
