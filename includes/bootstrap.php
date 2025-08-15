<?php
declare(strict_types=1);

/**
 * whoizme – bootstrap (safe auth/redirects)
 * - توحيد قراءة الإعدادات
 * - إعداد السيشن بشكل مضبوط
 * - أوتو-جارْد يمنع الـ redirect loop
 * - أدوات مساعدة خفيفة
 */

/* -------- Paths -------- */
$ROOT = dirname(__DIR__);       // project root
$INC  = __DIR__;                // /includes
$PUB  = $ROOT . '/public';      // /public (للمعلومية فقط)

/* -------- Config -------- */
$CFG = require $INC . '/config.php';   // جسر إلى app/config.php
$DEV = !empty($CFG['dev']);

/* -------- Encoding / TZ -------- */
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

/* -------- Session (بإعدادات كوكي سليمة) -------- */
if (session_status() !== PHP_SESSION_ACTIVE) {
    // استنتاج الأمان من الـ base_url (https أم http)
    $isHttps = false;
    if (!empty($CFG['base_url'])) {
        $u = parse_url($CFG['base_url']);
        $isHttps = isset($u['scheme']) && strtolower($u['scheme']) === 'https';
    }
    session_name('whoizme_sess');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',     // لو بتستعمل دومين فرعي ثبّت الدومين هنا
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* -------- Errors in dev -------- */
if ($DEV) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    if (is_dir($ROOT . '/storage/logs')) {
        ini_set('error_log', $ROOT . '/storage/logs/php_errors.log');
    }
}

/* -------- Constants & helpers -------- */
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($CFG['base_url'] ?? '/', '/'));
}

function base_url(string $path = ''): string {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function redirect(string $to): never {
    // يقبل مسار نسبي أو مطلق
    if (str_starts_with($to, 'http')) {
        header('Location: ' . $to);
    } else {
        header('Location: ' . base_url($to));
    }
    exit;
}

function current_user_id(): int {
    return (int)($_SESSION['uid'] ?? 0);
}

/* -------- DB (PDO) -------- */
require_once $INC . '/db.php';

/* -------- Optional includes -------- */
foreach ([$INC . '/auth.php', $INC . '/helpers.php', $INC . '/lang.php'] as $f) {
    if (is_file($f)) require_once $f;
}

/* -------- Safe Auto‑Guard --------
   يمنع الـ redirect loop ويفصل بين صفحات عامة وخاصة.
   لو حابب توقفه تمامًا خلّي $CFG['auto_guard'] = false
--------------------------------------------------- */
$autoGuard = $CFG['auto_guard'] ?? true;
if ($autoGuard) {
    // حدّد اسم الملف الحالي (بدون مسار)
    $CURRENT = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');

    // صفحات عامة لا تتطلب تسجيل دخول
    $PUBLIC_PAGES = [
        '',                 // index لو السيرفر بيخدم / مباشرة
        'index.php',
        'login.php',
        'register.php',
        'forgot.php',
        'reset.php',
        'privacy.php',
        'terms.php',
        'help.php',
        'health.php',
        '_selfcheck.php',
        'r.php',            // redirector
        'u.php',            // public profiles
    ];

    $isPublic   = in_array($CURRENT, $PUBLIC_PAGES, true);
    $isLoggedIn = current_user_id() > 0;

    // 1) لو الصفحة ليست عامة والمستخدم غير مسجل → ودّيه لصفحة الدخول
    if (!$isPublic && !$isLoggedIn) {
        redirect('/login.php');
    }

    // 2) لو هو في صفحة login/register وهو أصلاً مسجل → ودّيه للداشبورد
    if ($isPublic && $isLoggedIn && in_array($CURRENT, ['login.php','register.php'], true)) {
        redirect('/dashboard.php');
    }
}

/* -------- Manual guard (للاستخدام اليدوي داخل الصفحة)
   استعملها في أي صفحة خاصة بدل ما تعتمد على الأوتو-جارْد لو حبيت
--------------------------------------------------- */
function require_login(): void {
    if (!current_user_id()) {
        redirect('/login.php');
    }
}