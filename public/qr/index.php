<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$q   = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$allowedPer = [10,25,50];
$perReq = (int)($_GET['per'] ?? 10);
$per  = in_array($perReq, $allowedPer, true) ? $perReq : 10;
$off  = ($page - 1) * $per;

// KPIs (simple queries; can be optimized later)
$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE user_id=:uid AND is_active IN (1,'1','active','ACTIVE')");
$activeStmt->execute([':uid'=>$uid]);
$kpi_active = (int)$activeStmt->fetchColumn();

$scanStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id=:uid AND item_type='qr' AND type='scan'");
$scanStmt->execute([':uid'=>$uid]);
$kpi_scans = (int)$scanStmt->fetchColumn();
$kpi_visitors = $kpi_scans; // placeholder until we track unique visitors

// Deltas (real data from events/qr_codes)
$today0 = (new DateTime('today'))->format('Y-m-d 00:00:00');
$yest0  = (new DateTime('yesterday'))->format('Y-m-d 00:00:00');
$now    = date('Y-m-d H:i:s');

// Scans: today vs yesterday
$st = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id=:uid AND item_type='qr' AND type='scan' AND created_at>=:a AND created_at<:b");
$st->execute([':uid'=>$uid, ':a'=>$today0, ':b'=>$now]);  $scans_today = (int)$st->fetchColumn();
$st->execute([':uid'=>$uid, ':a'=>$yest0,  ':b'=>$today0]); $scans_yday  = (int)$st->fetchColumn();
$delta_scans = $scans_yday>0 ? (($scans_today-$scans_yday)/$scans_yday)*100.0 : ($scans_today>0?100.0:0.0);

// Active created: this week vs last week
$week0 = (new DateTime('monday this week'))->format('Y-m-d 00:00:00');
$prevW0= (new DateTime('monday last week'))->format('Y-m-d 00:00:00');
$st = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE user_id=:uid AND created_at>=:a AND created_at<:b");
$st->execute([':uid'=>$uid, ':a'=>$week0,  ':b'=>$now]);   $act_week = (int)$st->fetchColumn();
$st->execute([':uid'=>$uid, ':a'=>$prevW0, ':b'=>$week0]); $act_prev = (int)$st->fetchColumn();
$delta_active = $act_prev>0 ? (($act_week-$act_prev)/$act_prev)*100.0 : ($act_week>0?100.0:0.0);

// Visitors delta mirrors scans (until we have unique visitor model)
$delta_visitors = $delta_scans;

// List data
$where = 'q.user_id = :uid';
$params = [':uid'=>$uid];

// Filter by active status (show hidden if requested)
$showHidden = isset($_GET['show_hidden']) && $_GET['show_hidden'] === '1';
if (!$showHidden) {
    $where .= ' AND (q.is_active = 1 OR q.is_active IS NULL)';
}

if ($q !== '') {
    // Safe: default to searching id if short_code info not loaded yet
    if (isset($hasShortCode) && $hasShortCode) {
        $where .= ' AND (q.title LIKE :kw OR q.payload LIKE :kw OR q.short_code LIKE :kw)'; 
    } else {
        $where .= ' AND (q.title LIKE :kw OR q.payload LIKE :kw OR q.id LIKE :kw)'; 
    }
    $params[':kw'] = "%$q%"; 
}

$typeParam = strtolower(trim($_GET['type'] ?? 'all'));
$allowedTypes = ['all','url','vcard','text','email','wifi','pdf','stores','images'];
if (!in_array($typeParam, $allowedTypes, true)) { $typeParam = 'all'; }
if ($typeParam !== 'all') { $where .= ' AND q.type = :type'; $params[':type'] = $typeParam; }

$countSql = "SELECT COUNT(*) FROM qr_codes q WHERE $where";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k=>$v) { $countStmt->bindValue($k, $v); }
$countStmt->execute();
$totalRows = (int)$countStmt->fetchColumn();

// Check if short_code column exists
$hasShortCode = false;
try {
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM qr_codes LIKE 'short_code'");
    $checkStmt->execute();
    $hasShortCode = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasShortCode = false;
}

// Check if style_json column exists
$hasStyleJson = false;
try {
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM qr_codes LIKE 'style_json'");
    $checkStmt->execute();
    $hasStyleJson = $checkStmt->rowCount() > 0;
} catch (PDOException $e) {
    $hasStyleJson = false;
}

// Build query based on column existence
if ($hasShortCode && $hasStyleJson) {
    $sql = "SELECT q.id, q.short_code as code, q.type, q.title, q.payload, q.style_json, q.is_active, q.created_at,
                   (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS scans
            FROM qr_codes q
            WHERE $where
            ORDER BY q.created_at DESC
            LIMIT :per OFFSET :off";
} elseif ($hasShortCode) {
    $sql = "SELECT q.id, q.short_code as code, q.type, q.title, q.payload, '{}' as style_json, q.is_active, q.created_at,
                   (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS scans
            FROM qr_codes q
            WHERE $where
            ORDER BY q.created_at DESC
            LIMIT :per OFFSET :off";
} elseif ($hasStyleJson) {
    $sql = "SELECT q.id, q.id as code, q.type, q.title, q.payload, q.style_json, q.is_active, q.created_at,
                   (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS scans
            FROM qr_codes q
            WHERE $where
            ORDER BY q.created_at DESC
            LIMIT :per OFFSET :off";
} else {
    $sql = "SELECT q.id, q.id as code, q.type, q.title, q.payload, '{}' as style_json, q.is_active, q.created_at,
                   (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS scans
            FROM qr_codes q
            WHERE $where
            ORDER BY q.created_at DESC
        LIMIT :per OFFSET :off";
}
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $off, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Debug: Log the number of rows found
error_log("QR List: Found " . count($rows) . " QR codes for user " . $uid);

$page_title = 'QR Codes';
include __DIR__ . '/../partials/app_header.php';
?>

<main class="dashboard">

  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>

  <div class="container dash-grid" role="region" aria-label="QR Codes layout">
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [
          ['label' => 'Dashboard', 'href' => '/dashboard'],
          ['label' => 'QR Codes',  'href' => null],
        ];
        $topbar = [ 'search' => [ 'enabled' => false ] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>

    <section class="maincol">
      <?php
        // List view header + type tabs
        $labels = [
          'all'=>['en'=>'All','ar'=>'الكل'],
          'url'=>['en'=>'URL','ar'=>'رابط'],
          'vcard'=>['en'=>'vCard','ar'=>'بطاقة'],
          'text'=>['en'=>'Text','ar'=>'نص'],
          'email'=>['en'=>'E‑mail','ar'=>'بريد'],
          'wifi'=>['en'=>'Wi‑Fi','ar'=>'واي فاي'],
          'pdf'=>['en'=>'PDF','ar'=>'PDF'],
          'stores'=>['en'=>'App Stores','ar'=>'متاجر التطبيقات'],
          'images'=>['en'=>'Images','ar'=>'صور'],
        ];
        $lang = ($GLOBALS['lang'] ?? 'en');
        $type = strtolower(trim($_GET['type'] ?? 'all'));
        $allowedTypes = array_keys($labels);
        if (!in_array($type, $allowedTypes, true)) { $type = 'all'; }
        $viewMode = $_GET['view'] ?? 'table'; // default list
        function build_qr_url(array $overrides = []) {
          $qs = $_GET;
          foreach ($overrides as $k=>$v) { if ($v === null) unset($qs[$k]); else $qs[$k]=$v; }
          return '/qr' . ($qs ? ('?' . http_build_query($qs)) : '');
        }
      ?>
      <?php if ($viewMode !== 'cards'): ?>
      <?php endif; ?>

      <!-- KPI cards (analytics style) -->
      <div class="kpis u-mb-16">
        <div class="panel kpi">
          <div class="u-flex u-ai-center u-jc-between">
            <div class="kpi__label"><i class="fi fi-rr-chart-line-up"></i><span>Total scans</span></div>
            <span class="delta <?= ($delta_scans>=0?'delta--up':'delta--down') ?>"><?= ($delta_scans>=0?'+':'') . number_format(abs($delta_scans),1) ?>%</span>
          </div>
          <div class="u-flex u-ai-center u-gap-12 u-mt-6">
            <span class="kpi__icon kpi__icon--links"><i class="fi fi-rr-chart-line-up" aria-hidden="true"></i></span>
            <div class="kpi__value kpi__value--xl"><?= number_format($kpi_scans) ?></div>
          </div>
        </div>
        <div class="panel kpi">
          <div class="u-flex u-ai-center u-jc-between">
            <div class="kpi__label"><i class="fi fi-rr-qrcode"></i><span>Active QR codes</span></div>
            <span class="delta <?= ($delta_active>=0?'delta--up':'delta--down') ?>"><?= ($delta_active>=0?'+':'') . number_format(abs($delta_active),1) ?>%</span>
          </div>
          <div class="u-flex u-ai-center u-gap-12 u-mt-6">
            <span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode" aria-hidden="true"></i></span>
            <div class="kpi__value kpi__value--xl"><?= number_format($kpi_active) ?></div>
          </div>
        </div>
        <div class="panel kpi">
          <div class="u-flex u-ai-center u-jc-between">
            <div class="kpi__label"><i class="fi fi-rr-users"></i><span>Unique visitors</span></div>
            <span class="delta <?= ($delta_visitors>=0?'delta--up':'delta--down') ?>"><?= ($delta_visitors>=0?'+':'') . number_format(abs($delta_visitors),1) ?>%</span>
          </div>
          <div class="u-flex u-ai-center u-gap-12 u-mt-6">
            <span class="kpi__icon kpi__icon--page"><i class="fi fi-rr-users" aria-hidden="true"></i></span>
            <div class="kpi__value kpi__value--xl"><?= number_format($kpi_visitors) ?></div>
          </div>
        </div>
      </div>

      <!-- Success/Error messages -->
      <?php if (isset($_GET['created'])): ?>
        <div class="alert alert--success u-mb-12">
          <i class="fi fi-rr-check" aria-hidden="true"></i>
          <span>QR code created successfully!</span>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['duplicated'])): ?>
        <div class="alert alert--success u-mb-12">
          <i class="fi fi-rr-check" aria-hidden="true"></i>
          <span>QR code duplicated successfully!</span>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['toggled'])): ?>
        <div class="alert alert--success u-mb-12">
          <i class="fi fi-rr-check" aria-hidden="true"></i>
          <span>QR code <?= htmlspecialchars($_GET['toggled']) ?> successfully!</span>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert--success u-mb-12">
          <i class="fi fi-rr-check" aria-hidden="true"></i>
          <span>QR code deleted successfully!</span>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert--error u-mb-12">
          <i class="fi fi-rr-exclamation" aria-hidden="true"></i>
          <span>An error occurred. Please try again.</span>
        </div>
      <?php endif; ?>

      <!-- Header (title + controls + tabs) -->
      <div class="qr-header u-mb-12">
        <div class="qr-header__top">
          <form class="search-pill" method="get" action="/qr-codes">
            <i class="fi fi-rr-search" aria-hidden="true"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search QR…" data-qr-search />
          </form>
          <div class="seg-switch" role="tablist" aria-label="View">
            <a href="#" class="seg-btn is-active" data-view="table" role="tab" aria-selected="true"><i class="fi fi-rr-table"></i> <span>Table view</span> <span class="seg-count">· <?= number_format($totalRows) ?></span></a>
            <a href="#" class="seg-btn" data-view="cards" role="tab" aria-selected="false"><i class="fi fi-rr-apps"></i> <span>Cards view</span> <span class="seg-count">· <?= number_format($totalRows) ?></span></a>
          </div>
          <a class="btn btn--primary" href="/qr/new">+ New</a>
  </div>

      </div>

      <!-- Grid View -->
      <div class="qr-grid" id="qrGrid" data-view="cards" style="display: none;">
  <?php if (!$rows): ?>
          <div class="card">
            <div class="card__body u-ta-center">
              <div class="u-mb-4"><span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode"></i></span></div>
              <div class="h4 u-mt-0">No QR codes yet</div>
              <p class="muted">Create your first QR and start tracking scans.</p>
              <a class="btn btn--primary" href="/qr/new">Create QR</a>
            </div>
          </div>
        <?php else: foreach ($rows as $r): ?>
          <?php 
            $pid = (int)$r['id'];
            // Resolve absolute file paths safely
            $projectRoot = dirname(__DIR__, 2); // /whoizme
            $publicPng  = $projectRoot . '/public/qr/'   . $pid . '.png';
            $storagePng = $projectRoot . '/storage/qr/' . $pid . '.png';

            $img = null;
            // Prefer the public PNG if it exists and is non-empty (cache-bust with mtime)
            if (is_file($publicPng) && filesize($publicPng) > 0) {
              $img = '/qr/' . $pid . '.png?v=' . @filemtime($publicPng);
            } elseif (is_file($storagePng) && filesize($storagePng) > 0) {
              // Inline as data URI if only storage file exists
              $bin = @file_get_contents($storagePng);
              if ($bin !== false) {
                $img = 'data:image/png;base64,' . base64_encode($bin);
              }
            }

            if (!$img) {
              // SVG fallback placeholder (lightweight, no external asset dependency)
              $label = htmlspecialchars($r['title'] ?: ('QR #' . $pid), ENT_QUOTES, 'UTF-8');
              $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">'
                   . '<defs><linearGradient id="g" x1="0" x2="1"><stop offset="0" stop-color="#0b1220"/><stop offset="1" stop-color="#131a2a"/></linearGradient></defs>'
                   . '<rect width="200" height="200" rx="12" ry="12" fill="url(#g)" stroke="#1f2741"/>'
                   . '<rect x="28" y="28" width="24" height="24" fill="#9bb0ff"/><rect x="148" y="28" width="24" height="24" fill="#9bb0ff"/>'
                   . '<rect x="28" y="148" width="24" height="24" fill="#9bb0ff"/>'
                   . '<rect x="76" y="76" width="48" height="48" fill="#2d3a68"/>'
                   . '<text x="100" y="190" text-anchor="middle" font-size="12" fill="#7f89a9">' . $label . '</text>'
                   . '</svg>';
              $img = 'data:image/svg+xml;base64,' . base64_encode($svg);
            }
          ?>
          <div class="qr-card" as="article">
            <div class="qr-card__body">
              <a href="/qr/view.php?id=<?= (int)$r['id'] ?>" aria-label="Open QR details">
                <div class="qr-card__media">
                  <img class="qr-card__img" src="<?= htmlspecialchars($img) ?>" alt="QR preview: <?= htmlspecialchars($r['title'] ?: ('QR #'.(int)$r['id'])) ?>" loading="lazy" decoding="async">
                </div>
              </a>
              <div class="qr-card__title"><?= htmlspecialchars($r['title'] ?: ('QR #'.(int)$r['id'])) ?></div>
              <div class="qr-card__meta">• <?= (int)($r['scans'] ?? 0) ?> scans • <?= date('M d', strtotime((string)$r['created_at'])) ?></div>
              <div class="qr-card__actions">
                <a class="btn btn--ghost btn--sm" href="/qr/view.php?id=<?= (int)$r['id'] ?>">View</a>
                <button class="btn btn--ghost btn--sm" data-copy="/qrgo.php?q=<?= (int)$r['id'] ?>">Copy link</button>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Table View (default) -->
      <?php
        $qsType = strtolower(trim($_GET['type'] ?? 'all'));
        if ($qsType === 'stores' || $qsType === 'app') $clientActive = 'appstores';
        elseif ($qsType === 'image') $clientActive = 'images';
        else $clientActive = $qsType;
        $clientAllowed = ['all','url','vcard','text','email','wifi','pdf','appstores','images'];
        if (!in_array($clientActive, $clientAllowed, true)) $clientActive = 'all';
      ?>
      <div class="qr-list card">
        <header class="qr-list__header">
          <h2 class="qr-list__title">QR Codes</h2>
          <nav class="qr-list__tabs" role="tablist" aria-label="Filter QR type" data-current-type="<?= htmlspecialchars($clientActive) ?>">
            <button class="tabs__item" role="tab" data-type="all" aria-selected="<?= $clientActive==='all'?'true':'false' ?>">All</button>
            <button class="tabs__item" role="tab" data-type="url" aria-selected="<?= $clientActive==='url'?'true':'false' ?>">URL</button>
            <button class="tabs__item" role="tab" data-type="vcard" aria-selected="<?= $clientActive==='vcard'?'true':'false' ?>">vCard</button>
            <button class="tabs__item" role="tab" data-type="text" aria-selected="<?= $clientActive==='text'?'true':'false' ?>">Text</button>
            <button class="tabs__item" role="tab" data-type="email" aria-selected="<?= $clientActive==='email'?'true':'false' ?>">E‑mail</button>
            <button class="tabs__item" role="tab" data-type="wifi" aria-selected="<?= $clientActive==='wifi'?'true':'false' ?>">Wi‑Fi</button>
            <button class="tabs__item" role="tab" data-type="pdf" aria-selected="<?= $clientActive==='pdf'?'true':'false' ?>">PDF</button>
            <button class="tabs__item" role="tab" data-type="appstores" aria-selected="<?= $clientActive==='appstores'?'true':'false' ?>">App Stores</button>
            <button class="tabs__item" role="tab" data-type="images" aria-selected="<?= $clientActive==='images'?'true':'false' ?>">Images</button>
          </nav>
        </header>
        <div class="qr-list__body">
          <div class="qr-table" id="qrTable" data-view="table">
        <?php if (!$rows): ?>
          <div class="card">
            <div class="card__body u-ta-center">
              <div class="u-mb-4"><span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode"></i></span></div>
              <div class="h4 u-mt-0">No QR codes yet</div>
              <p class="muted">Create your first QR and start tracking scans.</p>
              <a class="btn btn--primary" href="/qr/new">Create QR</a>
            </div>
          </div>
  <?php else: ?>
          <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
                  <th class="qr-thumb">QR</th>
                  <th class="qr-title">Title</th>
                  <th class="qr-destination">Destination</th>
                  <th class="qr-scans">Scans</th>
                  <th class="qr-created">Created</th>
                  <th class="qr-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
                  <?php
                    $qrId = (int)$r['id'];
                    $title = htmlspecialchars($r['title'] ?: ('QR #' . $qrId));
                    $payload = $r['payload'];
                    $type = $r['type'];
                    $scans = (int)($r['scans'] ?? 0);
                    $created = date('M d, Y', strtotime((string)$r['created_at']));
                    $shortCode = $r['code'] ?? null;
                    $styleJson = $r['style_json'] ?? '{}';
                    
                    // Format destination text based on type
                    $destinationText = '';
                    switch ($type) {
                        case 'url':
                            $url = $payload;
                            if (preg_match('/^https?:\/\/([^\/]+)/', $url, $matches)) {
                                $destinationText = $matches[1];
                            } else {
                                $destinationText = $url;
                            }
                            break;
                        case 'vcard':
                            if (preg_match('/FN:([^\r\n]+)/', $payload, $matches)) {
                                $destinationText = 'vCard • ' . $matches[1];
                            } else {
                                $destinationText = 'vCard • Contact';
                            }
                            break;
                        case 'text':
                            $destinationText = substr($payload, 0, 80);
                            if (strlen($payload) > 80) {
                                $destinationText .= '...';
                            }
                            break;
                        case 'email':
                            if (preg_match('/mailto:([^?]+)/', $payload, $matches)) {
                                $destinationText = 'mailto:' . $matches[1];
                            } else {
                                $destinationText = 'Email';
                            }
                            break;
                        case 'wifi':
                            if (preg_match('/S:([^;]+)/', $payload, $matches)) {
                                $destinationText = 'WIFI:' . $matches[1];
                            } else {
                                $destinationText = 'WiFi Network';
                            }
                            break;
                        case 'pdf':
                        case 'app':
                        case 'image':
                            if (preg_match('/^https?:\/\/([^\/]+)/', $payload, $matches)) {
                                $destinationText = ucfirst($type) . ' • ' . $matches[1];
                            } else {
                                $destinationText = ucfirst($type);
                            }
                            break;
                        default:
                            $destinationText = $payload;
                    }
                  ?>
                  <?php 
                    $clientType = ($type==='stores'?'appstores':($type==='images'?'images':$type));
                    $normalized_q = strtolower(trim(($r['title'] ?? '').' '.($destinationText ?? '').' '.($payload ?? '')));
                  ?>
                  <tr class="qr-row" data-type="<?= htmlspecialchars($clientType) ?>" data-q="<?= htmlspecialchars($normalized_q, ENT_QUOTES) ?>" data-qr-id="<?= $qrId ?>" data-qr-payload="<?= htmlspecialchars($payload, ENT_QUOTES) ?>">
                    <td class="qr-cell qr-cell--thumb">
                      <div class="qr-thumb-container">
                        <div class="qr-thumb" data-payload="<?= htmlspecialchars($payload) ?>" aria-label="QR preview: <?= $title ?>"></div>
                      </div>
                    </td>
                    <td class="qr-cell qr-cell--details">
                      <a href="/qr/view.php?id=<?= $qrId ?>" class="qr-title__link"><h3 class="qr-title__text"><?= $title ?></h3></a>
                      <div class="qr-title__meta">
                        <span class="badge badge--sm badge--primary"><?= strtoupper($type) ?></span>
                        <?php if (isset($r['is_active']) && $r['is_active'] == 0): ?><span class="badge badge--sm badge--muted">Hidden</span><?php endif; ?>
                      </div>
                      <div class="qr-destination__text" title="<?= htmlspecialchars($payload) ?>"><?= htmlspecialchars($destinationText) ?></div>
                      <div class="qr-meta__row"><span class="qr-meta__item"><?= $created ?></span> · <span class="qr-meta__item"><?= number_format($scans) ?> scans</span></div>
          </td>
                    <td class="qr-cell qr-cell--actions">
                      <div class="qr-actions">
                        <a href="/qr/view.php?id=<?= $qrId ?>" class="qr-actions__link" data-role="view">View details</a>
                        <button class="btn btn--action" data-action="download-svg" data-id="<?= $qrId ?>" aria-label="Download SVG"><i class="fi fi-rr-download"></i></button>
                        <div class="qr-actions__kebab">
                          <button class="btn btn--action" data-action="menu-toggle" aria-label="More actions" aria-expanded="false" aria-controls="menu-<?= $qrId ?>"><i class="fi fi-rr-menu-dots"></i></button>
                          <div class="kebab-menu" role="menu" id="menu-<?= $qrId ?>">
                            <a class="kebab-menu__item" href="/qr/new?id=<?= $qrId ?>" data-action="edit"><i class="fi fi-rr-edit"></i><span>Edit</span></a>
                            <form method="post" action="/qr/delete.php" onsubmit="return confirm('Are you sure?')">
                              <input type="hidden" name="id" value="<?= $qrId ?>">
                              <button type="submit" class="kebab-menu__item" data-action="delete" data-id="<?= $qrId ?>"><i class="fi fi-rr-trash"></i><span>Delete</span></button>
                            </form>
                          </div>
                        </div>
                      </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($totalRows > $per): ?>
        <div class="pagination-wrapper u-mt-16">
          <div class="pagination">
            <?php
              $totalPages = ceil($totalRows / $per);
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $page + 2);
            ?>
            
            <?php if ($page > 1): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination__link">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                 class="pagination__link <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
              <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination__link">Next &raquo;</a>
            <?php endif; ?>
          </div>
          
          <div class="pagination-info">
            Showing <?= number_format($off + 1) ?>-<?= number_format(min($off + $per, $totalRows)) ?> of <?= number_format($totalRows) ?> QR codes
          </div>
        </div>
      <?php endif; ?>

    </section>
  </div>

</main>

<!-- Include QR code library for client-side generation -->
<script src="/assets/js/qrcode.min.js"></script>
<!-- Include QR list enhancement module (list view only) -->
<script src="/assets/js/qr.js" defer></script>
<script src="/assets/js/qr-list.js" defer></script>

<script>
// View switching with localStorage persistence
document.addEventListener('DOMContentLoaded', function() {
  const viewBtns = document.querySelectorAll('[data-view]');
  const views = document.querySelectorAll('#qrGrid, #qrTable');
  const STORAGE_KEY = 'qr_view_preference';
  
  // Get saved preference or default to 'table'
  const savedView = localStorage.getItem(STORAGE_KEY) || 'table';
  
  // Function to switch views
  function switchView(targetView) {
    // Update button states
    viewBtns.forEach(b => {
      b.classList.remove('is-active');
      b.setAttribute('aria-selected', 'false');
    });
    
    // Find and activate the correct button
    const activeBtn = document.querySelector(`[data-view="${targetView}"]`);
    if (activeBtn) {
      activeBtn.classList.add('is-active');
      activeBtn.setAttribute('aria-selected', 'true');
    }
    
    // Show/hide views
    views.forEach(view => {
      if (view.getAttribute('data-view') === targetView) {
        view.style.display = '';
      } else {
        view.style.display = 'none';
      }
    });
    
    // Save preference
    localStorage.setItem(STORAGE_KEY, targetView);
  }
  
  // Initialize with saved preference
  switchView(savedView);
  
  // Handle button clicks
  viewBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const targetView = this.getAttribute('data-view');
      switchView(targetView);
    });
  });
});

// Copy helper (fallback for grid view)
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('[data-copy]');
  if(!btn) return;
  const val = btn.getAttribute('data-copy');
  navigator.clipboard?.writeText(val);
  btn.textContent = 'Copied';
  setTimeout(()=>{ btn.textContent='Copy link'; }, 1200);
});
</script>

<?php include __DIR__ . '/../partials/app_footer.php'; ?>