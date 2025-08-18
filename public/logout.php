<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', !empty($params['secure']), !empty($params['httponly']));
}
session_destroy();

header('Location: /login.php', true, 302);
exit;