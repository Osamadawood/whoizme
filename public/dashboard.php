<?php
// Boot & guards
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/events.php';

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
        <div class="panel kpi">
          <div class="panel__title">Total clicks</div>
          <div class="kpi__value">69,215</div>
          <div class="kpi__delta text-success">+4.52% today</div>
        </div>
        <div class="panel kpi">
          <div class="panel__title">Active links</div>
          <div class="kpi__value">1,593</div>
          <div class="kpi__delta text-success">+2.28% this week</div>
        </div>
        <div class="panel kpi">
          <div class="panel__title">QR scans</div>
          <div class="kpi__value">23,008</div>
          <div class="kpi__delta text-danger">−0.73% today</div>
        </div>
      </div>

      <!-- Two-up analytics cards -->
      <div class="twoup u-mb-16">
        <div class="panel">
          <div class="panel__body">
            <?php $trend_days = ($uid && isset($pdo)) ? wz_event_series($pdo, $uid, $p) : []; ?>
            <section class="dash-card dash-card--trend" data-trend-root>
              <header class="dash-card__head">
                <h3 class="dash-card__title">Traffic Trend</h3>
                <div class="pill-switch" data-trend-tabs>
                  <button type="button" data-p="7d"  class="pill is-active">7d</button>
                  <button type="button" data-p="30d" class="pill">30d</button>
                  <button type="button" data-p="90d" class="pill">90d</button>
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
              <span>Recent activity</span>
              <a href="/activity" class="link-muted view-all" aria-label="View all activity">View all</a>
            </div>
            <ul class="activity list-plain">
              <?php
                $recentItems = ($uid && isset($pdo)) ? (function() use($pdo,$uid){
                  try { return wz_recent_activity($pdo, $uid, 6); } catch(Throwable $e){ return []; }
                })() : [];
              ?>
              <?php if (!$recentItems): ?>
                <li class="activity__item"><span class="muted">No recent activity yet — create your first link</span></li>
              <?php else: foreach ($recentItems as $it):
                $icon = $it['type']==='link' ? '<i class="fi fi-rr-link-simple"></i>' : ($it['type']==='qr' ? '<i class="fi fi-rr-qr-code"></i>' : '<i class="fi fi-rr-file"></i>');
                $delta = (int)($it['delta'] ?? 0);
              ?>
              <li class="activity__item">
                <span class="activity__icon" aria-hidden="true"><?= $icon ?></span>
                <span class="activity__main"><strong><?= htmlspecialchars($it['title']) ?></strong> <span class="muted">· <?= $delta ?> <?= $it['type']==='qr'?'scans':'clicks' ?> · <?= htmlspecialchars($it['at']) ?></span></span>
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
              <?php else: foreach ($top as $r): ?>
                <tr>
                  <td><?php echo htmlspecialchars($r['label']); ?></td>
                  <td><span class="badge"><?php echo htmlspecialchars(strtoupper($r['item_type'])); ?></span></td>
                  <td><?php echo number_format((int)($r['total'] ?? 0)); ?></td>
                  <td><span class="badge badge--up"><?php echo (int)($r['today'] ?? 0); ?> today</span></td>
                  <td><?php echo !empty($r['first_seen']) ? date('M d', strtotime($r['first_seen'])) : '-'; ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
          <div data-top-paging class="u-flex u-gap-8 u-mt-16"></div>
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
    let html=''; const make=(n,l=n)=>`<button class="u-btn u-btn--ghost ${n===curr?'is-active':''}" data-page="${n}">${l}</button>`;
    if (curr>1) html+=make(curr-1,'&laquo;');
    const start=Math.max(1,curr-2), end=Math.min(max,curr+2);
    for (let i=start;i<=end;i++) html+=make(i);
    if (curr<max) html+=make(curr+1,'&raquo;');
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
<?php include __DIR__ . '/partials/app_footer.php'; ?>