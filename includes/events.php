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
function wz_top_items(PDO $pdo, int $userId, string $itemType='all', string $period='7d', int $limit=10, string $sort='total', string $dir='desc', int $page=1, int $per=105): array {
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
    $base .= " GROUP BY item_type, item_id, label";

    // Sorting whitelist
    $sortKey = strtolower($sort);
    $sortCol = 'total';
    if ($sortKey === 'today') $sortCol = 'today';
    elseif ($sortKey === 'first_seen') $sortCol = 'first_seen';

    $dirKey = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
    $base .= " ORDER BY {$sortCol} {$dirKey} LIMIT :lim OFFSET :off";

    $st = $pdo->prepare($base);
    $st->bindValue(':uid', $userId, PDO::PARAM_INT);
    $st->bindValue(':since', $since);
    if ($itemType !== 'all') $st->bindValue(':it', $itemType);
    $page = max(1, (int)$page);
    $per  = (int)$per > 0 ? (int)$per : (int)$limit;
    $off  = ($page - 1) * $per;
    $st->bindValue(':lim', $per, PDO::PARAM_INT);
    $st->bindValue(':off', $off, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Daily time-series for clicks/scans/views (open) with zero-filled dates
 * Returns: [ [date, clicks, scans, views, total], ... ] ascending by date
 */
function wz_event_series(PDO $pdo, int $userId, string $period): array {
    $days = ($period === '30d') ? 30 : (($period === '90d') ? 90 : 7);
    $since = (new DateTime('-'. $days .' days'))->format('Y-m-d 00:00:00');

    $sql = "SELECT DATE(created_at) AS d,
                   SUM(CASE WHEN type='click' THEN 1 ELSE 0 END) AS clicks,
                   SUM(CASE WHEN type='scan'  THEN 1 ELSE 0 END) AS scans,
                   SUM(CASE WHEN type='open'  THEN 1 ELSE 0 END) AS views,
                   COUNT(*) AS total
            FROM events
            WHERE user_id=:uid AND created_at>=:since
            GROUP BY DATE(created_at)
            ORDER BY d ASC";
    $st = $pdo->prepare($sql);
    $st->execute([':uid'=>$userId, ':since'=>$since]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $by = [];
    foreach ($rows as $r) {
        $by[$r['d']] = [
            'date'   => $r['d'],
            'clicks' => (int)$r['clicks'],
            'scans'  => (int)$r['scans'],
            'views'  => (int)$r['views'],
            'total'  => (int)$r['total'],
        ];
    }

    // zero-fill spine
    $out = [];
    $cur = new DateTime($since);
    $today = new DateTime('today');
    while ($cur <= $today) {
        $k = $cur->format('Y-m-d');
        $out[] = $by[$k] ?? ['date'=>$k,'clicks'=>0,'scans'=>0,'views'=>0,'total'=>0];
        $cur->modify('+1 day');
    }
    return $out;
}


/**
 * Log a link click to link_clicks using server IP/UA.
 */
function wz_log_link_click(PDO $pdo, int $linkId, ?int $userId = null): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    try {
        $st = $pdo->prepare('INSERT INTO link_clicks (link_id, user_id, ip_address, user_agent, created_at) VALUES (:id, :uid, :ip, :ua, NOW())');
        $st->execute([':id'=>$linkId, ':uid'=>$userId, ':ip'=>$ip, ':ua'=>$ua]);
    } catch (Throwable $e) {
        // swallow; clicks are best-effort
    }
}


