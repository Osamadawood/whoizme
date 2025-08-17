<?php
declare(strict_types=1);
define('SKIP_AUTH_GUARD', true);
define('PAGE_PUBLIC', true);
require dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
echo "SID: ", session_id(), "\n";
echo "Logged in? ", is_logged_in() ? 'YES' : 'NO', "\n";
echo "UID: ", current_user_id(), "\n";
echo "Cookies: ", json_encode($_COOKIE, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), "\n";