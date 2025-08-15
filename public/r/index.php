<?php
// public/r/index.php

// Debug on local
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Optional bootstrap (load if present)
$__bootstrap = __DIR__ . '/../../app/bootstrap.php';
if (is_file($__bootstrap)) {
    require_once $__bootstrap;
}
$config = require __DIR__ . '/../../app/config.php';

// استقبل الكود من الـquery
$code = isset($_GET['c']) ? trim($_GET['c']) : '';
if ($code === '') {
    http_response_code(400);
    echo 'Missing code';
    exit;
}

// اتصال PDO (عدّل DSN/اليوزر/الباسورد حسب مشروعك)
$pdo = new PDO('mysql:host=localhost;port=8889;dbname=whoiz;charset=utf8mb4', 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

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

// حوّل (prevent caching the 302 so each hit is recorded)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');
header('Location: ' . $target, true, 302);
exit;