<?php
// اسمح بتخطّي الجارد في هذه الصفحة
define('SKIP_AUTH_GUARD', true);

// حمّل البوتستراب العام
require_once __DIR__ . '/_bootstrap.php';

// مخرجات واضحة من غير HTML
header('Content-Type: text/plain; charset=utf-8');

// معلومات أساسية
echo "SELF-CHECK\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'n/a') . "\n";

// اختبار السيشن (يزيد كل رفريش)
$_SESSION['__probe'] = (int)(($_SESSION['__probe'] ?? 0) + 1);
echo "SESSION: OK (hit=" . $_SESSION['__probe'] . ")\n";

// اختبار الداتا بيز
try {
    $pdo = db();
    $val = $pdo->query("SELECT 1 AS ok")->fetch();
    echo "DB: OK (" . (int)($val['ok'] ?? 0) . ")\n";
} catch (Throwable $e) {
    echo "DB: ERROR -> " . $e->getMessage() . "\n";
}

// حالة المستخدم
echo "UID: " . (int)($_SESSION['uid'] ?? 0) . "\n";
echo "DONE.\n";