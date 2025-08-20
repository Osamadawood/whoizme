<?php
// includes/toggle_locale.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$to = isset($_GET['to']) ? strtolower($_GET['to']) : '';
$allowed = ['en','ar'];
if (!in_array($to, $allowed, true)) {
    // لو طلب غير صالح نرجع للغة الحالية أو EN
    $to = ($_SESSION['locale'] ?? 'en');
}

// خزّن في السيشن + كوكي لسنة
$_SESSION['locale'] = $to;
setcookie('whoizme_locale', $to, [
    'expires' => time() + 365*24*3600,
    'path'    => '/',
    'httponly'=> false,
    'samesite'=> 'Lax'
]);

// رجّع المستخدم لنفس الصفحة
$back = $_SERVER['HTTP_REFERER'] ?? '/dashboard.php';
header('Location: ' . $back);
exit;