<?php
// Boot & guards
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Dashboard';
include __DIR__ . '/partials/app_header.php';
?>

<main class="dashboard">
  <div class="container dash-grid">

    <!-- ============ Sidebar ============ -->
    <aside class="panel side-nav">
      <div class="panel__body">
        <div class="side__title">Dashboard</div>
        <nav class="side-list u-mb-16" aria-label="Sidebar">
          <a class="side-link is-active" href="/dashboard.php">Overview</a>
          <a class="side-link" href="/links.php">Links</a>
          <a class="side-link" href="/qr.php">QR Codes</a>
          <a class="side-link" href="/analytics.php">Analytics</a>
          <a class="side-link" href="/templates.php">Templates</a>
          <a class="side-link" href="/menus.php">Menus</a>
          <a class="side-link" href="/settings.php">Settings</a>
        </nav>
        <a class="btn btn-primary" href="/create-link.php">Create link</a>
      </div>
    </aside>

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
            <div class="panel__title">Clicks trend</div>
            <div class="chart" role="img" aria-label="Clicks trend area chart placeholder"></div>
          </div>
        </div>
        <div class="panel">
          <div class="panel__body">
            <div class="panel__title">Recent activity</div>
            <ul class="muted list-plain">
              <li><strong>os.me/summer</strong> • 312 clicks • 09:30 AM</li>
              <li><strong>os.me/menu-qr</strong> • 128 scans • 08:47 AM</li>
              <li><strong>os.me/launch</strong> • 1.2k clicks • Yesterday</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Table: top items -->
      <div class="panel">
        <div class="panel__body">
          <div class="panel__title">Top links & QR</div>
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
                <td>Short link</td>
                <td>9,842</td>
                <td><span class="badge badge--up">+4.8%</span></td>
                <td>Mar 03</td>
              </tr>
              <tr>
                <td>Restaurant Menu</td>
                <td>QR</td>
                <td>5,103</td>
                <td><span class="badge badge--down">−1.2%</span></td>
                <td>Feb 27</td>
              </tr>
              <tr>
                <td>Landing — Spring</td>
                <td>Short link</td>
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

<?php include __DIR__ . '/partials/app_footer.php'; ?>