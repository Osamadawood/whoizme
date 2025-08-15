<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
// logout.php  (انسخ نفس الفكرة لـ /admin/logout.php)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* امسح كل السيشن */
$_SESSION = [];

/* امسح كوكي السيشن */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

/* دمّر السيشن وغيّر الـID */
session_destroy();
session_write_close();
session_id('');
session_regenerate_id(true);

/* امنع أي كاش للصفحة دي */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* رجّع حسب السياق */
$to = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '/admin/login.php' : '/login.php';
header("Location: $to");
exit;