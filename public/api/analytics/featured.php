<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/analytics.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) { http_response_code(401); echo json_encode(['error'=>'unauthorized']); exit; }

$p = $_GET['p'] ?? '7d';
if (!in_array($p, ['7d','30d','90d'], true)) $p = '7d';

/** @var PDO $pdo */
try {
    // Always compute latest created items to avoid helper dummy data
    $hasClicksTable = false; $hasClicksCol = true;
    try { $t = $pdo->query("SHOW TABLES LIKE 'link_clicks'"); if ($t && $t->rowCount()>0) $hasClicksTable = true; } catch (Throwable $_) { $hasClicksTable=false; }
    try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'clicks'"); if (!$c || $c->rowCount()===0) $hasClicksCol = false; } catch (Throwable $_) { $hasClicksCol=false; }

    $totalExpr = $hasClicksCol ? 'COALESCE(clicks,0)' : ($hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id=links.id)' : '0');
    $todayExpr = $hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id=links.id AND DATE(lc.created_at)=CURDATE())' : '0';

    $links = [];
    try {
        $sqlL = "SELECT id AS item_id, title AS label, 'link' AS type, created_at,
                        $totalExpr AS total,
                        $todayExpr AS today
                 FROM links WHERE user_id=:uid ORDER BY created_at DESC LIMIT 4";
        $stL = $pdo->prepare($sqlL); $stL->execute([':uid'=>$uid]);
        $links = $stL->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $_) { $links = []; }

    $qrs = [];
    try {
        $sqlQ = "SELECT q.id AS item_id, COALESCE(NULLIF(q.title,''), CONCAT('QR #', q.id)) AS label,
                        'qr' AS type, q.created_at,
                        (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS total,
                        (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id AND DATE(e.created_at)=CURDATE()) AS today
                 FROM qr_codes q WHERE q.user_id=:uid ORDER BY q.created_at DESC LIMIT 4";
        $stQ = $pdo->prepare($sqlQ); $stQ->execute([':uid'=>$uid]);
        $qrs = $stQ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $_) { $qrs = []; }

    $merged = array_merge($links, $qrs);
    usort($merged, function($a,$b){ return strcmp((string)($b['created_at']??''),(string)($a['created_at']??'')); });
    $items = array_slice($merged, 0, 4);

    echo json_encode(['period'=>$p, 'items'=>$items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error'=>'server']);
}
exit;


