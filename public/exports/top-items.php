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
// Sorting/paging for export (slice)
$sort = $_GET['sort'] ?? 'total';
$dir  = $_GET['dir'] ?? 'desc';
$page = (int)($_GET['page'] ?? 1);
$per  = (int)($_GET['per'] ?? 200);
if (!in_array($sort, ['total','today','first_seen'], true)) $sort = 'total';
if (!in_array(strtolower($dir), ['asc','desc'], true)) $dir = 'desc';
if ($page < 1) $page = 1;
if ($per < 1 || $per > 200) $per = 200;

$rows = isset($pdo) ? wz_top_items($pdo, $uid, $t_param, $p, $per, $sort, $dir, $page, $per) : [];

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


