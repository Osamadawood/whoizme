<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

echo "SELF-CHECK\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "BASE_URL: " . (BASE_URL ?? 'n/a') . "\n";

// Session rw
$_SESSION['__probe'] = ($_SESSION['__probe'] ?? 0) + 1;
echo "SESSION: OK (hit=" . (int)$_SESSION['__probe'] . ")\n";

// DB
try {
    $pdo = db();
    $one = $pdo->query("SELECT 1 AS ok")->fetch();
    echo "DB: OK (" . ($one['ok'] ?? '?') . ")\n";
} catch (Throwable $e) {
    echo "DB: ERROR -> " . $e->getMessage() . "\n";
}

// Paths
echo "ROOT: $ROOT\nINC: $INC\nPUB: $PUB\n";

// Optional auth check (won't redirect here)
echo "UID: " . current_user_id() . "\n";

echo "DONE.\n";