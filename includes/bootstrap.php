<?php
// تشغيل السيشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تفعيل عرض الأخطاء في وضع التطوير
if (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// تحديد المسارات الأساسية
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// تحميل الاتصال بالداتا بيز
require_once INCLUDES_PATH . '/db.php';

// تحميل ملف اللغة
require_once INCLUDES_PATH . '/lang.php';

// تحميل أي وظائف أو هيلبرز إضافية
if (file_exists(APP_PATH . '/helpers.php')) {
    require_once APP_PATH . '/helpers.php';
}

// تحميل أي دوال أو كلاسات أخرى ضرورية
if (file_exists(APP_PATH . '/functions/track_hit.php')) {
    require_once APP_PATH . '/functions/track_hit.php';
}

// التحقق من تسجيل دخول المستخدم (لصفحات تتطلب تسجيل دخول)
function require_login()
{
    if (empty($_SESSION['uid'])) {
        header('Location: /login.php');
        exit;
    }
}

// الحصول على معرف المستخدم الحالي
function current_user_id(): int
{
    return (int)($_SESSION['uid'] ?? 0);
}