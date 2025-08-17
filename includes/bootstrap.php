<?php
declare(strict_types=1);

/**
 * Bootstrap – يبدأ السيشن ويحمّل الـ helpers و auth
 * مهم: الملف دا لازم يتضمّن أول سطر في كل صفحة PHP عامة/لوحة.
 */

# ابدأ السيشن بأمان إن ماكنش شغال
if (session_status() !== PHP_SESSION_ACTIVE) {
    // SameSite=Lax كويس لصفحات auth
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

# متغير base اختياري لو بتحتاجه في الروابط
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base === '.' || $base === '\\') { $base = ''; }

# لو عندك إعدادات/اتصال DB حمّلها هنا (اختياري)
$cfgFile = __DIR__ . '/config.php';
if (is_file($cfgFile)) { require_once $cfgFile; }
$dbFile  = __DIR__ . '/db.php';
if (is_file($dbFile))  { require_once $dbFile; } // يُعرّف $pdo لو موجود

# Helpers بسيطة
if (!function_exists('url')) {
    function url(string $path = '/', array $qs = []): string {
        $q = $qs ? ('?' . http_build_query($qs)) : '';
        return $path . $q;
    }
}

# حمّل دوال المصادقة (مرة واحدة فقط)
require_once __DIR__ . '/auth.php';