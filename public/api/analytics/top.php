<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/events.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }

$p    = $_GET['p']    ?? '7d';
$tab  = $_GET['tab']  ?? ($_GET['t'] ?? 'all');
$page = (int)($_GET['page'] ?? 1);
$per  = (int)($_GET['per']  ?? 5);
$sort = $_GET['sort'] ?? 'total';
$dir  = $_GET['dir']  ?? 'desc';

if (!in_array($p, ['7d','30d','90d'], true)) $p = '7d';
if (!in_array($tab, ['all','links','qr','pages'], true)) $tab = 'all';
if ($page < 1) $page = 1;
if ($per < 1) $per = 5; if ($per > 10) $per = 10;
if (!in_array($sort, ['total','today','first_seen'], true)) $sort = 'total';
if (!in_array(strtolower($dir), ['asc','desc'], true)) $dir = 'desc';

$map = ['all'=>'all','links'=>'link','qr'=>'qr','pages'=>'page'];
$itemType = $map[$tab] ?? 'all';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo json_encode(['error'=>'db']); exit; }

// Data slice
$rows = wz_top_items($pdo, $uid, $itemType, $p, $per, $sort, $dir, $page, $per);

// Total count via grouped subquery
$since = (new DateTime('-'.($p==='30d'?30:($p==='90d'?90:7)).' days'))->format('Y-m-d 00:00:00');
$q = "SELECT COUNT(1) FROM (
        SELECT item_type,item_id
        FROM events
        WHERE user_id=:uid AND created_at>=:since" . ($itemType!=='all' ? " AND item_type=:it" : "") .
      " GROUP BY item_type,item_id) sub";
$st = $pdo->prepare($q);
$st->bindValue(':uid', $uid, PDO::PARAM_INT);
$st->bindValue(':since', $since);
if ($itemType!=='all') $st->bindValue(':it', $itemType);
$st->execute();
$totalRows = (int)$st->fetchColumn();
$totalPages = (int)max(1, (int)ceil($totalRows / $per));

// Normalize row output
$outRows = [];
foreach ($rows as $r) {
  $outRows[] = [
    'title'      => (string)($r['label'] ?? ''),
    'type'       => strtoupper((string)($r['item_type'] ?? '')),
    'total'      => (int)($r['total'] ?? 0),
    'today'      => (int)($r['today'] ?? 0),
    'first_seen' => !empty($r['first_seen']) ? date('Y-m-d', strtotime((string)$r['first_seen'])) : null,
  ];
}

echo json_encode([
  'rows'   => $outRows,
  'paging' => [
    'page'        => $page,
    'per'         => $per,
    'total_pages' => $totalPages,
    'total_rows'  => $totalRows,
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;


