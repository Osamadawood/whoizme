<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/analytics/params.php';

header('Content-Type: application/json; charset=utf-8');

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'db']); exit; }

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }

function norm_date_r(?string $s, string $fallback): string { $d = $s?DateTime::createFromFormat('Y-m-d', substr($s,0,10)):null; return $d?$d->format('Y-m-d'):$fallback; }
$from = ana_normalize_date($_GET['from'] ?? null, date('Y-m-d', strtotime('-30 days')));
$to   = ana_normalize_date($_GET['to']   ?? null, date('Y-m-d'));
$scope = $_GET['scope'] ?? 'all'; if (!in_array($scope,['all','links','qrs'],true)) $scope='all';
$limit = (int)($_GET['limit'] ?? 10); if ($limit < 3) $limit = 3; if ($limit > 25) $limit = 25;
try { ana_window_guard($from, $to, 365); } catch (Throwable $e) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_range']); exit; }

try {
  $rows = [];
  $hasLC = ana_table_exists($pdo,'link_clicks');
  $hasQS = ana_table_exists($pdo,'qr_scans');
  $hasL  = ana_table_exists($pdo,'links');
  $hasQ  = ana_table_exists($pdo,'qr_codes');
  $hasLinks = $hasLC && $hasL; $hasQrs = $hasQS && $hasQ;
  $lcRef = $hasLC ? (ana_pick_ref($pdo,'link_clicks') ?: 'referrer') : null;
  $qsRef = $hasQS ? (ana_pick_ref($pdo,'qr_scans')   ?: 'referrer') : null;
  $lcTs  = $hasLC ? (ana_pick_created($pdo,'link_clicks') ?: 'created_at') : null;
  $qsTs  = $hasQS ? (ana_pick_created($pdo,'qr_scans')   ?: 'created_at') : null;
  $norm = function(string $col){ return "COALESCE(NULLIF(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX($col,'/',3),'//',-1)) ,''),'Direct')"; };
  $fromTs = $from.' 00:00:00'; $toTs = $to.' 23:59:59';
  if (($scope==='all'||$scope==='links') && $hasLinks && $lcRef && $lcTs) {
    $sql = "SELECT ".$norm('lc.`'.$lcRef.'`')." AS h, COUNT(*) AS c
            FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id
            WHERE l.user_id=:uid AND lc.`{$lcTs}` BETWEEN :fromTs AND :toTs
            GROUP BY h";
    $st=$pdo->prepare($sql); $st->execute([':uid'=>$uid,':fromTs'=>$fromTs,':toTs'=>$toTs]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $rows[$r['h']] = ($rows[$r['h']] ?? 0) + (int)$r['c']; }
  }
  if (($scope==='all'||$scope==='qrs') && $hasQrs && $qsRef && $qsTs) {
    $sql = "SELECT ".$norm('qs.`'.$qsRef.'`')." AS h, COUNT(*) AS c
            FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id
            WHERE q.user_id=:uid AND qs.`{$qsTs}` BETWEEN :fromTs AND :toTs
            GROUP BY h";
    $st=$pdo->prepare($sql); $st->execute([':uid'=>$uid,':fromTs'=>$fromTs,':toTs'=>$toTs]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $rows[$r['h']] = ($rows[$r['h']] ?? 0) + (int)$r['c']; }
  }

  arsort($rows);
  $top = array_slice($rows, 0, $limit-1, true);
  $other = array_sum(array_slice($rows, $limit-1, null, true));
  $series = [];
  foreach ($top as $k=>$v) $series[] = [ $k, (int)$v ];
  if ($other > 0) $series[] = ['Other', (int)$other];

  echo json_encode(['ok'=>true,'series'=>$series], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'server']); }
exit;


