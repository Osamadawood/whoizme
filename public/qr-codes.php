<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'QR Codes';
include __DIR__ . '/partials/app_header.php';
?>

<main class="dashboard">

  <?php include __DIR__ . '/partials/app_sidebar.php'; ?>

  <div class="container dash-grid" role="region" aria-label="QR Codes layout">

    <section class="maincol">

      <!-- KPI cards (mocked for now) -->
      <?php
        $kpi_active   = 12;   // TODO: replace with DB query
        $kpi_scans    = 284;  // TODO: replace with DB query
        $kpi_visitors = 173;  // TODO: replace with DB query
      ?>
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

      <!-- Header: search + new -->
      <div class="u-flex u-ai-center u-jc-between u-mb-12">
        <h3 class="h3 u-m-0">Your QR Codes</h3>
        <div class="u-flex u-gap-8">
          <form class="u-flex u-gap-8" method="get" action="/qr-codes">
            <input class="input" type="text" name="q" placeholder="Search QR…" />
            <button class="btn btn--ghost" type="submit">Search</button>
          </form>
          <a class="btn btn--primary" href="/qr/new.php">+ New</a>
        </div>
      </div>

      <!-- QR grid (mocked; replace with DB query later) -->
      <?php
        $qrItems = [
          ['id'=>1,'title'=>'Menu – Downtown','img'=>'/qr/1.png','scans'=>42,'created'=>'2025-08-01'],
          ['id'=>2,'title'=>'Promo Landing','img'=>'/qr/2.png','scans'=>13,'created'=>'2025-08-03'],
          ['id'=>3,'title'=>'vCard – Osama','img'=>'/qr/3.png','scans'=>88,'created'=>'2025-08-05'],
          ['id'=>4,'title'=>'Sticker Booth','img'=>'/qr/4.png','scans'=>0,'created'=>'2025-08-10'],
        ];
      ?>

      <style>
        .qr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
        .qr-card__qr{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:12px;border:1px solid var(--border);background:#0b1220}
        .qr-card__title{font-weight:600;margin:.6rem 0;color:var(--text)}
        .qr-card__meta{color:var(--text-muted);font-size:.9rem}
        .qr-card__actions{display:flex;gap:8px;margin-top:.8rem}
      </style>

      <div class="qr-grid" id="qrGrid">
        <?php if (!$qrItems): ?>
          <div class="card">
            <div class="card__body u-ta-center">
              <div class="u-mb-4"><span class="kpi__icon kpi__icon--qr"><i class="fi fi-rr-qrcode"></i></span></div>
              <div class="h4 u-mt-0">No QR codes yet</div>
              <p class="muted">Create your first QR and start tracking scans.</p>
              <a class="btn btn--primary" href="/qr/new.php">Create QR</a>
            </div>
          </div>
        <?php else: foreach ($qrItems as $q): ?>
          <div class="card">
            <div class="card__body">
              <img class="qr-card__qr" src="<?= htmlspecialchars($q['img']) ?>" alt="QR preview">
              <div class="qr-card__title"><?= htmlspecialchars($q['title']) ?></div>
              <div class="qr-card__meta">• <?= (int)$q['scans'] ?> scans • <?= date('M d', strtotime($q['created'])) ?></div>
              <div class="qr-card__actions">
                <a class="btn btn--ghost btn--sm" href="/qr/view.php?id=<?= (int)$q['id'] ?>">View</a>
                <button class="btn btn--ghost btn--sm" data-copy="/r/<?= (int)$q['id'] ?>">Copy link</button>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </section>
  </div>

</main>

<script>
// Progressive enhancement: optional AJAX refresh if endpoint exists
(async function(){
  try{
    const res = await fetch('/api/qr/recent.php', {credentials:'same-origin'});
    if(!res.ok) return;
    const data = await res.json();
    if(!data || !Array.isArray(data.items)) return;
    const grid = document.getElementById('qrGrid');
    grid.innerHTML = data.items.map((q)=>`
      <div class="card"><div class="card__body">
        <img class="qr-card__qr" src="${q.img||'/assets/img/qr-placeholder.png'}" alt="QR preview"/>
        <div class="qr-card__title">${(q.title||'').replace(/</g,'&lt;')}</div>
        <div class="qr-card__meta">• ${Number(q.scans||0)} scans • ${q.created||''}</div>
        <div class="qr-card__actions">
          <a class="btn btn--ghost btn--sm" href="/qr/view.php?id=${q.id}">View</a>
          <button class="btn btn--ghost btn--sm" data-copy="${q.short||''}">Copy link</button>
        </div>
      </div></div>
    `).join('');
  }catch(_){/* ignore */}
})();

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

<?php include __DIR__ . '/partials/app_footer.php'; ?>


