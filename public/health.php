<?php
// اسمح بتخطّي الجارد في هذه الصفحة
define('SKIP_AUTH_GUARD', true);

// حمّل البوتستراب العام
require_once __DIR__ . '/_bootstrap.php';

// مخرجات نصية بسيطة
header('Content-Type: text/plain; charset=utf-8');

try {
    $x = db()->query("SELECT 1")->fetchColumn();
    echo "OK " . (int)$x;
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERR: " . $e->getMessage();
}