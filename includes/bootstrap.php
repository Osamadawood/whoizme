<?php
declare(strict_types=1);

/**
 * includes/bootstrap.php
 * تهيئة موحّدة للتطبيق (سيشن + كونفيج + PDO + هيلبرز) — بدون أي HTML.
 */

$ROOT = dirname(__DIR__);
$INC  = __DIR__;
$PUB  = $ROOT . '/public';

/* -----------------------
   تحميل الكونفيج
------------------------ */
$CFG = [];
foreach ([$ROOT . '/app/config.php', $INC . '/config.php'] as $cfgFile) {
    if (is_file($cfgFile)) {
        $cfg = require $cfgFile;
        if (is_array($cfg)) $CFG = array_merge($CFG, $cfg);
    }
}
$CFG += [
    'dev'      => true,
    'base_url' => '/',
    'db'       => [
        'driver'      => 'mysql',
        'host'        => '127.0.0.1',
        'port'        => 3306,
        'name'        => 'whoiz',
        'user'        => 'root',
        'pass'        => '',
        'charset'     => 'utf8mb4',
        'unix_socket' => null,
        'options'     => [],
    ],
];

/* -----------------------
   بيئة عامة + أخطاء
------------------------ */
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}
if (!empty($CFG['dev'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    if (is_dir($ROOT . '/storage/logs')) {
        ini_set('error_log', $ROOT . '/storage/logs/php_errors.log');
    }
}

/* -----------------------
   ثوابت وهيلبرز
------------------------ */
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

/* -----------------------
   اتصال PDO واحد مشترك
------------------------ */
/** @var PDO $pdo */
$pdo = null;
try {
    $db        = $CFG['db'] ?? [];
    $driver    = (string)($db['driver']  ?? 'mysql');
    $host      = (string)($db['host']    ?? '127.0.0.1');
    $port      = (int)   ($db['port']    ?? 3306);
    $name      = (string)($db['name']    ?? 'whoiz');
    $user      = (string)($db['user']    ?? 'root');
    $pass      = (string)($db['pass']    ?? '');
    $charset   = (string)($db['charset'] ?? 'utf8mb4');
    $sock      = $db['unix_socket'] ?? null;

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (!empty($db['options']) && is_array($db['options'])) {
        foreach ($db['options'] as $k => $v) $opts[$k] = $v;
    }

    if ($driver === 'mysql') {
        $dsn = $sock
            ? "mysql:unix_socket={$sock};dbname={$name};charset={$charset}"
            : "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    } elseif ($driver === 'sqlite') {
        $dsn = "sqlite:{$name}";
    } else {
        throw new RuntimeException("Unsupported DB driver: {$driver}");
    }
    $pdo = new PDO($dsn, $user, $pass, $opts);
} catch (Throwable $e) {
    error_log('DB bootstrap failed: ' . $e->getMessage());
    if (!empty($CFG['dev'])) {
        http_response_code(500);
        die('Database connection error. Check config/DB.');
    }
    http_response_code(500);
    exit;
}
$GLOBALS['pdo'] = $pdo;

/* -----------------------
   تحميل auth & helpers إن وُجد
------------------------ */
foreach ([$INC . '/helpers.php', $INC . '/lang.php', $INC . '/auth.php'] as $f) {
    if (is_file($f)) require_once $f;
}

/* -----------------------
   حارس المصادقة
   - لتعطيله في صفحة: define('SKIP_AUTH_GUARD', true) قبل require
------------------------ */
$skip = defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD === true;

$publicWhitelist = [
    '/', '/index.php',
    '/login', '/login.php', '/do_login', '/do_login.php',
    '/logout', '/logout.php',
    '/register', '/register.php',
    '/forgot-password', '/forgot.php',
    '/reset-password', '/reset.php',
    '/terms', '/terms.php',
    '/privacy', '/privacy.php',
    '/styleguide', '/styleguide.php',
    '/health', '/health.php', '/_selfcheck.php',
];

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (!$skip) {
    if (!in_array($reqPath, $publicWhitelist, true) && !current_user_id()) {
        $here = $_SERVER['REQUEST_URI'] ?? '/dashboard';
        $here = preg_replace('~\.php($|\?)~i', '$1', $here);
        $qpos = strpos($here, '?');
        $cleanHere = $qpos === false ? $here : substr($here, 0, $qpos);
        $loginUrl = function_exists('wz_url') ? wz_url('/login', ['return' => $cleanHere]) : '/login?return=' . rawurlencode($cleanHere);
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }
}