<?php
define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "SELF-CHECK\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "BASE_URL: " . (BASE_URL ?: '/') . "\n";
echo "SESSION: OK\n";

try {
    $ok = (int) db()->query('SELECT 1')->fetchColumn();
    echo "DB: OK ({$ok})\n";
} catch (Throwable $e) {
    echo "DB: FAIL " . $e->getMessage() . "\n";
}

echo "UID: " . current_user_id() . "\n";
echo "DONE.\n";