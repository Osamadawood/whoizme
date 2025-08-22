<?php
declare(strict_types=1);

/**
 * Dev-only: add a demo event for quick QA of trend/top
 * Params: ?type=click|scan|open|create&what=link|qr|page&id=10&label=foo
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/events.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Optional admin check if available
$isAdmin = false;
if (function_exists('admin_can')) {
  $isAdmin = admin_can('*') || admin_can('links.edit') || !empty($_SESSION['is_super']);
} else {
  // Fall back to simple guard: only allow in dev
  $isAdmin = !empty($GLOBALS['CFG']['dev']);
}

if (!$isAdmin) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }

$type = strtolower(trim((string)($_GET['type'] ?? 'click')));
$what = strtolower(trim((string)($_GET['what'] ?? 'link')));
$id   = (int)($_GET['id'] ?? 0);
$label= (string)($_GET['label'] ?? '');

if (!in_array($type, ['click','scan','open','create'], true)) $type = 'click';
if (!in_array($what, ['link','qr','page'], true)) $what = 'link';
if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad id']); exit; }

try {
  /** @var PDO $pdo */
  if (!isset($pdo) || !($pdo instanceof PDO)) throw new RuntimeException('DB unavailable');
  wz_log_event($pdo, $uid, $what, $id, $type, $label ?: ($what.' #'.$id));
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'exception']);
}


