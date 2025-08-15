<?php
// includes/db.php
$config = require __DIR__ . '/../app/config.php';
$db     = $config['db'];

$dsn = sprintf(
  'mysql:host=%s;port=%d;dbname=%s;charset=%s',
  $db['host'],
  $db['port'],
  $db['name'],
  $db['charset'] ?? 'utf8mb4'
);

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $db['user'], $db['pass'], $options);
} catch (PDOException $e) {
  // أثناء التطوير خليه يطبع رسالة واضحة
  http_response_code(500);
  die('DB connection error: ' . $e->getMessage());
}