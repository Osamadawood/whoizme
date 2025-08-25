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
try { ana_window_guard($from, $to, 365); } catch (Throwable $e) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_range']); exit; }

$map = function($v){ $v = strtolower((string)$v); if (in_array($v,['desktop','mobile','tablet','unknown'],true)) return ucfirst($v); return 'Unknown'; };

try {
  $series = ['Desktop'=>0,'Mobile'=>0,'Tablet'=>0,'Unknown'=>0];
  $hasLC = ana_table_exists($pdo,'link_clicks');
  $hasQS = ana_table_exists($pdo,'qr_scans');
  $hasL  = ana_table_exists($pdo,'links');
  $hasQ  = ana_table_exists($pdo,'qr_codes');
  $fromTs = $from.' 00:00:00'; $toTs = $to.' 23:59:59';
  if (($scope==='all'||$scope==='links') && $hasLC && $hasL) {
    $devCol = ana_pick_device($pdo,'link_clicks') ?: 'ua_device';
    $tsCol  = ana_pick_created($pdo,'link_clicks') ?: 'created_at';
    $sql = "SELECT LOWER(COALESCE(lc.`{$devCol}`,'unknown')) AS d, COUNT(*) AS c
            FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id
            WHERE l.user_id=:uid AND lc.`{$tsCol}` BETWEEN :fromTs AND :toTs
            GROUP BY d";
    $st = $pdo->prepare($sql); $st->execute([':uid'=>$uid, ':fromTs'=>$fromTs, ':toTs'=>$toTs]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $series[$map($r['d'])] += (int)$r['c']; }
  }
  if (($scope==='all'||$scope==='qrs') && $hasQS && $hasQ) {
    $devCol = ana_pick_device($pdo,'qr_scans') ?: 'ua_device';
    $tsCol  = ana_pick_created($pdo,'qr_scans') ?: 'created_at';
    $sql = "SELECT LOWER(COALESCE(qs.`{$devCol}`,'unknown')) AS d, COUNT(*) AS c
            FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id
            WHERE q.user_id=:uid AND qs.`{$tsCol}` BETWEEN :fromTs AND :toTs
            GROUP BY d";
    $st = $pdo->prepare($sql); $st->execute([':uid'=>$uid, ':fromTs'=>$fromTs, ':toTs'=>$toTs]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) { $series[$map($r['d'])] += (int)$r['c']; }
  }
  $out = [];$total=0; foreach ($series as $k=>$v){ $out[] = [$k,(int)$v]; $total += (int)$v; }
  echo json_encode(['ok'=>true,'series'=>$out,'total'=>$total], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'server']); }
exit;


