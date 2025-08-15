<?php
declare(strict_types=1);
define('SKIP_AUTH_GUARD', true);
require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();
    $ok  = (int)$pdo->query('SELECT 1')->fetchColumn();
    echo $ok === 1 ? "OK 1\n" : "FAIL\n";
} catch (Throwable $e) {
    echo "DB connection error: " . $e->getMessage() . "\n";
}