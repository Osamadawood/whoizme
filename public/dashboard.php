<?php
// Boot & guards
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/events.php';
require_once __DIR__ . '/../includes/analytics.php';

$page_title = 'Dashboard';
include __DIR__ . '/partials/app_header.php';
?>

<?php
  // Shared params
  $p = $_GET['p'] ?? '7d';
  $p = in_array($p, ['7d','30d','90d'], true) ? $p : '7d';

  $t = $_GET['t'] ?? 'all';
  $t = in_array($t, ['all','links','qr','pages'], true) ? $t : 'all';
  $t_param = [
    'all'   => 'all',
    'links' => 'link',
    'qr'    => 'qr',
    'pages' => 'page',
  ][$t];

  $uid = (int)($_SESSION['user_id'] ?? 0);
  // KPIs
  $kpis = [];
  if ($uid && isset($pdo) && $pdo instanceof PDO) {
    try { $kpis = wz_kpis($pdo, $uid); } catch (Throwable $e) { $kpis = []; }
  }
  $total_clicks = number_format((int)($kpis['total_clicks'] ?? 0));
  $active_links = number_format((int)($kpis['active_links'] ?? 0));
  $total_scans  = number_format((int)($kpis['total_scans']  ?? 0));
  $delta_today_clicks = (float)($kpis['delta_today_clicks'] ?? 0.0);
  $delta_week_active  = (float)($kpis['delta_week_active_links'] ?? 0.0);
  $delta_today_scans  = (float)($kpis['delta_today_scans'] ?? 0.0);
  $fmtDelta = function(float $v, string $suffix): array {
    $cls = $v >= 0 ? 'text-success' : 'text-danger';
    $sign = $v >= 0 ? '+' : '−';
    return [ 'cls' => $cls, 'text' => $sign . number_format(abs($v), 2) . '% ' . $suffix ];
  };
  $d1 = $fmtDelta($delta_today_clicks, 'today');
  $d2 = $fmtDelta($delta_week_active, 'this week');
  $d3 = $fmtDelta($delta_today_scans, 'today');
  // Sorting & pagination
  $sort = $_GET['sort'] ?? 'total';
  $dir  = $_GET['dir'] ?? 'desc';
  $page = (int)($_GET['page'] ?? 1);
  $per  = (int)($_GET['per'] ?? 10);
  if (!in_array($sort, ['total','today','first_seen'], true)) $sort = 'total';
  if (!in_array(strtolower($dir), ['asc','desc'], true)) $dir = 'desc';
  if ($page < 1) $page = 1;
  if ($per < 1 || $per > 100) $per = 10;
?>

<main class="dashboard">


    <!-- ============ Sidebar ============ -->
    <?php
      $sidebar_path = __DIR__ . '/partials/app_sidebar.php';
      $sidebar_fixed = true; // hint for fixed/sticky layout (optional in partial)
      if (file_exists($sidebar_path)) {
        include $sidebar_path;
      } else {
        error_log("[whoizme] Sidebar partial missing: {$sidebar_path}");
        echo '<aside class="sidebar sidebar--placeholder">'
           . '<div class="sidebar__section"><strong>Sidebar</strong></div>'
           . '<p class="muted">Sidebar partial not found. Please add <code>/partials/app_sidebar.php</code>.</p>'
           . '</aside>';
      }
    ?>

  <div class="container dash-grid" role="region" aria-label="Dashboard layout">

  <!-- ============ Topbar ============ -->
  <div class="container topbar--inset" role="region" aria-label="Primary toolbar">
    <?php
      // Breadcrumbs: dashboard is root, so we do not repeat "Home"
      $breadcrumbs = [ ['label' => 'Dashboard', 'href' => '/dashboard'] ];

      // Topbar config handed to the partial (the partial should read $topbar if present)
      $topbar = [
        'search' => [
          'enabled'     => true,
          'placeholder' => 'Search links & QR…',
          'action'      => '/search',
          'name'        => 'q',
          'method'      => 'GET',
        ],
        'actions' => [
          'create_button' => [
            'label' => '+ Create new',
            'id'    => 'js-create-new',
            'data'  => [ 'toggle' => 'create-dialog' ]
          ],
          'theme_toggle' => true,   // light/dark switch
          'language'     => [
            'enabled' => true,
            'current' => (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'EN'),
            'options' => ['EN','AR']
          ],
          'profile'      => [
            // the partial will fallback to initial if avatar is empty
            'name'   => (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'),
            'avatar' => (isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : ''),
          ]
        ]
      ];

      include __DIR__ . '/partials/app_topbar.php';
    ?>
  </div>

    <!-- ============ Main content ============ -->
    <section class="maincol">

      <!-- KPIs -->
      <div class="kpis u-mb-16">
        <div class="panel kpi" aria-live="polite">
          <div class="panel__title">Total clicks</div>
          <div class="kpi__value"><?= $total_clicks ?></div>
          <div class="kpi__delta <?= $d1['cls'] ?>"><?= htmlspecialchars($d1['text']) ?></div>
        </div>
        <div class="panel kpi" aria-live="polite">
          <div class="panel__title">Active links</div>
          <div class="kpi__value"><?= $active_links ?></div>
          <div class="kpi__delta <?= $d2['cls'] ?>"><?= htmlspecialchars($d2['text']) ?></div>
        </div>
        <div class="panel kpi" aria-live="polite">
          <div class="panel__title">QR scans</div>
          <div class="kpi__value"><?= $total_scans ?></div>
          <div class="kpi__delta <?= $d3['cls'] ?>"><?= htmlspecialchars($d3['text']) ?></div>
        </div>
      </div>

      <!-- Two-up analytics cards: trend (2/3) + featured (1/3) on lg+ -->
      <div class="twoup u-mb-16">
        <style>
          @media (min-width: 768px){ .twoup{ display:grid; gap:24px; grid-template-columns: 1fr .5fr; align-items:start; } }
          .featured-list{ list-style:none; margin:0; padding:0; }
          .featured-item + .featured-item{ margin-top:10px; }
          .featured-item > a.tile{ display:flex; align-items:center; gap:16px; padding:12px; border-radius:12px; border:1px solid var(--border); background: var(--surface); transition: transform .15s ease, background-color .15s ease; color: inherit; text-decoration:none; }
          .featured-item > a.tile:hover{ transform: translateY(-1px); background: color-mix(in srgb, var(--primary) 6%, transparent); }
          .chip{ width:56px; height:56px; display:flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid var(--border); color: var(--primary); background: rgba(75,107,251,.08); font-size:24px; }
          .thumb{ width:56px; height:56px; border-radius:12px; object-fit:cover; border:1px solid var(--border); background: #111827; }
          .meta{ color: var(--text-muted); font-size: 12px; }
          .spacer{ flex:1; }
        </style>

        <div class="panel">
          <div class="panel__body">
            <?php $trend_days = ($uid && isset($pdo)) ? wz_event_series($pdo, $uid, $p) : []; ?>
            <section class="dash-card dash-card--trend" data-trend-root>
              <header class="dash-card__head">
                <h3 class="dash-card__title">Traffic Trend</h3>
                <div class="filters segmented" data-trend-tabs>
                  <button type="button" data-p="7d"  class="segmented__btn is-active">7d</button>
                  <button type="button" data-p="30d" class="segmented__btn">30d</button>
                  <button type="button" data-p="90d" class="segmented__btn">90d</button>
                </div>
              </header>

              <!-- Chart goes here -->
              <div id="trend-chart" class="trend-chart" aria-label="Daily traffic trend" role="img"></div>

              <noscript>
                <ul class="trend-fallback">
                  <?php foreach (($trend_days ?? []) as $d): ?>
                    <li><?= htmlspecialchars($d['date']) ?> — <?= (int)($d['total'] ?? 0) ?></li>
                  <?php endforeach; ?>
                </ul>
              </noscript>
            </section>
          </div>
        </div>
        <div class="panel">
          <div class="panel__body">
            <div class="panel__title u-flex u-ai-center u-jc-between">
              <span>Latest links &amp; QR</span>
            </div>
            <?php
              $featured = ($uid && isset($pdo)) ? (function() use($pdo,$uid,$p){
                try { return wz_featured_items($pdo, $uid, $p, 4); } catch(Throwable $e){ return []; }
              })() : [];
            ?>
            <ul class="featured-list" data-featured-root aria-busy="false">
              <?php if (!$featured): ?>
                <li class="featured-item"><div class="chip">★</div><div><div><strong>No highlights yet</strong></div><div class="meta">Create your first link or QR</div></div></li>
              <?php else: foreach ($featured as $it): $href = ($it['type']==='qr'?'/qr':'/links'); ?>
                <li class="featured-item">
                  <a class="tile" href="<?= $href ?>">
                    <?php if (!empty($it['thumb'])): ?>
                      <img class="thumb" src="<?= htmlspecialchars($it['thumb']) ?>" alt=""/>
                    <?php else: ?>
                      <div class="chip" aria-hidden="true"><?= $it['type']==='qr'?'<i class="fi fi-rr-qrcode" aria-hidden="true"></i>':'<i class="fi fi-rr-link" aria-hidden="true"></i>' ?></div>
                    <?php endif; ?>
                    <div class="info">
                      <div class="title"><strong><?= htmlspecialchars($it['label'] ?? '') ?></strong></div>
                      <div class="meta">• <?= (int)($it['today']??0) ?> today • <?= (int)($it['total']??0) ?> total<?= !empty($it['created_at'])?(' • '.date('M d', strtotime($it['created_at']))):'' ?></div>
                    </div>
                    <span class="spacer"></span>
                    <span aria-hidden="true">›</span>
                  </a>
                </li>
              <?php endforeach; endif; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Table: top items -->
      <div class="panel">
        <div class="panel__body">
          <div class="panel__title">Top links & QR</div>
          <style>
            .type-chip{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;border:1px solid var(--border);background:rgba(75,107,251,.08);color:var(--primary);margin-right:8px}
            .type-chip i{font-size:14px;line-height:1}
            .pager{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
            .pager__link{min-width:36px;height:36px;padding:0 12px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:10px;background:transparent;color:var(--text);text-decoration:none}
            .pager__link:hover{background:color-mix(in srgb, var(--primary) 8%, transparent)}
            .pager__link.is-active{background:var(--primary);color:var(--surface);border-color:transparent}
          </style>
          <div class="u-flex u-ai-center u-jc-between u-mb-4">
            <div class="filters segmented" role="tablist" aria-label="Filter type">
              <a class="segmented__btn <?= $t==='all'?'is-active':'' ?>"   href="#" data-tab="all">All</a>
              <a class="segmented__btn <?= $t==='links'?'is-active':'' ?>" href="#" data-tab="links">Links</a>
              <a class="segmented__btn <?= $t==='qr'?'is-active':'' ?>"    href="#" data-tab="qr">QR</a>
              <a class="segmented__btn <?= $t==='pages'?'is-active':'' ?>" href="#" data-tab="pages">Pages</a>
            </div>
            <a class="btn btn--ghost btn--sm" href="#" data-export>Export CSV</a>
          </div>
          <table class="table" role="table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Clicks/Scans</th>
                <th>Today</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody data-top-table-body>
              <?php $top = ($uid && isset($pdo)) ? wz_top_items($pdo, $uid, $t_param, $p, 10, $sort, $dir, $page, $per) : []; ?>
              <?php if (!$top): ?>
                <tr><td colspan="5" class="muted">No items yet.</td></tr>
              <?php else: foreach ($top as $r):
                $today  = (int)($r['today'] ?? 0);
                $yday   = (int)($r['yesterday'] ?? 0); // may not exist; derive sign via our exported delta if available
                $deltaP = isset($r['delta_today_pct']) ? (float)$r['delta_today_pct'] : null;
                $badgeClass = 'badge'; $deltaText = $today . ' today';
                if ($deltaP !== null) {
                  if ($deltaP > 0) { $badgeClass .= ' badge--up'; }
                  elseif ($deltaP < 0) { $badgeClass .= ' badge--down'; }
                }
              ?>
                <tr>
                  <td>
                    <span class="type-chip" aria-hidden="true">
                      <?php echo ($r['item_type']==='qr') ? '<i class="fi fi-rr-qr-code"></i>' : '<i class="fi fi-rr-link-simple"></i>'; ?>
                    </span>
                    <?php echo htmlspecialchars($r['label']); ?>
                  </td>
                  <td><span class="badge"><?php echo htmlspecialchars(strtoupper($r['item_type'])); ?></span></td>
                  <td><?php echo number_format((int)($r['total'] ?? 0)); ?></td>
                  <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($deltaText); ?></span></td>
                  <td><?php echo !empty($r['first_seen']) ? date('M d', strtotime($r['first_seen'])) : '-'; ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
          <div data-top-paging class="pager u-mt-16" role="navigation" aria-label="Top items pagination"></div>
        </div>
      </div>

    </section>
  </div>

</main>

<script>
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('.segmented__btn');
  if(!btn) return;
  const group = btn.closest('.segmented');
  group.querySelectorAll('.segmented__btn').forEach(b=>b.classList.remove('is-active'));
  btn.classList.add('is-active');
});
</script>
<script defer src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0"></script>
<script defer src="/assets/js/trend.js"></script>
<script defer src="/assets/js/dashboard-loader.js"></script>
<script>
(function(){
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const qs = new URLSearchParams(window.location.search);
  let period = qs.get('p') || '7d';
  let tab    = qs.get('tab') || 'all';
  let page   = parseInt(qs.get('page')||'1',10);
  let per    = parseInt(qs.get('per') || '5',10);
  let sort   = qs.get('sort') || 'today';
  let dir    = qs.get('dir')  || 'desc';

  const $trendCanvas = document.getElementById('trend-canvas');
  const $tabs = document.querySelectorAll('[data-tab]');
  const $periods = document.querySelectorAll('[data-period]');
  const $tableBody = document.querySelector('[data-top-table-body]');
  const $paging = document.querySelector('[data-top-paging]');
  const $exportBtn = document.querySelector('[data-export]');

  let chart;
  function drawTrend(days){
    if (!$trendCanvas) return;
    const labels = (days||[]).map(d=>d.date);
    const values = (days||[]).map(d=>d.total);
    if (!chart) {
      chart = new Chart($trendCanvas, {
        type: 'line',
        data: { labels, datasets: [{ label:'Total', data: values, tension:.3, borderWidth:2, pointRadius:2 }]},
        options: { animation: prefersReduced?false:{duration:600}, responsive:true, plugins:{legend:{display:false}}, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.15)'}} } }
      });
    } else {
      chart.data.labels = labels; chart.data.datasets[0].data = values; chart.update();
    }
  }

  async function loadTrend(){
    const r = await fetch(`/api/analytics/trend.php?p=${period}`, {credentials:'same-origin'});
    const j = await r.json(); drawTrend(j.days||[]);
  }
  async function loadTop(){
    const url = `/api/analytics/top.php?p=${period}&tab=${tab}&page=${page}&per=${per}&sort=${sort}&dir=${dir}`;
    const r = await fetch(url, {credentials:'same-origin'}); const j = await r.json();
    if ($tableBody) {
      $tableBody.innerHTML = (j.rows||[]).map(r=>`<tr><td>${r.title}</td><td class="u-ta-right u-text-muted">${r.type}</td><td class="u-ta-right">${r.total}</td><td class="u-ta-right">${r.today}</td><td class="u-ta-right u-text-muted">${r.first_seen??'-'}</td></tr>`).join('');
    }
    const { total_pages } = j.paging||{ total_pages:1};
    const max = Math.max(1,total_pages); const curr = Math.min(page,max);
    let html=''; const make=(n,l=n,active=false)=>`<a href="#" class="pager__link${active?' is-active':''}" data-page="${n}">${l}</a>`;
    if (curr>1) html+=make(curr-1,'‹');
    const start=Math.max(1,curr-2), end=Math.min(max,curr+2);
    for (let i=start;i<=end;i++) html+=make(i,String(i), i===curr);
    if (curr<max) html+=make(curr+1,'›');
    if ($paging) $paging.innerHTML=html;
  }

  function pushState(){ history.replaceState({}, '', '/dashboard'); }
  async function refreshAll(){ pushState(); await Promise.all([loadTop()]); }

  $tabs.forEach(el=>el.addEventListener('click',e=>{ e.preventDefault(); tab=el.dataset.tab; page=1; refreshAll(); }));
  $periods.forEach(el=>el.addEventListener('click',e=>{ e.preventDefault(); period=el.dataset.period; page=1; refreshAll(); }));
  $paging?.addEventListener('click',e=>{ const b=e.target.closest('[data-page]'); if(!b) return; page=parseInt(b.dataset.page,10); refreshAll(); });
  $exportBtn?.addEventListener('click',()=>{ const q=new URLSearchParams({p:period, tab, sort, dir, per:String(per), page:String(page)}); window.location.href=`/exports/top-items.php?${q.toString()}`; });

  window.addEventListener('DOMContentLoaded', refreshAll);
})();
</script>
<script type="module" id="featured-js">
(function(){
  const root = document.querySelector('[data-featured-root]');
  if (!root) return;
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const qs = new URLSearchParams(location.search);
  const period = qs.get('p') || '7d';
  const skeleton = () => {
    let html='';
    for (let i=0;i<6;i++) html += '<li class="featured-item" aria-hidden="true"><div class="chip">&nbsp;</div><div class="info"><div class="title" style="width:160px;height:14px;background:var(--border);border-radius:6px"></div><div class="meta" style="margin-top:6px;width:220px;height:10px;background:var(--border);border-radius:6px"></div></div></li>';
    root.innerHTML = html;
    root.setAttribute('aria-busy','true');
  };
  const hydrate = (items)=>{
    root.setAttribute('aria-busy','false');
    if (!Array.isArray(items) || !items.length){ root.innerHTML = '<li class="featured-item"><div class="chip">★</div><div><div><strong>No highlights yet</strong></div><div class="meta">Create your first link or QR</div></div></li>'; return; }
    const fmtDate = (d)=>{ try{ const dt=new Date(d.replace(/-/g,'/')); return dt.toLocaleDateString(document.dir==='rtl'?'ar':'en',{month:'short',day:'numeric'});}catch(_){return d||'';} };
    root.innerHTML = items.map(it=>{
      const thumb = it.thumb ? `<img class="thumb" src="${it.thumb}" alt="">` : `<div class="chip" aria-hidden="true">${it.type==='qr'?'<i class="fi fi-rr-qrcode" aria-hidden="true"></i>':'<i class="fi fi-rr-link" aria-hidden="true"></i>'}</div>`;
      const meta = `• ${Number(it.today||0)} today • ${Number(it.total||0)} total` + (it.created_at?` • ${fmtDate(it.created_at)}`:'');
      const href = it.type==='qr'?'/qr':'/links';
      return `<li class="featured-item">${thumb}<div class="info"><div class="title"><strong>${(it.label||'').replace(/</g,'&lt;')}</strong></div><div class="meta">${meta}</div></div><span class="spacer"></span><a class="link-muted" href="${href}" aria-label="Open">›</a></li>`;
    }).join('');
  };
  async function load(){
    skeleton();
    try{
      const r = await fetch(`/api/analytics/featured.php?p=${encodeURIComponent(period)}`, {credentials:'same-origin'});
      const j = await r.json();
      hydrate(j.items||[]);
    }catch(_){
      hydrate([]);
    }
  }
  if (!prefersReduced) requestAnimationFrame(load); else load();
})();
</script>
<?php include __DIR__ . '/partials/app_footer.php'; ?>