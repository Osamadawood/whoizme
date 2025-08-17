<?php
declare(strict_types=1);

/**
 * Bootstrap خفيف:
 * - يبدأ السيشن
 * - يحمّل الكونفِج/الاتصال بقاعدة البيانات ($pdo)
 * - يعرّف أدوات auth بدون أي تحويلات تلقائية
 * - يعرّف require_login() فقط هي اللي بتحوّل
 */

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

// سيشن
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}

// BASE_URL (اختياري)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// تحميل الداتا بيز ($pdo) من أقرب ملف موجود
$pdo = $pdo ?? ($GLOBALS['pdo'] ?? null);
if (!$pdo instanceof PDO) {
    // جرّب includes/db.php -> app/db.php -> app/database.php
    $dbFiles = [
        __DIR__ . '/db.php',
        dirname(__DIR__) . '/app/db.php',
        dirname(__DIR__) . '/app/database.php',
    ];
    foreach ($dbFiles as $dbFile) {
        if (is_file($dbFile)) {
            require_once $dbFile;
            break;
        }
    }
    // بعد التحميل، لو لسه مش PDO، حاول تبني اتصال بسيط من config لو متاح
    if (!$pdo instanceof PDO) {
        $cfgPaths = [
            __DIR__ . '/config.php',
            dirname(__DIR__) . '/app/config.php',
        ];
        $CFG = [];
        foreach ($cfgPaths as $cfgFile) {
            if (is_file($cfgFile)) { $CFG = require $cfgFile; break; }
        }
        $dsn  = $CFG['db_dsn']  ?? '';
        $usr  = $CFG['db_user'] ?? '';
        $pass = $CFG['db_pass'] ?? '';
        if ($dsn) {
            try {
                $pdo = new PDO($dsn, $usr, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (Throwable $e) {
                // سيبها بدون كراش — do_login هيتعامل ويرجّع err=1
            }
        }
    }
}

// أدوات بسيطة
function current_user_id(): int {
    return (int)($_SESSION['uid'] ?? 0);
}
function is_logged_in(): bool {
    return current_user_id() > 0;
}

/**
 * الحارس الوحيد للتحويل (يُستخدم فقط في الصفحات اللي محتاجة لوجين)
 */
function require_login(): void {
    if (is_logged_in()) return;

    $req = (string)($_SERVER['REQUEST_URI'] ?? '/dashboard.php');

    // امنع صفحات اللوجين/المعالج كـ return
    $path   = (string)(parse_url($req, PHP_URL_PATH) ?? '');
    $blocked = ['', '/', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php'];
    $safe    = in_array($path, $blocked, true) ? '/dashboard.php' : $path;

    header('Location: /login.php?return=' . urlencode($safe));
    exit;
}