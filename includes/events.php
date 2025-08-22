<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Insert an event row.
 * type: 'click'|'scan'|'open'|'create'
 * itemType: 'link'|'qr'|'page'
 */
function wz_log_event(PDO $pdo, int $userId, string $itemType, int $itemId, string $type, ?string $label=null, ?string $ts=null): bool {
    $validItem = ['link','qr','page'];
    $validType = ['click','scan','open','create'];
    if (!in_array($itemType, $validItem, true)) $itemType = 'page';
    if (!in_array($type, $validType, true)) $type = 'open';

    if ($ts !== null) {
        // Ensure proper DATETIME format
        $dt = date('Y-m-d H:i:s', strtotime($ts));
        $sql = "INSERT INTO events (user_id, item_type, item_id, type, label, created_at)
                VALUES (:uid, :it, :iid, :typ, :lbl, :ts)";
        $st  = $pdo->prepare($sql);
        return $st->execute([':uid'=>$userId, ':it'=>$itemType, ':iid'=>$itemId, ':typ'=>$type, ':lbl'=>$label, ':ts'=>$dt]);
    }

    $sql = "INSERT INTO events (user_id, item_type, item_id, type, label, created_at)
            VALUES (:uid, :it, :iid, :typ, :lbl, NOW())";
    $st  = $pdo->prepare($sql);
    return $st->execute([':uid'=>$userId, ':it'=>$itemType, ':iid'=>$itemId, ':typ'=>$type, ':lbl'=>$label]);
}

/**
 * Daily counts for last 7/30/90 days per type.
 * Returns rows: [date, click, scan, open, create, total]
 */
function wz_events_trend(PDO $pdo, int $userId, string $period='7d'): array {
    $days = ($period === '30d') ? 30 : (($period === '90d') ? 90 : 7);
    $since = (new DateTime('-'. $days .' days'))->format('Y-m-d 00:00:00');

    $sql = "SELECT DATE(created_at) AS d, type, COUNT(*) AS c
            FROM events
            WHERE user_id=:uid AND created_at>=:since
            GROUP BY DATE(created_at), type
            ORDER BY d ASC";
    $st = $pdo->prepare($sql);
    $st->execute([':uid'=>$userId, ':since'=>$since]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $series = [];
    $cur = new DateTime($since); $today = new DateTime('today');
    while ($cur <= $today) {
        $k = $cur->format('Y-m-d');
        $series[$k] = ['date'=>$k,'click'=>0,'scan'=>0,'open'=>0,'create'=>0,'total'=>0];
        $cur->modify('+1 day');
    }
    foreach ($rows as $r) {
        $k = $r['d']; $t = $r['type']; $c = (int)$r['c'];
        if (!isset($series[$k])) continue;
        if (isset($series[$k][$t])) $series[$k][$t] += $c;
        $series[$k]['total'] += $c;
    }
    return array_values($series);
}

/**
 * Recent events: [item_type, item_id, type, label, created_at]
 */
function wz_events_recent(PDO $pdo, int $userId, int $limit=6): array {
    $sql = "SELECT item_type, item_id, type, label, created_at
            FROM events
            WHERE user_id=:uid
            ORDER BY created_at DESC
            LIMIT :lim";
    $st = $pdo->prepare($sql);
    $st->bindValue(':uid', $userId, PDO::PARAM_INT);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Top items aggregated within a period, optionally filtered by itemType.
 * Returns rows: [item_type,item_id,label,total,today,clicks,scans,opens,creates,first_seen]
 */
function wz_top_items(PDO $pdo, int $userId, string $itemType='all', string $period='7d', int $limit=10): array {
    $days = ($period === '30d') ? 30 : (($period === '90d') ? 90 : 7);
    $since = (new DateTime('-'. $days .' days'))->format('Y-m-d 00:00:00');

    $base = "SELECT item_type, item_id,
                    COALESCE(NULLIF(label,''), CONCAT(UPPER(item_type),' #', item_id)) AS label,
                    SUM(1) AS total,
                    SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today,
                    SUM(CASE WHEN type='click'  THEN 1 ELSE 0 END) AS clicks,
                    SUM(CASE WHEN type='scan'   THEN 1 ELSE 0 END) AS scans,
                    SUM(CASE WHEN type='open'   THEN 1 ELSE 0 END) AS opens,
                    SUM(CASE WHEN type='create' THEN 1 ELSE 0 END) AS creates,
                    MIN(created_at) AS first_seen
             FROM events
             WHERE user_id=:uid AND created_at>=:since";

    if ($itemType !== 'all') {
        $base .= " AND item_type=:it";
    }
    $base .= " GROUP BY item_type, item_id, label ORDER BY total DESC LIMIT :lim";

    $st = $pdo->prepare($base);
    $st->bindValue(':uid', $userId, PDO::PARAM_INT);
    $st->bindValue(':since', $since);
    if ($itemType !== 'all') $st->bindValue(':it', $itemType);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


