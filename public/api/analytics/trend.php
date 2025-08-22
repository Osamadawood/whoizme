<?php
declare(strict_types=1);

// Trend JSON endpoint: returns daily totals for 7d/30d/90d for the current user

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/events.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$p = $_GET['p'] ?? '7d';
$p = in_array($p, ['7d','30d','90d'], true) ? $p : '7d';

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
  http_response_code(401);
  echo json_encode(['error' => 'unauthorized']);
  exit;
}

/** @var PDO $pdo */
$series = isset($pdo) ? wz_event_series($pdo, $uid, $p) : [];

// Reduce to {date,total}
$days = [];
foreach ($series as $row) {
  $days[] = [
    'date'  => (string)($row['date'] ?? ''),
    'total' => (int)($row['total'] ?? 0),
  ];
}

echo json_encode([
  'period' => $p,
  'days'   => $days,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;


