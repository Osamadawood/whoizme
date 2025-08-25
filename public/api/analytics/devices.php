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

function norm_date_d(?string $s, string $fallback): string { $d = $s?DateTime::createFromFormat('Y-m-d', substr($s,0,10)):null; return $d?$d->format('Y-m-d'):$fallback; }
$from = ana_normalize_date($_GET['from'] ?? null, date('Y-m-d', strtotime('-30 days')));
$to   = ana_normalize_date($_GET['to']   ?? null, date('Y-m-d'));
$scope = $_GET['scope'] ?? 'all'; if (!in_array($scope,['all','links','qrs'],true)) $scope='all';
// New filters
$deviceCsv = isset($_GET['device']) ? strtolower(trim((string)$_GET['device'])) : '';
$refFilter = isset($_GET['ref']) ? trim((string)$_GET['ref']) : '';
try { ana_window_guard($from, $to, 365); } catch (Throwable $e) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_range']); exit; }

$map = function($v){ $v = strtolower((string)$v); if (in_array($v,['desktop','mobile','tablet','unknown'],true)) return ucfirst($v); return 'Unknown'; };

try {
  $series = ['Desktop'=>0,'Mobile'=>0,'Tablet'=>0,'Unknown'=>0];
  $hasLC = ana_table_exists($pdo,'link_clicks');
  $hasQS = ana_table_exists($pdo,'qr_scans');
  $hasL  = ana_table_exists($pdo,'links');
  $hasQ  = ana_table_exists($pdo,'qr_codes');
  $fromTs = $from.' 00:00:00'; $toTs = $to.' 23:59:59';
  $allowed = ['desktop','mobile','tablet','unknown'];
  $deviceList = array_values(array_filter(array_map('trim', $deviceCsv!==''?explode(',', $deviceCsv):[]), function($d) use($allowed){ return in_array($d, $allowed, true); }));
  $refHost = '';
  if ($refFilter !== '') {
    $h = parse_url((stripos($refFilter,'://')===false?'http://':'').$refFilter, PHP_URL_HOST);
    if (!$h) { $h = preg_replace('~^[a-z]+://~i','',$refFilter); $h = preg_replace('~/.*$~','',$h); }
    $refHost = strtolower(trim(substr($h,0,128)));
  }
  if (($scope==='all'||$scope==='links') && $hasLC && $hasL) {
    $devCol = ana_pick_device($pdo,'link_clicks') ?: 'ua_device';
    $tsCol  = ana_pick_created($pdo,'link_clicks') ?: 'created_at';
    $refCol = ana_pick_ref($pdo,'link_clicks');
    $where = "l.user_id=:uid AND lc.`{$tsCol}` BETWEEN :fromTs AND :toTs";
    $dyn=[]; if ($deviceList) { $where .= " AND LOWER(lc.`{$devCol}`) IN (".implode(',', array_fill(0,count($deviceList),'?')).")"; foreach($deviceList as $d){$dyn[]=$d;} }
    if ($refHost!=='' && $refCol) { $where .= " AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(lc.`{$refCol}`,'//',-1),'/',1)) LIKE ?"; $dyn[]='%'.$refHost.'%'; }
    $sql = "SELECT LOWER(COALESCE(lc.`{$devCol}`,'unknown')) AS d, COUNT(*) AS c FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id WHERE {$where} GROUP BY d";
    $st = $pdo->prepare($sql); $st->bindValue(':uid',$uid,PDO::PARAM_INT); $st->bindValue(':fromTs',$fromTs); $st->bindValue(':toTs',$toTs); $i=1; foreach($dyn as $v){ $st->bindValue($i++,$v); }
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $series[$map($r['d'])] += (int)$r['c']; }
  }
  if (($scope==='all'||$scope==='qrs') && $hasQS && $hasQ) {
    $devCol = ana_pick_device($pdo,'qr_scans') ?: 'ua_device';
    $tsCol  = ana_pick_created($pdo,'qr_scans') ?: 'created_at';
    $refCol = ana_pick_ref($pdo,'qr_scans');
    $where = "q.user_id=:uid AND qs.`{$tsCol}` BETWEEN :fromTs AND :toTs";
    $dyn=[]; if ($deviceList) { $where .= " AND LOWER(qs.`{$devCol}`) IN (".implode(',', array_fill(0,count($deviceList),'?')).")"; foreach($deviceList as $d){$dyn[]=$d;} }
    if ($refHost!=='' && $refCol) { $where .= " AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(qs.`{$refCol}`,'//',-1),'/',1)) LIKE ?"; $dyn[]='%'.$refHost.'%'; }
    $sql = "SELECT LOWER(COALESCE(qs.`{$devCol}`,'unknown')) AS d, COUNT(*) AS c FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id WHERE {$where} GROUP BY d";
    $st = $pdo->prepare($sql); $st->bindValue(':uid',$uid,PDO::PARAM_INT); $st->bindValue(':fromTs',$fromTs); $st->bindValue(':toTs',$toTs); $i=1; foreach($dyn as $v){ $st->bindValue($i++,$v); }
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $series[$map($r['d'])] += (int)$r['c']; }
  }
  $out = [];$total=0; foreach ($series as $k=>$v){ $out[] = [$k,(int)$v]; $total += (int)$v; }
  echo json_encode(['ok'=>true,'series'=>$out,'total'=>$total], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'server']); }
exit;


