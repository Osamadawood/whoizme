<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }

$p = $_GET['p'] ?? '7d';
if (!in_array($p, ['7d','30d','90d'], true)) $p = '7d';

/** @var PDO $pdo */
try {
    $items = wz_featured_items($pdo, $uid, $p, 4);
    echo json_encode(['period'=>$p, 'items'=>$items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error'=>'server']);
}
exit;


