<?php
// Boot & guards
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Dashboard';
include __DIR__ . '/partials/app_header.php';
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
              <span>Clicks trend</span>
              <div class="segmented" role="tablist" aria-label="Range selector">
                <button class="segmented__btn is-active" role="tab" aria-selected="true" data-range="7d">7d</button>
                <button class="segmented__btn" role="tab" aria-selected="false" data-range="30d">30d</button>
                <button class="segmented__btn" role="tab" aria-selected="false" data-range="90d">90d</button>
              </div>
            </div>
            <div class="chart" role="img" aria-label="Clicks trend area chart placeholder"></div>
            <div class="empty muted" aria-hidden="true">No data yet – create your first link to see trends.</div>
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
              <button class="segmented__btn is-active" role="tab" aria-selected="true" data-filter="all">All</button>
              <button class="segmented__btn" role="tab" aria-selected="false" data-filter="links">Links</button>
              <button class="segmented__btn" role="tab" aria-selected="false" data-filter="qr">QR</button>
              <button class="segmented__btn" role="tab" aria-selected="false" data-filter="pages">Pages</button>
            </div>
            <a href="/exports/top-items.csv" class="btn btn--ghost btn--sm">Export CSV</a>
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
              <tr>
                <td>Summer Campaign</td>
                <td><span class="badge">Short link</span></td>
                <td>9,842</td>
                <td>
                  <span class="badge badge--up">+4.8%</span></td>
                <td>Mar 03</td>
              </tr>
              <tr>
                <td>Restaurant Menu</td>
                <td><span class="badge">QR</span></td>
                <td>5,103</td>
                <td><span class="badge badge--down">−1.2%</span></td>
                <td>Feb 27</td>
              </tr>
              <tr>
                <td>Landing — Spring</td>
                <td><span class="badge">Short link</span></td>
                <td>3,258</td>
                <td><span class="badge badge--up">+2.1%</span></td>
                <td>Feb 18</td>
              </tr>
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