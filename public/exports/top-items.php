<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/events.php';

// Params (preserve mapping with dashboard)
$p = $_GET['p'] ?? '7d';
$p = in_array($p, ['7d','30d','90d'], true) ? $p : '7d';

$t = $_GET['t'] ?? 'all';
$t = in_array($t, ['all','links','qr','pages'], true) ? $t : 'all';
$t_param = [
  'all'   => 'all',
  'links' => 'link',
  'qr'    => 'qr',
  'pages' => 'page',
][$t];

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) {
  http_response_code(403);
  exit('Forbidden');
}

/** @var PDO $pdo */
$rows = isset($pdo) ? wz_top_items($pdo, $uid, $t_param, $p, 200) : [];

// Output CSV with UTF-8 BOM
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="top-items.csv"');
echo "\xEF\xBB\xBF"; // BOM

$out = fopen('php://output', 'w');
fputcsv($out, ['Title','Type','Total','Today','FirstSeen']);

foreach ($rows as $r) {
  $title = (string)($r['label'] ?? '');
  $type  = strtoupper((string)($r['item_type'] ?? ''));
  $total = (int)($r['total'] ?? 0);
  $today = (int)($r['today'] ?? 0);
  $first = !empty($r['first_seen']) ? date('Y-m-d H:i:s', strtotime((string)$r['first_seen'])) : '';
  fputcsv($out, [$title, $type, $total, $today, $first]);
}

fclose($out);
exit;


