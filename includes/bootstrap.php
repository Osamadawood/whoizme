<?php
declare(strict_types=1);

// Paths
$ROOT = dirname(__DIR__);
$INC  = __DIR__;
$PUB  = $ROOT . '/public';

// Load config (bridge -> app/config.php)
$CFG = require $INC . '/config.php';

// Env basics
mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('whoizme_sess');
    session_start();
}

// Error reporting in dev
if (!empty($CFG['dev'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
    // اختياري: سجل الأخطاء داخل المشروع
    if (is_dir($ROOT . '/storage/logs')) {
        ini_set('error_log', $ROOT . '/storage/logs/php_errors.log');
    }
}

// Constants & helpers
if (!defined('BASE_URL')) {
    define('BASE_URL', $CFG['base_url'] ?? '/');
}

// DB (PDO with socket-first fallback is inside this file)
require_once $INC . '/db.php';

// Optional includes if they exist
foreach ([$INC . '/auth.php', $INC . '/helpers.php', $INC . '/lang.php'] as $f) {
    if (is_file($f)) require_once $f;
}

// Tiny helpers used across pages
function base_url(string $path = ''): string {
    return rtrim(BASE_URL, '/') . ($path ? '/' . ltrim($path, '/') : '');
}

function current_user_id(): int {
    return (int)($_SESSION['uid'] ?? 0);
}

function require_login(): void {
    if (!current_user_id()) {
        header('Location: /login.php');
        exit;
    }
}