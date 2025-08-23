<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$q   = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = max(1, min(50, (int)($_GET['per'] ?? 12)));
$off  = ($page - 1) * $per;

// KPIs (simple queries; can be optimized later)
$activeStmt = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE user_id=:uid AND is_active IN (1,'1','active','ACTIVE')");
$activeStmt->execute([':uid'=>$uid]);
$kpi_active = (int)$activeStmt->fetchColumn();

$scanStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id=:uid AND item_type='qr' AND type='scan'");
$scanStmt->execute([':uid'=>$uid]);
$kpi_scans = (int)$scanStmt->fetchColumn();
$kpi_visitors = $kpi_scans; // placeholder until we track unique visitors

// List data
$where = 'q.user_id = :uid';
$params = [':uid'=>$uid];
if ($q !== '') { $where .= ' AND (q.title LIKE :kw OR q.payload LIKE :kw OR q.code LIKE :kw)'; $params[':kw'] = "%$q%"; }

$sql = "SELECT q.id, q.code, q.type, q.title, q.payload, q.is_active, q.created_at,
               (SELECT COUNT(*) FROM events e WHERE e.user_id=:uid AND e.item_type='qr' AND e.item_id=q.id) AS scans
        FROM qr_codes q
        WHERE $where
        ORDER BY q.created_at DESC
        LIMIT :per OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $off, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$page_title = 'QR Codes';
include __DIR__ . '/../partials/app_header.php';
?>

<main class="dashboard">

  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>

  <div class="container dash-grid" role="region" aria-label="QR Codes layout">

    <section class="maincol">

      <!-- KPI cards -->
      <div class="kpis u-mb-16">
        <div class="panel kpi">
          <div class="panel__title">Active QR Codes</div>
          <div class="u-flex u-ai-center u-gap-12">
            <span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode" aria-hidden="true"></i></span>
            <div class="kpi__value"><?= number_format($kpi_active) ?></div>
          </div>
        </div>
        <div class="panel kpi">
          <div class="panel__title">Total Scans</div>
          <div class="u-flex u-ai-center u-gap-12">
            <span class="kpi__icon kpi__icon--links"><i class="fi fi-rr-chart-line-up" aria-hidden="true"></i></span>
            <div class="kpi__value"><?= number_format($kpi_scans) ?></div>
          </div>
        </div>
        <div class="panel kpi">
          <div class="panel__title">Unique Visitors</div>
          <div class="u-flex u-ai-center u-gap-12">
            <span class="kpi__icon kpi__icon--page"><i class="fi fi-rr-users" aria-hidden="true"></i></span>
            <div class="kpi__value"><?= number_format($kpi_visitors) ?></div>
          </div>
        </div>
      </div>

      <!-- Header -->
      <div class="u-flex u-ai-center u-jc-between u-mb-12">
        <h3 class="h3 u-m-0">Your QR Codes</h3>
        <div class="u-flex u-gap-8">
          <form class="u-flex u-gap-8" method="get" action="/qr-codes">
            <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search QR…" />
            <button class="btn btn--ghost" type="submit">Search</button>
          </form>
          <a class="btn btn--primary" href="/qr/new.php">+ New</a>
        </div>
      </div>

      <style>
        /* Grid */
        .qr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px}

        /* Card */
        .qr-card{border:1px solid var(--border);border-radius:16px;background:var(--surface);transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease}
        .qr-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);border-color:color-mix(in srgb,var(--primary) 30%,var(--border))}
        .qr-card__body{padding:16px}

        /* Media wrapper keeps height while image lazy-loads */
        .qr-card__media{position:relative;border-radius:12px;overflow:hidden;border:1px solid var(--border);background:linear-gradient(180deg,#0b1220,#131a2a)}
        .qr-card__media::before{content:"";display:block;aspect-ratio:1/1}
        .qr-card__img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain}

        .qr-card__title{font-weight:600;margin:.75rem 0 .35rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .qr-card__meta{color:var(--text-muted);font-size:.9rem}
        .qr-card__actions{display:flex;gap:8px;margin-top:.9rem}
      </style>

      <div class="qr-grid" id="qrGrid">
        <?php if (!$rows): ?>
          <div class="card">
            <div class="card__body u-ta-center">
              <div class="u-mb-4"><span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode"></i></span></div>
              <div class="h4 u-mt-0">No QR codes yet</div>
              <p class="muted">Create your first QR and start tracking scans.</p>
              <a class="btn btn--primary" href="/qr/new.php">Create QR</a>
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

    </section>
  </div>

</main>

<script>
// Copy helper
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