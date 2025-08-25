<?php
declare(strict_types=1);
// DEV-only analytics seeder (links + qrs). Requires auth + token=DEV and local env.

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

header('Content-Type: application/json; charset=utf-8');

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'db']); exit; }

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }

// Guard: local env + token
function is_local_env(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $hostNoPort = preg_replace('~:\\d+$~', '', $host);
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($hostNoPort === 'localhost' || str_ends_with($hostNoPort, '.local')) return true;
    if ($ip === '127.0.0.1' || $ip === '::1') return true;
    return false;
}
if (!is_local_env() || ($_GET['token'] ?? '') !== 'DEV') { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'forbidden']); exit; }

function colExists(PDO $pdo, string $table, string $col): bool {
    try {
        $sql = 'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND LOWER(table_name) = LOWER(:t) AND LOWER(column_name) = LOWER(:c) LIMIT 1';
        $st  = $pdo->prepare($sql);
        $st->execute([':t' => $table, ':c' => $col]);
        return (bool) $st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}
function tableExists(PDO $pdo, string $table): bool {
    try {
        $sql = 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND LOWER(table_name) = LOWER(:t) LIMIT 1';
        $st  = $pdo->prepare($sql);
        $st->execute([':t' => $table]);
        return (bool) $st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}
function firstAvailableColumn(PDO $pdo, string $table, array $candidates): ?string {
    foreach ($candidates as $c) { if (colExists($pdo,$table,$c)) return $c; }
    return null;
}
function pickFkCol(PDO $pdo, string $table, array $candidates): ?string {
    foreach ($candidates as $c) { if (colExists($pdo, $table, $c)) return $c; }
    // Extra fallbacks commonly seen in mixed schemas
    $extra = ['parent_id','item_id','owner','ownerId','object_id'];
    foreach ($extra as $c) { if (colExists($pdo, $table, $c)) return $c; }
    return null;
}
function pickCreatedCol(PDO $pdo, string $table): ?string {
    return firstAvailableColumn($pdo, $table, ['created_at','created','timestamp','ts','createdAt','created_time']);
}
function pickDeviceCol(PDO $pdo, string $table): ?string {
    return firstAvailableColumn($pdo, $table, ['ua_device','device','device_type','ua_device_type','ua']);
}
function pickReferrerCol(PDO $pdo, string $table): ?string {
    return firstAvailableColumn($pdo, $table, ['referrer','referer','ref','referrer_url','referrer_host']);
}
function pickUserCol(PDO $pdo, string $table): ?string {
    return firstAvailableColumn($pdo,$table,['user_id','uid','owner_id']);
}

$days   = max(1, min(365, (int)($_GET['days'] ?? 30)));
$perDay = max(1, min(200, (int)($_GET['per'] ?? 5)));
$scope  = in_array(($_GET['scope'] ?? 'all'), ['all','links','qrs'], true) ? $_GET['scope'] : 'all';
$tzStr  = (string)($_GET['tz'] ?? date_default_timezone_get());
try { $tz = new DateTimeZone($tzStr); } catch(Throwable $e) { $tz = new DateTimeZone(date_default_timezone_get()); }

$createdLinks = 0; $createdQrs = 0; $insLC = 0; $insQS = 0;

// Pick or create parents
$linkIds = []; $qrIds = [];

$destCol = firstAvailableColumn($pdo,'links',['destination_url','destination','url']);

try {
    if (tableExists($pdo,'links')) {
        $st = $pdo->prepare('SELECT id FROM links WHERE user_id=:u ORDER BY id LIMIT 5');
        $st->execute([':u'=>$uid]); $linkIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$linkIds) {
            for ($i=0;$i<3;$i++) {
                if ($destCol) {
                    $pdo->prepare("INSERT INTO links (user_id,title,{$destCol},is_active,created_at) VALUES (:u, :t, :d, 1, NOW())")
                        ->execute([':u'=>$uid, ':t'=>'DEV seed link '.($i+1), ':d'=>'https://example.com/seed']);
                } else {
                    $pdo->prepare('INSERT INTO links (user_id,title,is_active,created_at) VALUES (:u, :t, 1, NOW())')
                        ->execute([':u'=>$uid, ':t'=>'DEV seed link '.($i+1)]);
                }
                $linkIds[] = (int)$pdo->lastInsertId(); $createdLinks++;
            }
        }
    }
    if (tableExists($pdo,'qr_codes')) {
        $st = $pdo->prepare('SELECT id FROM qr_codes WHERE user_id=:u ORDER BY id LIMIT 5');
        $st->execute([':u'=>$uid]); $qrIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$qrIds) {
            for ($i=0;$i<3;$i++) {
                $pdo->prepare('INSERT INTO qr_codes (user_id,title,type,is_active,created_at) VALUES (:u, :t, :type, 1, NOW())')
                    ->execute([':u'=>$uid, ':t'=>'DEV seed QR '.($i+1), ':type'=>'url']);
                $qrIds[] = (int)$pdo->lastInsertId(); $createdQrs++;
            }
        }
    }
} catch (Throwable $e) { /* ignore */ }

if (($scope==='links' && !$linkIds) || ($scope==='qrs' && !$qrIds) || (!$linkIds && !$qrIds)) {
    echo json_encode(['ok'=>false,'error'=>'no parents']); exit;
}

$hasLC = tableExists($pdo,'link_clicks');
$hasQS = tableExists($pdo,'qr_scans');

// Auto-create simple tables in DEV if missing
if (!$hasLC) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS link_clicks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            link_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ua_device VARCHAR(32) NULL,
            referrer VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_lc_link_created (link_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $hasLC = true;
    } catch (Throwable $e) { /* keep false if fails */ }
}
if (!$hasQS) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS qr_scans (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            qr_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ua_device VARCHAR(32) NULL,
            referrer VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_qs_qr_created (qr_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $hasQS = true;
    } catch (Throwable $e) { /* keep false if fails */ }
}

if (!$hasLC && !$hasQS) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'tables_missing']); exit; }

// Discover actual column names per table to be resilient to schema diffs
$linkClicks = [
  'table'   => 'link_clicks',
  'fk'      => $hasLC ? pickFkCol($pdo,'link_clicks',['link_id','links_id','linkId','lid','parent_id','item_id']) : null,
  'created' => $hasLC ? pickCreatedCol($pdo,'link_clicks') : null,
  'device'  => $hasLC ? pickDeviceCol($pdo,'link_clicks') : null,
  'ref'     => $hasLC ? pickReferrerCol($pdo,'link_clicks') : null,
  'user'    => $hasLC ? pickUserCol($pdo,'link_clicks') : null,
];
$qrScans = [
  'table'   => 'qr_scans',
  'fk'      => $hasQS ? pickFkCol($pdo,'qr_scans',['qr_id','qr_code_id','qrId','qrCodeId','parent_id','item_id']) : null,
  'created' => $hasQS ? pickCreatedCol($pdo,'qr_scans') : null,
  'device'  => $hasQS ? pickDeviceCol($pdo,'qr_scans') : null,
  'ref'     => $hasQS ? pickReferrerCol($pdo,'qr_scans') : null,
  'user'    => $hasQS ? pickUserCol($pdo,'qr_scans') : null,
];

// Weighted pickers
function pickWeighted(array $weights): string {
    $sum = array_sum($weights); $r = mt_rand(1, max(1,$sum)); $acc = 0;
    foreach ($weights as $k=>$w) { $acc += $w; if ($r <= $acc) return (string)$k; }
    return (string)array_key_first($weights);
}
$devW = ['desktop'=>55,'mobile'=>35,'tablet'=>8,'unknown'=>2];
$refW = ['google.com'=>30,'twitter.com'=>15,'facebook.com'=>12,'linkedin.com'=>8,'direct'=>25,'other'=>10];
$refOthers = ['news.ycombinator.com','bing.com','t.co'];

for ($d=$days-1; $d>=0; $d--) {
    $start = new DateTime("-{$d} days", $tz); $start->setTime(0,0,0);
    for ($i=0; $i<$perDay; $i++) {
        $sec = mt_rand(0, 86399); $ts = clone $start; $ts->modify("+{$sec} seconds");
        $tsStr = $ts->format('Y-m-d H:i:s');
        $device = pickWeighted($devW);
        $refPick = pickWeighted($refW);
        $host = $refPick === 'other' ? $refOthers[array_rand($refOthers)] : $refPick;
        $refVal = ($refPick === 'direct') ? null : ('https://'.$host.'/');

        $toLinks = ($scope==='links') || ($scope==='all' && ($i % 2 === 0));
        if ($toLinks && $linkIds && $hasLC && $linkClicks['fk']) {
            $lid = $linkIds[array_rand($linkIds)];
            $cols = [$linkClicks['fk']];
            $vals = [':fk'=>$lid];
            $place = [':fk'];
            if ($linkClicks['created']) { $cols[] = $linkClicks['created']; $place[]=':ts'; $vals[':ts']=$tsStr; }
            if ($linkClicks['device'])  { $cols[] = $linkClicks['device'];  $place[]=':dev'; $vals[':dev']=$device; }
            if ($linkClicks['ref'] && $refVal !== null) { $cols[] = $linkClicks['ref']; $place[]=':ref'; $vals[':ref']=$refVal; }
            if ($linkClicks['user'])    { $cols[] = $linkClicks['user'];    $place[]=':uid'; $vals[':uid']=$uid; }

            $sql = 'INSERT INTO `'.$linkClicks['table'].'` (' . implode(',', array_map(fn($c)=>"`$c`", $cols)) . ') VALUES (' . implode(',', $place) . ')';
            try { $pdo->prepare($sql)->execute($vals); $insLC++; } catch(Throwable $e) { /* ignore single row failure */ }
        }
        if ((!$toLinks || $scope==='qrs') && $qrIds && $hasQS && $qrScans['fk']) {
            $qid = $qrIds[array_rand($qrIds)];
            $cols = [$qrScans['fk']];
            $vals = [':fk'=>$qid];
            $place = [':fk'];
            if ($qrScans['created']) { $cols[] = $qrScans['created']; $place[]=':ts'; $vals[':ts']=$tsStr; }
            if ($qrScans['device'])  { $cols[] = $qrScans['device'];  $place[]=':dev'; $vals[':dev']=$device; }
            if ($qrScans['ref'] && $refVal !== null) { $cols[] = $qrScans['ref']; $place[]=':ref'; $vals[':ref']=$refVal; }
            if ($qrScans['user'])    { $cols[] = $qrScans['user'];    $place[]=':uid'; $vals[':uid']=$uid; }

            $sql = 'INSERT INTO `'.$qrScans['table'].'` (' . implode(',', array_map(fn($c)=>"`$c`", $cols)) . ') VALUES (' . implode(',', $place) . ')';
            try { $pdo->prepare($sql)->execute($vals); $insQS++; } catch(Throwable $e) { /* ignore single row failure */ }
        }
    }
}

$fromStr = (new DateTime('-'.($days-1).' days', $tz))->setTime(0,0,0)->format('Y-m-d');
$toStr   = (new DateTime('now', $tz))->format('Y-m-d');
echo json_encode([
    'ok'=>true,
    'inserted'=>['link_clicks'=>$insLC,'qr_scans'=>$insQS],
    'created_parents'=>['links'=>$createdLinks,'qrs'=>$createdQrs],
    'range'=>['from'=>$fromStr,'to'=>$toStr],
    'scope'=>$scope
    ,'debug'=>[
      'link_clicks'=>$linkClicks,
      'qr_scans'=>$qrScans
    ]
]);
exit;
