<?php
declare(strict_types=1);
define('SKIP_AUTH_GUARD', true);
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "SELF-CHECK\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'n/a') . "\n";

// session
echo "SESSION: " . (session_status() === PHP_SESSION_ACTIVE ? "OK" : "FAIL") . "\n";

// DB
try {
    $pdo = db();
    $ok  = (int)$pdo->query('SELECT 1')->fetchColumn();
    echo "DB: " . ($ok === 1 ? "OK (1)" : "FAIL") . "\n";
} catch (Throwable $e) {
    echo "DB: FAIL " . $e->getMessage() . "\n";
}

echo "UID: " . (int)($_SESSION['uid'] ?? 0) . "\n";
echo "DONE.\n";