<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';

try {
    $db = new Database($config['db']);
    echo "✅ Database connected successfully";
} catch (Throwable $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}