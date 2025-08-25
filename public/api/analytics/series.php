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

function norm_date(?string $s, string $fallback): string {
  if (!$s) return $fallback;
  $d = DateTime::createFromFormat('Y-m-d', substr($s,0,10));
  return $d ? $d->format('Y-m-d') : $fallback;
}

$from = ana_normalize_date($_GET['from'] ?? null, date('Y-m-d', strtotime('-30 days')));
$to   = ana_normalize_date($_GET['to']   ?? null, date('Y-m-d'));
$interval = $_GET['interval'] ?? 'day';
$scope    = $_GET['scope']    ?? 'all';
if (!in_array($interval, ['day','week','month'], true)) $interval = 'day';
if (!in_array($scope, ['all','links','qrs'], true)) $scope = 'all';

// clamp to 365 days window
$dtFrom = new DateTime($from); $dtTo = new DateTime($to);
if ($dtTo < $dtFrom) { [$dtFrom,$dtTo] = [$dtTo,$dtFrom]; }
if ($dtFrom < (new DateTime('-365 days'))) $dtFrom = new DateTime('-365 days');
$from = $dtFrom->format('Y-m-d'); $to = $dtTo->format('Y-m-d');

try { ana_window_guard($from, $to, 365); } catch (Throwable $e) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad_range']); exit; }

// Build base select via helper
[$sqlBase] = ana_scope_sql($scope);

// Pull all timestamps and aggregate in PHP for portability
try {
  $rows = [];
  // Gracefully handle missing tables
  $hasLC = ana_table_exists($pdo, 'link_clicks');
  $hasQS = ana_table_exists($pdo, 'qr_scans');
  $hasL  = ana_table_exists($pdo, 'links');
  $hasQ  = ana_table_exists($pdo, 'qr_codes');
  $hasLinks = $hasLC && $hasL;
  $hasQrs   = $hasQS && $hasQ;
  if (!$hasLinks && !$hasQrs) { echo json_encode(['ok'=>true,'labels'=>[],'counts'=>[],'total'=>0]); exit; }
  $parts = [];
  if (($scope==='links' || $scope==='all') && $hasLinks) {
    $parts[] = "SELECT lc.created_at FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id WHERE l.user_id=:uid AND lc.created_at BETWEEN :fromTs AND :toTs";
  }
  if (($scope==='qrs' || $scope==='all') && $hasQrs) {
    $parts[] = "SELECT qs.created_at FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id WHERE q.user_id=:uid AND qs.created_at BETWEEN :fromTs AND :toTs";
  }
  $fromTs = $from.' 00:00:00'; $toTs = $to.' 23:59:59';
  if ($parts) {
    // Discover created_at column names for resilience
    $lcCreated = $hasLC ? (ana_pick_created($pdo,'link_clicks') ?: 'created_at') : null;
    $qsCreated = $hasQS ? (ana_pick_created($pdo,'qr_scans')   ?: 'created_at') : null;
    // Replace created_at with discovered names
    $sql = '';
    $bind = [':uid'=>$uid, ':fromTs'=>$fromTs, ':toTs'=>$toTs];
    $seg = [];
    if (($scope==='links'||$scope==='all') && $hasLinks && $lcCreated) {
      $seg[] = "SELECT lc.`{$lcCreated}` AS created_at FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id WHERE l.user_id=:uid AND lc.`{$lcCreated}` BETWEEN :fromTs AND :toTs";
    }
    if (($scope==='qrs'||$scope==='all') && $hasQrs && $qsCreated) {
      $seg[] = "SELECT qs.`{$qsCreated}` AS created_at FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id WHERE q.user_id=:uid AND qs.`{$qsCreated}` BETWEEN :fromTs AND :toTs";
    }
    if ($seg) {
      $sql = implode(' UNION ALL ', $seg);
      $st = $pdo->prepare($sql);
      $st->execute($bind);
      $rows = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
  }

  // Fallback: use events table if we still have no rows (older installs)
  if (!$rows && ana_table_exists($pdo, 'events')) {
    $whereType = '';
    if ($scope === 'links') { $whereType = " AND item_type='link'"; }
    elseif ($scope === 'qrs') { $whereType = " AND item_type='qr'"; }
    $sqlEv = "SELECT created_at FROM events WHERE user_id=:uid AND created_at BETWEEN :fromTs AND :toTs" . $whereType . " AND type IN ('click','scan')";
    $st = $pdo->prepare($sqlEv); $st->execute([':uid'=>$uid, ':fromTs'=>$fromTs, ':toTs'=>$toTs]);
    $rows = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
  }

  $bucket = function(string $ts) use($interval): string {
    $t = new DateTime($ts);
    if ($interval === 'month') return $t->format('Y-m');
    if ($interval === 'week')  return $t->format('o-W');
    return $t->format('Y-m-d');
  };

  $counts = [];
  foreach ($rows as $ts) { $k = $bucket($ts); $counts[$k] = ($counts[$k] ?? 0) + 1; }

  // Build spine
  $labels = [];
  $cur = clone $dtFrom; $end = clone $dtTo;
  while ($cur <= $end) {
    $labels[] = $interval==='month' ? $cur->format('Y-m') : ($interval==='week' ? $cur->format('o-W') : $cur->format('Y-m-d'));
    $cur->modify($interval==='month'?'+1 month':($interval==='week'?'+1 week':'+1 day'));
  }
  $series = [];
  foreach ($labels as $k) $series[] = (int)($counts[$k] ?? 0);

  echo json_encode(['ok'=>true,'labels'=>$labels,'counts'=>$series,'total'=>array_sum($series)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>'server']);
}
exit;


