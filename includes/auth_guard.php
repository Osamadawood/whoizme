<?php
// includes/auth_guard.php
declare(strict_types=1);

// لازم يكون bootstrap متحمّل قبل الجارد (عشان السيشن و الـ helpers)
if (!isset($_SESSION)) {
    session_name('whoizme_sess');
    session_start();
}

if (empty($_SESSION['uid'])) {
    // رجّع المستخدم للوجين مع return = الصفحة الحالية
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $ret = $uri !== '' ? '?return=' . urlencode($uri) : '';
    header('Location: /login.php' . $ret);
    exit;
}