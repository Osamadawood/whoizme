<?php
declare(strict_types=1);

/**
 * includes/bootstrap.php
 * تهيئة موحّدة للتطبيق (سيشن + كونفيج + PDO + هيلبرز)
 * بدون أي HTML.
 */

/* =======================
   مسارات أساسية
   ======================= */
$ROOT = dirname(__DIR__);
$INC  = __DIR__;
$PUB  = $ROOT . '/public';

/* =======================
   تحميل الكونفيج
   نحاول أكثر من مسار (app/config.php ثم includes/config.php)
   ======================= */
$CFG = [];
$cfgCandidates = [
    $ROOT . '/app/config.php',
    $INC  . '/config.php',
];
foreach ($cfgCandidates as $cfgFile) {
    if (is_file($cfgFile)) {
        $cfg = require $cfgFile;
        if (is_array($cfg)) {
            $CFG = array_merge($CFG, $cfg);
        }
    }
}

/* قيم افتراضية آمنة */
$CFG += [
    'dev'      => true,
    'base_url' => '/',
    'db'       => [
        'driver'     => 'mysql',
        'host'       => '127.0.0.1',
        'port'       => 3306,
        'name'       => 'whoiz',
        'user'       => 'root',
        'pass'       => '',
        'charset'    => 'utf8mb4',
        'unix_socket'=> null,        // مثال: '/Applications/MAMP/tmp/mysql/mysql.sock'
        'options'    => [],          // PDO options extra
    ],
];

/* =======================
   بيئة عامة
   ======================= */
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}

/* وضع التطوير: إظهار الأخطاء وتسجيلها داخل المشروع إن وُجد */
if (!empty($CFG['dev'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    if (is_dir($ROOT . '/storage/logs')) {
        ini_set('error_log', $ROOT . '/storage/logs/php_errors.log');
    }
}

/* =======================
   ثوابت / هيلبرز بسيطة
   ======================= */
if (!defined('BASE_URL')) {
    define('BASE_URL', (string)($CFG['base_url'] ?? '/'));
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        return rtrim(BASE_URL, '/') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }
}

if (!function_exists('require_login')) {
    function require_login(): void {
        if (!current_user_id()) {
            $return = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login.php?return=' . rawurlencode($return), true, 302);
            exit;
        }
    }
}

/* =======================
   إنشاء اتصال PDO واحد ومشترك
   ======================= */
/** @var PDO $pdo */
$pdo = null;

(function () use (&$pdo, $CFG) {
    $db = $CFG['db'] ?? [];

    $driver     = (string)($db['driver']  ?? 'mysql');
    $host       = (string)($db['host']    ?? '127.0.0.1');
    $port       = (int)   ($db['port']    ?? 3306);
    $name       = (string)($db['name']    ?? 'whoiz');
    $user       = (string)($db['user']    ?? 'root');
    $pass       = (string)($db['pass']    ?? '');
    $charset    = (string)($db['charset'] ?? 'utf8mb4');
    $unixSocket = $db['unix_socket'] ?? null;

    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // دمج أي خيارات إضافية من الكونفيج
    if (!empty($db['options']) && is_array($db['options'])) {
        foreach ($db['options'] as $k => $v) {
            $pdoOptions[$k] = $v;
        }
    }

    $dsn = '';
    if ($driver === 'mysql') {
        if ($unixSocket) {
            // تفضيل الـ socket في بيئات MAMP/XAMPP لو متوفر
            $dsn = "mysql:unix_socket={$unixSocket};dbname={$name};charset={$charset}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        }
    } elseif ($driver === 'sqlite') {
        $path = $name; // لو name هو مسار ملف sqlite
        $dsn  = "sqlite:{$path}";
    } else {
        throw new RuntimeException("Unsupported DB driver: {$driver}");
    }

    $pdo = new PDO($dsn, $user, $pass, $pdoOptions);
})();

// نوفره أيضًا عبر الـGLOBALS للي بيستدعيه من سكوب مختلف
$GLOBALS['pdo'] = $pdo;

/* =======================
   تحميل ملفات اختيارية (لو موجودة)
   ======================= */
foreach ([$INC . '/helpers.php', $INC . '/lang.php', $INC . '/auth.php'] as $f) {
    if (is_file($f)) require_once $f;
}

/* =======================
   حارس المصادقة (افتراضيًا مفعّل)
   - أي صفحة عمومية لازم تعرّف SKIP_AUTH_GUARD قبل require للبووتستراب:
     define('SKIP_AUTH_GUARD', true);
   ======================= */
$skip = defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD === true;

// صفحات عامة شائعة حتى لو الحارس مفعّل
$publicWhitelist = [
    '/login.php', '/do_login.php', '/logout.php',
    '/register.php', '/forgot.php', '/reset.php',
    '/health.php', '/_selfcheck.php',
];

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (!$skip) {
    // لو مش صفحة عامة و المستخدم مش لوج إن → حوّل للّوج إن
    if (!in_array($reqPath, $publicWhitelist, true) && !current_user_id()) {
        header('Location: /login.php?return=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'), true, 302);
        exit;
    }
}

// انتهى — لا تطبّع أي شيء في الإخراج.