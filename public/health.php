<?php
define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

try {
    $pdo = db();
    $ok = (int) $pdo->query('SELECT 1')->fetchColumn();
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK {$ok}\n";
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "DB connection error: " . $e->getMessage();
}