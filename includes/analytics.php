<?php
// includes/analytics.php
require_once __DIR__ . '/db_map.php';

function wz_user_id(): ?int {
  if (isset($_SESSION['user']['id'])) return (int)$_SESSION['user']['id'];
  if (isset($_SESSION['auth']['id'])) return (int)$_SESSION['auth']['id'];
  return null;
}

function wz_unified_events_sql(PDO $pdo): array {
  $m = wz_detect_schema($pdo);
  if ($m['events']['mode']==='unified') {
    $t=$m['events']['table']; $c=$m['events']['cols'];
    $sql = "SELECT `{$c['user_id']}` AS user_id, `{$c['item_type']}` AS item_type,
                   `{$c['item_id']}` AS item_id, `{$c['event_type']}` AS event_type,
                   `{$c['created_at']}` AS created_at
            FROM `{$t}` WHERE `{$c['user_id']}`=:uid";
    return [$sql,[]];
  }
  $parts=[];
  if ($m['events']['clicks']) {
    $c=$m['events']['clicks']['cols']; $t=$m['events']['clicks']['table'];
    $parts[]="SELECT `{$c['user_id']}` AS user_id,'link' AS item_type, `{$c['item_id']}` AS item_id,
                     'click' AS event_type, `{$c['created_at']}` AS created_at
              FROM `{$t}` WHERE `{$c['user_id']}`=:uid";
  }
  if ($m['events']['scans']) {
    $c=$m['events']['scans']['cols']; $t=$m['events']['scans']['table'];
    $parts[]="SELECT `{$c['user_id']}` AS user_id,'qr' AS item_type, `{$c['item_id']}` AS item_id,
                     'scan' AS event_type, `{$c['created_at']}` AS created_at
              FROM `{$t}` WHERE `{$c['user_id']}`=:uid";
  }
  return [implode(" UNION ALL ",$parts),[]];
}

function wz_kpis(PDO $pdo, int $uid): array {
  $m = wz_detect_schema($pdo);
  $linksT=$m['links']['table']; $lc=$m['links']['cols'];
  [$evSQL,] = wz_unified_events_sql($pdo);

  // Totals (all time)
  $sqlTot = "SELECT
               SUM(CASE WHEN event_type IN ('click','open') THEN 1 ELSE 0 END) AS total_clicks,
               SUM(CASE WHEN event_type='scan' THEN 1 ELSE 0 END) AS total_scans
             FROM ({$evSQL}) ev WHERE ev.user_id=:uid";
  $st=$pdo->prepare($sqlTot); $st->execute([':uid'=>$uid]);
  $tot=$st->fetch(PDO::FETCH_ASSOC) ?: ['total_clicks'=>0,'total_scans'=>0];

  $sqlActive="SELECT COUNT(*) FROM `{$linksT}` WHERE `{$lc['user_id']}`=:uid AND `{$lc['is_active']}` IN (1,'1','active','ACTIVE')";
  $st=$pdo->prepare($sqlActive); $st->execute([':uid'=>$uid]);
  $active=(int)$st->fetchColumn();

  $today0=(new DateTime('today'))->format('Y-m-d 00:00:00');
  $yest0 =(new DateTime('yesterday'))->format('Y-m-d 00:00:00');
  $now=date('Y-m-d H:i:s');

  // Today vs Yesterday deltas (clicks/open, scans)
  $sqlDay="SELECT COUNT(*) FROM ({$evSQL}) ev WHERE ev.user_id=:uid AND ev.event_type IN (%s) AND ev.created_at>=:a AND ev.created_at<:b";
  $st=$pdo->prepare(sprintf($sqlDay, "'click','open'"));
  $st->execute([':uid'=>$uid,':a'=>$today0,':b'=>$now]); $ct_clicks=(int)$st->fetchColumn();
  $st->execute([':uid'=>$uid,':a'=>$yest0, ':b'=>$today0]); $cy_clicks=(int)$st->fetchColumn();
  $deltaTodayClicks = $cy_clicks>0 ? (($ct_clicks-$cy_clicks)/$cy_clicks)*100.0 : ($ct_clicks>0?100.0:0.0);

  $st=$pdo->prepare(sprintf($sqlDay, "'scan'"));
  $st->execute([':uid'=>$uid,':a'=>$today0,':b'=>$now]); $ct_scans=(int)$st->fetchColumn();
  $st->execute([':uid'=>$uid,':a'=>$yest0, ':b'=>$today0]); $cy_scans=(int)$st->fetchColumn();
  $deltaTodayScans = $cy_scans>0 ? (($ct_scans-$cy_scans)/$cy_scans)*100.0 : ($ct_scans>0?100.0:0.0);

  // This week vs last week (active links created)
  $week0  =(new DateTime('monday this week'))->format('Y-m-d 00:00:00');
  $prevW0 =(new DateTime('monday last week'))->format('Y-m-d 00:00:00');
  $sqlW="SELECT COUNT(*) FROM `{$linksT}` WHERE `{$lc['user_id']}`=:uid AND `{$lc['is_active']}` IN (1,'1','active','ACTIVE') AND `{$lc['created_at']}`>=:a AND `{$lc['created_at']}`<:b";
  $st=$pdo->prepare($sqlW);
  $st->execute([':uid'=>$uid,':a'=>$week0, ':b'=>$now]);   $wk =(int)$st->fetchColumn();
  $st->execute([':uid'=>$uid,':a'=>$prevW0,':b'=>$week0]); $wkp=(int)$st->fetchColumn();
  $deltaWeekActiveLinks = $wkp>0 ? (($wk-$wkp)/$wkp)*100.0 : ($wk>0?100.0:0.0);

  return [
    'total_clicks'=>(int)$tot['total_clicks'],
    'total_scans' =>(int)$tot['total_scans'],
    'active_links'=>$active,
    'delta_today_clicks'=>$deltaTodayClicks,
    'delta_today_scans'=>$deltaTodayScans,
    'delta_week_active_links'=>$deltaWeekActiveLinks,
  ];
}

function wz_recent_activity(PDO $pdo, int $uid, int $limit=6): array {
  [$evSQL,] = wz_unified_events_sql($pdo);
  // Group by item, order by most recent activity
  $sql="SELECT item_type, item_id,
               MAX(created_at) AS last_at,
               SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today_cnt
        FROM ({$evSQL}) ev
        WHERE ev.user_id=:uid
        GROUP BY item_type, item_id
        ORDER BY last_at DESC
        LIMIT :lim";
  $st=$pdo->prepare($sql);
  $st->bindValue(':uid',$uid,PDO::PARAM_INT);
  $st->bindValue(':lim',$limit,PDO::PARAM_INT);
  $st->execute();
  $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  if (!$rows) return [];

  // Attach titles
  $m = wz_detect_schema($pdo);
  $linksT=$m['links']['table']; $lc=$m['links']['cols'];
  $qrT   =$m['qrcodes']['table']; $qc=$m['qrcodes']['cols'];

  $linkIds=[]; $qrIds=[];
  foreach($rows as $r){ if ($r['item_type']==='link') $linkIds[]=(int)$r['item_id']; if ($r['item_type']==='qr') $qrIds[]=(int)$r['item_id']; }
  $linkIds=array_values(array_unique(array_filter($linkIds)));
  $qrIds  =array_values(array_unique(array_filter($qrIds)));

  $titleBy=['link'=>[],'qr'=>[],'page'=>[]];
  if ($linkIds){
    $in=implode(',', array_fill(0,count($linkIds),'?'));
    $st2=$pdo->prepare("SELECT `{$lc['id']}` id, `{$lc['title']}` title FROM `{$linksT}` WHERE `{$lc['id']}` IN ({$in})");
    $st2->execute($linkIds);
    foreach($st2->fetchAll(PDO::FETCH_ASSOC) as $r){ $titleBy['link'][(int)$r['id']]=$r['title']; }
  }
  if ($qrIds){
    $in=implode(',', array_fill(0,count($qrIds),'?'));
    $st2=$pdo->prepare("SELECT `{$qc['id']}` id, `{$qc['title']}` title FROM `{$qrT}` WHERE `{$qc['id']}` IN ({$in})");
    $st2->execute($qrIds);
    foreach($st2->fetchAll(PDO::FETCH_ASSOC) as $r){ $titleBy['qr'][(int)$r['id']]=$r['title']; }
  }

  $out=[];
  foreach($rows as $r){
    $iid=(int)$r['item_id']; $typ=$r['item_type'];
    $title=$titleBy[$typ][$iid] ?? ($typ==='qr'?'QR code #'.$iid:($typ==='link'?'Link #'.$iid:'Page #'.$iid));
    $ts=strtotime($r['last_at']);
    $isToday = date('Y-m-d',$ts)===date('Y-m-d');
    $isYest  = date('Y-m-d',$ts)===date('Y-m-d', strtotime('yesterday'));
    $when = $isToday ? date('h:i A',$ts) : ($isYest ? 'Yesterday' : date('M d',$ts));
    $out[]=[ 'title'=>$title, 'type'=>$typ, 'delta'=>(int)$r['today_cnt'], 'at'=>$when ];
  }
  return $out;
}

function wz_top_items(PDO $pdo, int $uid, int $limit=10): array {
  $today0 =(new DateTime('today'))->format('Y-m-d 00:00:00');
  $yest0  =(new DateTime('yesterday'))->format('Y-m-d 00:00:00');
  $now    = date('Y-m-d H:i:s');
  [$evSQL,] = wz_unified_events_sql($pdo);

  // Aggregate all-time totals, and today's count for delta calc
  $sql="SELECT item_type,item_id,
               SUM(1) AS total_cnt,
               SUM(CASE WHEN created_at>=:t0 AND created_at<:now THEN 1 ELSE 0 END) AS today_cnt
        FROM ({$evSQL}) ev
        WHERE ev.user_id=:uid
        GROUP BY item_type,item_id
        ORDER BY total_cnt DESC
        LIMIT :lim";
  $st=$pdo->prepare($sql);
  $st->bindValue(':uid',$uid,PDO::PARAM_INT);
  $st->bindValue(':t0',$today0);
  $st->bindValue(':now',$now);
  $st->bindValue(':lim',$limit,PDO::PARAM_INT);
  $st->execute();
  $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];

  if (!$rows) return [];

  // Compute yesterday count per item for delta, and attach titles + created_at
  $m = wz_detect_schema($pdo);
  $linksT=$m['links']['table']; $lc=$m['links']['cols'];
  $qrT   =$m['qrcodes']['table']; $qc=$m['qrcodes']['cols'];

  $linkIds=[]; $qrIds=[];
  foreach($rows as $r){
    if ($r['item_type']==='link') $linkIds[]=(int)$r['item_id'];
    if ($r['item_type']==='qr')   $qrIds[]  =(int)$r['item_id'];
  }
  $linkIds=array_values(array_unique(array_filter($linkIds)));
  $qrIds  =array_values(array_unique(array_filter($qrIds)));

  $metaBy=['link'=>[],'qr'=>[]];
  if ($linkIds){
    $in=implode(',', array_fill(0,count($linkIds),'?'));
    $st2=$pdo->prepare("SELECT `{$lc['id']}` id, `{$lc['title']}` title, `{$lc['created_at']}` created_at FROM `{$linksT}` WHERE `{$lc['id']}` IN ({$in})");
    $st2->execute($linkIds);
    foreach($st2->fetchAll(PDO::FETCH_ASSOC) as $r){ $metaBy['link'][(int)$r['id']]=$r; }
  }
  if ($qrIds){
    $in=implode(',', array_fill(0,count($qrIds),'?'));
    $st2=$pdo->prepare("SELECT `{$qc['id']}` id, `{$qc['title']}` title, `{$qc['created_at']}` created_at FROM `{$qrT}` WHERE `{$qc['id']}` IN ({$in})");
    $st2->execute($qrIds);
    foreach($st2->fetchAll(PDO::FETCH_ASSOC) as $r){ $metaBy['qr'][(int)$r['id']]=$r; }
  }

  foreach($rows as &$r){
    $st2=$pdo->prepare("SELECT COUNT(*) FROM ({$evSQL}) ev
      WHERE ev.user_id=:uid AND ev.item_type=:it AND ev.item_id=:iid
        AND ev.created_at>=:a AND ev.created_at<:b");
    $st2->execute([':uid'=>$uid,':it'=>$r['item_type'],':iid'=>(int)$r['item_id'],':a'=>$yest0, ':b'=>$today0]);
    $yday=(int)$st2->fetchColumn();
    $today=(int)$r['today_cnt'];
    $r['delta_today_pct'] = $yday>0 ? (($today-$yday)/$yday)*100.0 : ($today>0?100.0:0.0);

    $iid=(int)$r['item_id'];
    $meta=$metaBy[$r['item_type']][$iid] ?? null;
    $r['title'] = $meta['title'] ?? (($r['item_type']==='qr')?('QR code #'.$iid):('Link #'.$iid));
    $r['created_at'] = $meta['created_at'] ?? null;
  }
  unset($r);
  return $rows;
}