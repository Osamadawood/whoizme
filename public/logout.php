<?php define('PUBLIC_PAGE', true); ?>
<?php
declare(strict_types=1);
if (!defined('SKIP_AUTH_GUARD')) define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: /login.php');
exit;
