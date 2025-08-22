<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }

$limit = (int)($_GET['limit'] ?? 6);
if ($limit < 1) $limit = 1; if ($limit > 20) $limit = 20;

/** @var PDO $pdo */
$items = wz_recent_activity($pdo, $uid, $limit);
echo json_encode(['items'=>$items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;


