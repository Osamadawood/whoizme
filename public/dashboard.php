<?php
// ===== boot & guards (ما لمستش أي لوجيك) =====
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$page_title = 'Dashboard';
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard — Whoiz.me</title>

  <!-- لو عندك CSS أساسي/توكنز بيُسحب تلقائيًا من الهيدر، سيب السطر الجاي -->
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">

  <!-- ====== Inline CSS (للتظبيط السريع فقط) ====== -->
  <style>
    /* page frame */
    .dashboard{padding-block:clamp(24px,4vw,48px);}
    .dashboard .container{max-width:1200px;margin-inline:auto;padding-inline:clamp(16px,3vw,28px);}
    /* grid layout like the reference */
    .dash-grid{display:grid;gap:clamp(12px,2vw,16px);}
    @media (min-width:980px){
      .dash-grid{grid-template-columns:260px 1fr;}
    }
    /* cards / surface */
    .panel{background:var(--surface);border:1px solid color-mix(in oklab,var(--text) 12%,transparent);
      border-radius:12px;box-shadow:var(--shadow-sm)}
    .panel--ghost{background:transparent;border:0;box-shadow:none}
    .panel__body{padding:clamp(16px,2.2vw,24px)}
    .panel__title{color:var(--muted);font-size:.95rem;margin-bottom:8px}
    .list-plain{list-style:none;margin:0;padding:0}
    /* sidebar */
    .side-nav .side__title{font-weight:700;letter-spacing:-.02em;color:var(--text)}
    .side-list{display:flex;flex-direction:column;gap:6px;margin-top:10px}
    .side-link{display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:10px;
      color:var(--muted);text-decoration:none;border:1px solid color-mix(in oklab,var(--text) 10%,transparent)}
    .side-link:hover{background:color-mix(in oklab,var(--surface) 70%,var(--text) 4%);color:var(--text)}
    .side-link.is-active{background:color-mix(in oklab,var(--surface) 80%,var(--primary) 6%);
      border-color:color-mix(in oklab,var(--primary) 22%,transparent);color:var(--text)}
    /* top KPIs */
    .kpis{display:grid;gap:clamp(10px,2vw,14px)}
    @media (min-width:740px){.kpis{grid-template-columns:repeat(3,minmax(0,1fr));}}
    .kpi{padding:16px;border-radius:12px;border:1px dashed color-mix(in oklab,var(--text) 10%,transparent)}
    .kpi__value{font-weight:700;font-size:clamp(22px,3.6vw,28px);letter-spacing:-.02em;color:var(--text)}
    .kpi__delta{font-size:.8rem;color:var(--success)}
    /* charts (placeholder) */
    .chart{height:230px;background:
      linear-gradient(180deg, color-mix(in oklab,var(--text) 10%,transparent), transparent 70%),
      radial-gradient(1200px 220px at 0% 120%, color-mix(in oklab,var(--primary) 22%,transparent), transparent 60%),
      radial-gradient(1200px 220px at 100% 0%, color-mix(in oklab,var(--primary) 18%,transparent), transparent 60%);
      border-radius:10px;border:1px solid color-mix(in oklab,var(--text) 12%,transparent)}
    /* 2-up cards row */
    .twoup{display:grid;gap:clamp(12px,2vw,16px)}
    @media (min-width:980px){.twoup{grid-template-columns:1fr 1fr}}
    /* table */
    .table{width:100%;border-collapse:separate;border-spacing:0 8px}
    .table th{font-size:.8rem;color:var(--muted);text-align:left;padding:10px 12px}
    .table td{padding:12px;border-top:1px solid color-mix(in oklab,var(--text) 12%,transparent);
      border-bottom:1px solid color-mix(in oklab,var(--text) 12%,transparent);background:var(--surface);
      color:var(--text)}
    .table tr td:first-child{border-radius:10px 0 0 10px}
    .table tr td:last-child{border-radius:0 10px 10px 0}
    .badge{font-size:.75rem;padding:.25rem .5rem;border-radius:999px;border:1px solid currentColor}
    .badge--up{color:var(--success)} .badge--down{color:var(--danger)}
    /* small helpers */
    .muted{color:var(--muted)} .u-mb-16{margin-bottom:16px} .u-mb-8{margin-bottom:8px}
    .btn{display:inline-flex;align-items:center;justify-content:center;height:42px;padding-inline:18px;
      border-radius:10px;border:1px solid color-mix(in oklab,var(--text) 12%,transparent);background:var(--surface);
      color:var(--text);text-decoration:none}
    .btn--primary{background:var(--primary);color:#fff;border-color:var(--primary)}
  </style>
</head>
<body>

  <?php
  // الهيدر العام (نفس اللي بيظهر في اللاندنج/الأوث – مش ملامسينه):
  include __DIR__.'/partials/landing_header.php';
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
          <a class="btn btn--primary" href="/create-link.php">Create link</a>
        </div>
      </aside>

      <!-- ============ Main content ============ -->
      <section class="maincol">

        <!-- KPIs -->
        <div class="kpis u-mb-16">
          <div class="panel kpi">
            <div class="panel__title">Total clicks</div>
            <div class="kpi__value">69,215</div>
            <div class="kpi__delta">+4.52% today</div>
          </div>
          <div class="panel kpi">
            <div class="panel__title">Active links</div>
            <div class="kpi__value">1,593</div>
            <div class="kpi__delta">+2.28% this week</div>
          </div>
          <div class="panel kpi">
            <div class="panel__title">QR scans</div>
            <div class="kpi__value">23,008</div>
            <div class="kpi__delta">−0.73% today</div>
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
              <ul class="muted list-plain" style="display:grid;gap:10px">
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

  </main>
</body>
</html>