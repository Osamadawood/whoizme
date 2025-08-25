<?php
declare(strict_types=1);
// DEV-only: clear seeded analytics rows (referrer LIKE 'seed://%')

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'db']); exit; }

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }

function is_local_env(): bool {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
  if (preg_match('~(localhost|\.local)(:\\d+)?$~i', $host)) return true;
  return $ip === '127.0.0.1' || $ip === '::1';
}
if (!is_local_env() || ($_GET['token'] ?? '') !== 'DEV') { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

$scope = in_array(($_GET['scope'] ?? 'all'), ['all','links','qrs'], true) ? $_GET['scope'] : 'all';

$deletedLC = 0; $deletedQS = 0;
try {
  if ($scope==='all' || $scope==='links') {
    $st = $pdo->prepare("DELETE FROM link_clicks WHERE referrer LIKE 'seed://%' ");
    $st->execute(); $deletedLC = $st->rowCount();
  }
  if ($scope==='all' || $scope==='qrs') {
    $st = $pdo->prepare("DELETE FROM qr_scans WHERE referrer LIKE 'seed://%' ");
    $st->execute(); $deletedQS = $st->rowCount();
  }
} catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'server']); exit; }

echo json_encode(['ok'=>true,'deleted'=>['link_clicks'=>$deletedLC,'qr_scans'=>$deletedQS],'scope'=>$scope]);
exit;


