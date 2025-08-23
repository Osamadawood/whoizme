<?php
declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/events.php';

// Debug on local
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Optional bootstrap (load if present)
$__bootstrap = __DIR__ . '/../../app/bootstrap.php';
if (is_file($__bootstrap)) {
    require_once $__bootstrap;
}
if (is_file(__DIR__ . '/../../app/config.php')) {
    $config = require __DIR__ . '/../../app/config.php';
}

// استقبل الكود من الـquery
$code = isset($_GET['c']) ? trim($_GET['c']) : '';
if ($code === '') {
    http_response_code(400);
    echo 'Missing code';
    exit;
}

// Use existing PDO from bootstrap
/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database unavailable');
}

// لو طالب debug اعرض تفاصيل
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "DEBUG MODE\n";
    echo "code = {$code}\n";
}
// Prevent caching debug output
if (isset($_GET['debug'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// هات السجل من جدول الروابط المختصرة (أسماء الأعمدة قد تختلف بين المشاريع)
$stmt = $pdo->prepare('SELECT * FROM short_links WHERE code = :c LIMIT 1');
$stmt->execute([':c' => $code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo 'Short link not found';
    if (isset($_GET['debug'])) {
        echo "\n(no row for code)";
    }
    exit;
}

// استخرج الهدف مهما كان اسم العمود عندك
$target = $row['target_url']
    ?? $row['target']
    ?? $row['url']
    ?? $row['link']
    ?? $row['destination']
    ?? null;

if (!$target) {
    http_response_code(500);
    echo 'Short link record exists but no target/url column was found.';
    if (isset($_GET['debug'])) {
        echo "\n(available columns): " . implode(', ', array_keys($row));
    }
    exit;
}

// سجّل سحب/زيارة (اختياري)
try {
    $ins = $pdo->prepare('INSERT INTO short_link_hits (code, created_at, ip, ua, ref) VALUES (:c, NOW(), :ip, :ua, :ref)');
    $ins->execute([
        ':c' => $code,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ':ref'=> $_SERVER['HTTP_REFERER'] ?? '',
    ]);
} catch (\Throwable $e) {
    // تجاهل خطأ التسجيل حتى لا يعطّل التحويل
    if (isset($_GET['debug'])) {
        echo "\n(hit-log error) ".$e->getMessage()."\n";
    }
}

// Log unified analytics event (non-blocking). For this route, treat as page open.
try {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $ua  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($uid > 0 && $ua && !preg_match('/bot|spider/i', $ua)) {
        $itemId = (int)($row['id'] ?? 0);
        $slug = (string)($row['slug'] ?? $row['code'] ?? '');
        $label = $slug ? ('os.me/'.ltrim($slug,'/')) : ((string)($row['title'] ?? $row['name'] ?? 'demo page'));
        wz_log_event($pdo, $uid, 'page', $itemId, 'open', $label);
    }
} catch (Throwable $e) {
    // ignore
}

// Build a clean, single 302 to target URL
// 1) Never echo .php in internal routes; 2) Avoid nested encoding of return params.
function clean_target(string $url): string {
    // If it looks like an internal route, strip .php suffixes
    $u = parse_url($url);
    if (!isset($u['host'])) { // relative/internal
        $path = $u['path'] ?? '/';
        $path = preg_replace('~\.php$~i', '', $path);
        // sanitize return param once if present
        $qs = [];
        if (!empty($u['query'])) parse_str($u['query'], $qs);
        if (isset($qs['return'])) {
            $ret = $qs['return'];
            if (!wz_is_safe_next($ret)) unset($qs['return']);
            else $qs['return'] = preg_replace('~\.php($|\?)~i', '$1', $ret);
        }
        $url = wz_url($path, $qs);
    }
    return $url;
}

$location = clean_target($target);

// prevent caching so each hit is recorded, but do not chain redirects
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Location: ' . $location, true, 302);
exit;