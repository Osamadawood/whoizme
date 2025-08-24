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
// Build rows from real tables for consistency with /links and /qr views
function load_top_links(PDO $pdo, int $uid): array {
  try {
    $hasClicksTable = false; $hasClicksCol = true;
    try { $t = $pdo->query("SHOW TABLES LIKE 'link_clicks'"); if ($t && $t->rowCount()>0) $hasClicksTable = true; } catch (Throwable $_) { $hasClicksTable=false; }
    try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'clicks'"); if (!$c || $c->rowCount()===0) $hasClicksCol = false; } catch (Throwable $_) { $hasClicksCol=false; }
    $totalExpr = $hasClicksCol ? 'COALESCE(l.clicks,0)' : ($hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id=l.id)' : '0');
    $todayExpr = $hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id=l.id AND DATE(lc.created_at)=CURDATE())' : '0';
    $q = "SELECT l.id AS item_id, l.title AS label, 'link' AS item_type,
                 $totalExpr AS total,
                 $todayExpr AS today,
                 l.created_at AS first_seen
          FROM links l WHERE l.user_id=:uid";
    $st = $pdo->prepare($q); $st->execute([':uid'=>$uid]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) { return []; }
}

function load_top_qr(PDO $pdo, int $uid): array {
  try {
    $q = "SELECT q.id AS item_id,
                 COALESCE(NULLIF(q.title,''), CONCAT('QR #', q.id)) AS label,
                 'qr' AS item_type,
                 (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS total,
                 (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id AND DATE(e.created_at)=CURDATE()) AS today,
                 q.created_at AS first_seen
          FROM qr_codes q WHERE q.user_id=:uid";
    $st = $pdo->prepare($q); $st->execute([':uid'=>$uid]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) { return []; }
}

// Load and merge according to tab
$arr = [];
if ($itemType === 'link') { $arr = load_top_links($pdo, $uid); }
elseif ($itemType === 'qr') { $arr = load_top_qr($pdo, $uid); }
else { $arr = array_merge(load_top_links($pdo, $uid), load_top_qr($pdo, $uid)); }

// Sort
usort($arr, function($a,$b) use ($sort,$dir){
  $cmp = 0;
  if ($sort === 'today') { $cmp = (int)($b['today']??0) <=> (int)($a['today']??0); }
  elseif ($sort === 'first_seen') { $cmp = strcmp((string)($a['first_seen']??''),(string)($b['first_seen']??'')); }
  else { $cmp = (int)($b['total']??0) <=> (int)($a['total']??0); }
  return strtolower($dir)==='asc' ? -$cmp : $cmp;
});

// Paging
$totalRows = count($arr);
$totalPages = (int)max(1, (int)ceil($totalRows / $per));
$offset = max(0, ($page-1) * $per);
$rows = array_slice($arr, $offset, $per);

// Total count via grouped subquery
// totalRows already computed above for merged list

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


