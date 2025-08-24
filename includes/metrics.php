<?php
declare(strict_types=1);

/**
 * Analytics metrics helper (defensive to missing tables/columns)
 */

/** @return bool */
function wz_table_exists(PDO $pdo, string $table): bool {
  try {
    $st = $pdo->prepare("SHOW TABLES LIKE :t");
    $st->execute([':t'=>$table]);
    return (bool)$st->fetchColumn();
  } catch (Throwable $e) { return false; }
}

/** @return bool */
function wz_column_exists(PDO $pdo, string $table, string $col): bool {
  try {
    $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :c");
    $st->execute([':c'=>$col]);
    return (bool)$st->fetchColumn();
  } catch (Throwable $e) { return false; }
}

/**
 * Compute phase-1 analytics KPIs.
 * - Scopes to current user where possible (via join with owner tables)
 */
function wz_get_analytics_metrics(PDO $pdo, ?string $from = null, ?string $to = null): array {
  $uid = (int)($_SESSION['user_id'] ?? 0);

  $hasLinks    = wz_table_exists($pdo, 'links');
  $hasQRs      = wz_table_exists($pdo, 'qr_codes');
  $hasLC       = wz_table_exists($pdo, 'link_clicks');
  $hasQS       = wz_table_exists($pdo, 'qr_scans');
  $hasEvents   = wz_table_exists($pdo, 'events');

  $metrics = [
    'total_engagements' => 0,
    'active_qr'         => 0,
    'unique_visitors'   => 0,
    'active_links'      => 0,
  ];

  // Active QR codes
  if ($hasQRs) {
    try {
      $ownerFilter = wz_column_exists($pdo, 'qr_codes', 'user_id') ? ' WHERE user_id=:uid' : '';
      $sql = "SELECT COUNT(*) FROM qr_codes" . $ownerFilter . (wz_column_exists($pdo,'qr_codes','is_active') ? ($ownerFilter?" AND":" WHERE") . " is_active=1" : "");
      $st = $pdo->prepare($sql);
      if ($ownerFilter) $st->bindValue(':uid', $uid, PDO::PARAM_INT);
      $st->execute();
      $metrics['active_qr'] = (int)$st->fetchColumn();
    } catch (Throwable $e) { /* keep 0 */ }
  }

  // Active links
  if ($hasLinks) {
    try {
      $ownerFilter = wz_column_exists($pdo, 'links', 'user_id') ? ' WHERE user_id=:uid' : '';
      $sql = "SELECT COUNT(*) FROM links" . $ownerFilter . (wz_column_exists($pdo,'links','is_active') ? ($ownerFilter?" AND":" WHERE") . " is_active=1" : "");
      $st = $pdo->prepare($sql);
      if ($ownerFilter) $st->bindValue(':uid', $uid, PDO::PARAM_INT);
      $st->execute();
      $metrics['active_links'] = (int)$st->fetchColumn();
    } catch (Throwable $e) { /* keep 0 */ }
  }

  // Total engagements (clicks + scans)
  $total = 0;
  if ($hasLC && $hasLinks) {
    try {
      $sql = "SELECT COUNT(*) FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id" . (wz_column_exists($pdo,'links','user_id') ? " WHERE l.user_id=:uid" : "");
      $st = $pdo->prepare($sql);
      if (wz_column_exists($pdo,'links','user_id')) $st->bindValue(':uid',$uid,PDO::PARAM_INT);
      $st->execute(); $total += (int)$st->fetchColumn();
    } catch (Throwable $e) { /* 0 */ }
  } elseif ($hasLC) {
    try { $total += (int)$pdo->query("SELECT COUNT(*) FROM link_clicks")->fetchColumn(); } catch (Throwable $e) {}
  }
  if ($hasQS && $hasQRs) {
    try {
      $sql = "SELECT COUNT(*) FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id" . (wz_column_exists($pdo,'qr_codes','user_id') ? " WHERE q.user_id=:uid" : "");
      $st = $pdo->prepare($sql);
      if (wz_column_exists($pdo,'qr_codes','user_id')) $st->bindValue(':uid',$uid,PDO::PARAM_INT);
      $st->execute(); $total += (int)$st->fetchColumn();
    } catch (Throwable $e) { /* 0 */ }
  } elseif ($hasEvents) {
    // Fallback via events table
    try {
      $sql = "SELECT COUNT(*) FROM events WHERE item_type='qr'" . (wz_column_exists($pdo,'events','user_id')?" AND user_id=:uid":"");
      $st = $pdo->prepare($sql);
      if (wz_column_exists($pdo,'events','user_id')) $st->bindValue(':uid',$uid,PDO::PARAM_INT);
      $st->execute(); $total += (int)$st->fetchColumn();
    } catch (Throwable $e) { /* 0 */ }
  }
  $metrics['total_engagements'] = $total;

  // Unique visitors across clicks + scans (union distinct)
  $parts = [];
  if ($hasLC) {
    $ownerJoin = ($hasLinks && wz_column_exists($pdo,'links','user_id')) ? ' INNER JOIN links l ON l.id=lc.link_id AND l.user_id=:uid' : '';
    $hasVid = wz_column_exists($pdo,'link_clicks','visitor_id');
    $hasIp  = wz_column_exists($pdo,'link_clicks','ip_country');
    $hasUa  = wz_column_exists($pdo,'link_clicks','user_agent');
    if ($hasVid || ($hasIp && $hasUa)) {
      $parts[] = "SELECT COALESCE(CAST(lc.visitor_id AS CHAR), SHA2(CONCAT(IFNULL(lc.ip_country,''), IFNULL(lc.user_agent,'')),256)) AS v FROM link_clicks lc{$ownerJoin}";
    } else {
      $parts[] = "SELECT DATE(lc.created_at) AS v FROM link_clicks lc{$ownerJoin}";
    }
  }
  if ($hasQS) {
    $ownerJoin = ($hasQRs && wz_column_exists($pdo,'qr_codes','user_id')) ? ' INNER JOIN qr_codes q ON q.id=qs.qr_id AND q.user_id=:uid' : '';
    $hasVid = wz_column_exists($pdo,'qr_scans','visitor_id');
    $hasIp  = wz_column_exists($pdo,'qr_scans','ip_country');
    $hasUa  = wz_column_exists($pdo,'qr_scans','user_agent');
    if ($hasVid || ($hasIp && $hasUa)) {
      $parts[] = "SELECT COALESCE(CAST(qs.visitor_id AS CHAR), SHA2(CONCAT(IFNULL(qs.ip_country,''), IFNULL(qs.user_agent,'')),256)) AS v FROM qr_scans qs{$ownerJoin}";
    } else {
      $parts[] = "SELECT DATE(qs.created_at) AS v FROM qr_scans qs{$ownerJoin}";
    }
  }
  if (!empty($parts)) {
    try {
      $sql = 'SELECT COUNT(1) FROM ('.implode(' UNION ', $parts).') u';
      $st = $pdo->prepare($sql);
      if (strpos($sql, ':uid') !== false) $st->bindValue(':uid',$uid,PDO::PARAM_INT);
      $st->execute();
      $metrics['unique_visitors'] = (int)$st->fetchColumn();
    } catch (Throwable $e) { /* 0 */ }
  }

  return $metrics;
}


