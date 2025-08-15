<?php
declare(strict_types=1);

/* ─── Paths ─────────────────────────────────────────────────────────────── */
$ROOT = dirname(__DIR__);
$INC  = __DIR__;
$PUB  = $ROOT . '/public';

/* ─── Config ────────────────────────────────────────────────────────────── */
$CFG = is_file($INC . '/config.php') ? require $INC . '/config.php' : [];
$CFG += ['base_url' => '/', 'dev' => true, 'auto_guard' => true];

/* ─── Env ───────────────────────────────────────────────────────────────── */
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

/* ─── Helpers ───────────────────────────────────────────────────────────── */
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim($CFG['base_url'] ?? '/', '/'));
}
function base_url(string $path = ''): string {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}
function redirect(string $to): never {
    header('Location: ' . (str_starts_with($to, 'http') ? $to : base_url($to)));
    exit;
}
if (!function_exists('current_user_id')) {
    function current_user_id(): int {
        return (int)($_SESSION['uid'] ?? 0);
    }
}

/* ─── DB ────────────────────────────────────────────────────────────────── */
require_once $INC . '/db.php';

/* ─── Optional includes ─────────────────────────────────────────────────── */
foreach ([$INC . '/auth.php', $INC . '/helpers.php', $INC . '/lang.php'] as $f) {
    if (is_file($f)) require_once $f;
}

/* ─── Guard (يمكن تعطيله بالكامل) ─────────────────────────────────────── */
/* 1) لو مُعرّف SKIP_AUTH_GUARD → ما تعملش أي فحص ولا تحويل */
if (defined('SKIP_AUTH_GUARD') && SKIP_AUTH_GUARD === true) {
    return;
}

/* 2) لو auto_guard = false → برضه ما تعملش حاجة */
if (empty($CFG['auto_guard'])) {
    return;
}

/* 3) الفحص العادي */
$CURRENT = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');

$PUBLIC_PAGES = [
    '', 'index.php',
    'login.php','register.php','forgot.php','reset.php',
    'privacy.php','terms.php','help.php',
    'health.php','_selfcheck.php',
    'r.php','u.php',
];

$isPublic   = in_array($CURRENT, $PUBLIC_PAGES, true);
$isLoggedIn = current_user_id() > 0;

// لو الصفحة خاصة والمستخدم غير مسجل → login
if (!$isPublic && !$isLoggedIn) {
    redirect('/login.php');
}

// لو هو في login/register وهو مسجل بالفعل → dashboard
if ($isPublic && $isLoggedIn && in_array($CURRENT, ['login.php','register.php'], true)) {
    redirect('/dashboard.php');
}