<?php require __DIR__ . '/../includes/auth.php'; ?>
<?php 
  require_once __DIR__ . '/../includes/bootstrap.php';
  if (auth_role() === 'admin') {
    include INC_PATH . '/partials/admin_header.php';
  } else {
    include INC_PATH . '/partials/user_header.php';
  }
?>
<?php
// ------------------------------------------------------
// Dashboard data
// ------------------------------------------------------
// Accept both session keys used across app (user_id | uid)
$uid = (int)($_SESSION['user_id'] ?? ($_SESSION['uid'] ?? 0));

$qrsCount       = 0;   // what we show in the tile
$qrsCountDb     = 0;   // strict count from qr_codes table
$linksCount     = 0; 
$recentQrs      = [];
$recentLinks    = [];
$username       = '';
$userCreatedAt  = '';

$totalClicks    = 0;   // all clicks across user short links
$qrScans        = 0;   // placeholder for future QR/landing-page breakdown
$landingClicks  = 0;   // placeholder until landing pages ship

try {
  if (isset($db) && is_object($db)) {
    // fetch username (for virtual profile QR fallback)
    try {
      $stU = $db->pdo()->prepare("SELECT username, created_at FROM users WHERE id=? LIMIT 1");
      $stU->execute([$uid]);
      if ($rowU = $stU->fetch()) {
        $username = trim((string)$rowU['username']);
        $userCreatedAt = (string)$rowU['created_at'];
      }
    } catch (Throwable $e) {}

    // Count user QRs from table (real records)
    try {
      $st = $db->pdo()->prepare("SELECT COUNT(*) FROM qr_codes WHERE user_id=?");
      $st->execute([$uid]);
      $qrsCountDb = (int)$st->fetchColumn();
    } catch (Throwable $e) { $qrsCountDb = 0; }

    // Count user links (support both short_links and links tables)
    foreach ([
      ['tbl' => 'short_links', 'cols' => ['id','code','target_url','created_at']],
      ['tbl' => 'links',       'cols' => ['id','code','target','created_at']],
    ] as $meta) {
      try {
        $st2 = $db->pdo()->prepare("SELECT COUNT(*) FROM {$meta['tbl']} WHERE user_id=?");
        $st2->execute([$uid]);
        $linksCount = (int)$st2->fetchColumn();
        if ($linksCount > 0) { break; }
      } catch (Throwable $e) { /* table not found -> try next */ }
    }

    // Recent QRs (real records)
    try {
      $st3 = $db->pdo()->prepare("SELECT id, code, title, type, created_at FROM qr_codes WHERE user_id=? ORDER BY id DESC LIMIT 5");
      $st3->execute([$uid]);
      $recentQrs = $st3->fetchAll();
    } catch (Throwable $e) { $recentQrs = []; }

    // Fallback: virtual profile QR if user has username but no rows yet
    if (count($recentQrs) === 0 && $username !== '') {
      $recentQrs = [[
        'id'         => 0,
        'code'       => $username,
        'title'      => 'Profile QR',
        'type'       => 'profile',
        'created_at' => $userCreatedAt ?: date('Y-m-d H:i:s'),
      ]];
    }

    // Decide what to show in the tile: prefer strict DB count; otherwise fallback list size
    $qrsCount = max($qrsCountDb, count($recentQrs));

    // Recent short links (support both tables)
    foreach ([
      ['tbl' => 'short_links', 'target' => 'target_url'],
      ['tbl' => 'links',       'target' => 'target'],
    ] as $meta) {
      try {
        $st4 = $db->pdo()->prepare("SELECT id, code, {$meta['target']} AS target_url, created_at FROM {$meta['tbl']} WHERE user_id=? ORDER BY id DESC LIMIT 5");
        $st4->execute([$uid]);
        $recentLinks = $st4->fetchAll();
        if ($recentLinks) { break; }
      } catch (Throwable $e) {}
    }

    // Total clicks across user's short links
    try {
      $stClicks = $db->pdo()->prepare("
        SELECT COUNT(*) 
        FROM short_link_hits sh 
        JOIN short_links sl ON sl.code = sh.code 
        WHERE sl.user_id = ?
      ");
      $stClicks->execute([$uid]);
      $totalClicks = (int)$stClicks->fetchColumn();
    } catch (Throwable $e) { $totalClicks = 0; }
  }
} catch (Throwable $e) {}

$name = htmlspecialchars($_SESSION['name'] ?? '');
$brand = htmlspecialchars($settings['site_name'] ?? 'Whoiz.me');
?>

<style>
/* ===== Admin Dashboard Skin ===== */
:root{
  --bg:#f6f8fb; --panel:#ffffff; --muted:#6b7280; --line:#e5e7eb; --ink:#0b1220;
  --blue:#0d6efd; --green:#198754; --violet:#6f42c1; --shadow:0 10px 30px rgba(2,6,23,.06);
}
body{background:var(--bg)}
.dash-hero{background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:20px 24px; box-shadow:var(--shadow)}
.dash-title{font-weight:800; letter-spacing:.2px; font-size:1.75rem; color:var(--ink)}
.dash-sub{color:var(--muted)}

/* Quick actions panel */
.qa-panel{background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:16px; box-shadow:var(--shadow)}
.qa-row{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px}
@media(max-width:992px){ .qa-row{grid-template-columns:1fr} }
.qa-card{display:flex; align-items:center; gap:16px; background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:18px; transition:.15s ease;}
.qa-card:hover{box-shadow:0 6px 16px rgba(2,6,23,.08)}
.qa-ico{width:64px; height:64px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:30px}
.qa-ico.links{background:rgba(25,135,84,.12); color:var(--green)}
.qa-ico.qr{background:rgba(13,110,253,.12); color:var(--blue)}
.qa-ico.page{background:rgba(111,66,193,.12); color:var(--violet)}
.qa-title{margin:0; font-size:1.05rem; font-weight:700}
.qa-desc{margin:.15rem 0 .5rem; color:var(--muted)}

/* Metrics strip */
.metrics{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px}
@media(max-width:992px){ .metrics{grid-template-columns:1fr} }
.metric{display:flex; align-items:center; gap:12px; background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px 16px; box-shadow:var(--shadow)}
.metric small{color:var(--muted); font-weight:600}
.metric .num{font-weight:800; font-size:2rem; line-height:1; color:var(--ink)}
.chip-muted{display:inline-block; padding:.25rem .6rem; border-radius:999px; background:#eef2ff; color:#3b82f6; font-weight:700; font-size:.85rem}
</style>

<div class="container py-3 py-md-4">
  <!-- Hero (like screenshot) -->
  <div class="dash-hero mb-3 d-flex align-items-center justify-content-between">
    <div>
      <div class="chip-muted mb-2"><?= htmlspecialchars($settings['site_name'] ?? 'Whoiz.me') ?></div>
      <div class="dash-title">Your Connections Platform</div>
      <div class="dash-sub">Manage Links, QR Codes, and Landing Pages (Coming Soon).</div>
    </div>
    <div></div>
  </div>

  <!-- Quick actions panel -->
  <div class="qa-panel mb-3">
    <div class="qa-row">
      <div class="qa-card">
        <div class="qa-ico links"><i class="bi bi-link-45deg"></i></div>
        <div class="flex-grow-1">
          <h3 class="qa-title">Links</h3>
          <div class="qa-desc">Create and manage short links, track clicks.</div>
          <a href="/link-stats" class="btn btn-outline-success btn-sm">Go to Links</a>
        </div>
      </div>
      <div class="qa-card">
        <div class="qa-ico qr"><i class="bi bi-qr-code"></i></div>
        <div class="flex-grow-1">
          <h3 class="qa-title">QR Codes</h3>
          <div class="qa-desc">Generate QR codes and view scans.</div>
          <a href="/qr/" class="btn btn-outline-primary btn-sm">Go to QR Codes</a>
        </div>
      </div>
      <div class="qa-card landingBox">
        <span class="badge-soon">Soon</span>

        <div class="landingBoxContent">
          <div class="qa-ico page"><i class="bi bi-file-earmark-richtext"></i></div>
          <div class="flex-grow-1">
            <h3 class="qa-title">Landing Pages</h3>
            <div class="qa-desc">Launch mini pages to showcase your links.</div>
          </div>
        </div>
        
      </div>
    </div>
  </div>

  <!-- Metrics strip (like screenshot) -->
  <div class="metrics mb-4">
    <div class="metric">
      <i class="bi bi-activity fs-5 text-primary"></i>
      <div>
        <small>ENGAGEMENTS</small>
        <div class="num"><?= (int)$totalClicks ?></div>
      </div>
    </div>
    <div class="metric">
      <i class="bi bi-link-45deg fs-5 text-success"></i>
      <div>
        <small>LINK CLICKS</small>
        <div class="num"><?= (int)$totalClicks ?></div>
      </div>
    </div>
    <div class="metric">
      <i class="bi bi-qr-code fs-5 text-dark"></i>
      <div>
        <small>LANDING PAGES CLICKS</small>
        <div class="num"><?= (int)$landingClicks ?></div>
      </div>
    </div>
  </div>

  <!-- Placeholder sections to be refined later (kept minimal as per new design) -->
  <div class="alert alert-light border" role="alert">
    We’ll add more blocks here (recent items, campaigns cards, etc.) to match the full dashboard experience.
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>