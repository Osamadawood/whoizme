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
      $breadcrumbs = [ ['label' => 'Dashboard', 'href' => '/dashboard.php'] ];

      // Topbar config handed to the partial (the partial should read $topbar if present)
      $topbar = [
        'search' => [
          'enabled'     => true,
          'placeholder' => 'Search links & QR…',
          'action'      => '/search.php',
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
            <div class="panel__title u-flex u-ai-center u-jc-between">
              <span>Traffic trend</span>
              <form method="get" class="segmented" role="tablist" aria-label="Range selector">
                <?php $trend = ($uid && isset($pdo)) ? wz_events_trend($pdo, $uid, $p) : []; ?>
                <input type="hidden" name="t" value="<?= htmlspecialchars($t) ?>">
                <button class="segmented__btn <?php echo $p==='7d'?'is-active':''; ?>" name="p" value="7d" role="tab" aria-selected="<?php echo $p==='7d'?'true':'false'; ?>">7d</button>
                <button class="segmented__btn <?php echo $p==='30d'?'is-active':''; ?>" name="p" value="30d" role="tab" aria-selected="<?php echo $p==='30d'?'true':'false'; ?>">30d</button>
                <button class="segmented__btn <?php echo $p==='90d'?'is-active':''; ?>" name="p" value="90d" role="tab" aria-selected="<?php echo $p==='90d'?'true':'false'; ?>">90d</button>
              </form>
            </div>
            <div class="chart" role="img" aria-label="Traffic trend data">
              <?php if (!$trend): ?>
                <div class="empty muted" aria-hidden="true">No data yet – create your first link to see trends.</div>
              <?php else: ?>
                <ul class="list-plain">
                  <?php foreach ($trend as $row): ?>
                    <li>
                      <span class="muted"><?php echo htmlspecialchars($row['date']); ?></span>
                      · <?php echo (int)($row['click'] ?? 0); ?> clicks
                      · <?php echo (int)($row['scan'] ?? 0); ?> scans
                      · <?php echo (int)($row['open'] ?? 0); ?> opens
                      · <?php echo (int)($row['create'] ?? 0); ?> creates
                      · <strong><?php echo (int)($row['total'] ?? 0); ?> total</strong>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="panel">
          <div class="panel__body">
            <div class="panel__title u-flex u-ai-center u-jc-between">
              <span>Recent activity</span>
              <a href="/activity.php" class="link-muted" aria-label="View all activity">View all</a>
            </div>
            <ul class="activity list-plain">
              <li class="activity__item">
                <span class="activity__icon" aria-hidden="true">🔗</span>
                <span class="activity__main"><strong>os.me/summer</strong> <span class="muted">· 312 clicks · 09:30 AM</span></span>
              </li>
              <li class="activity__item">
                <span class="activity__icon" aria-hidden="true">📷</span>
                <span class="activity__main"><strong>os.me/menu-qr</strong> <span class="muted">· 128 scans · 08:47 AM</span></span>
              </li>
              <li class="activity__item">
                <span class="activity__icon" aria-hidden="true">🚀</span>
                <span class="activity__main"><strong>os.me/launch</strong> <span class="muted">· 1.2k clicks · Yesterday</span></span>
              </li>
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
              <a class="segmented__btn <?= $t==='all'?'is-active':'' ?>"   href="/dashboard.php?t=all&p=<?= $p ?>">All</a>
              <a class="segmented__btn <?= $t==='links'?'is-active':'' ?>" href="/dashboard.php?t=links&p=<?= $p ?>">Links</a>
              <a class="segmented__btn <?= $t==='qr'?'is-active':'' ?>"    href="/dashboard.php?t=qr&p=<?= $p ?>">QR</a>
              <a class="segmented__btn <?= $t==='pages'?'is-active':'' ?>" href="/dashboard.php?t=pages&p=<?= $p ?>">Pages</a>
            </div>
            <a class="btn btn--ghost btn--sm" href="/exports/top-items.php?p=<?= $p ?>&t=<?= $t ?>">Export CSV</a>
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
            <tbody>
              <?php $top = ($uid && isset($pdo)) ? wz_top_items($pdo, $uid, $t_param, $p, 10) : []; ?>
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
<?php include __DIR__ . '/partials/app_footer.php'; ?>