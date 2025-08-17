<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
?>
<!-- بقية صفحتك كما هي (الهيدر/المحتوى/الفوتر) -->

<main class="dashboard">
  <!-- Gradient hero -->
  <section class="dash-hero">
    <div class="dash-hero__inner">
      <div class="dash-hero__left">
        <h1 class="dash-hero__title">Hey <span class="wave">👋</span></h1>
        <p class="dash-hero__subtitle">We’re on a mission to help you ship beautiful links, QR codes and landing pages.</p>
        <div class="dash-hero__actions">
          <a class="btn btn-primary" href="/link-create">Create link</a>
          <a class="btn" href="/qr-codes">Create QR</a>
        </div>
      </div>
      <div class="dash-hero__right">
        <!-- Optional: hero illustration placeholder -->
        <div class="dash-hero__art"></div>
      </div>
    </div>
  </section>

  <!-- KPI cards -->
  <section class="kpi-row">
    <article class="kpi-card">
      <div class="kpi-card__icon">🔗</div>
      <div class="kpi-card__meta">
        <h3 class="kpi-card__label">LINK CLICKS</h3>
        <div class="kpi-card__value">0</div>
        <div class="kpi-card__delta kpi-card__delta--up">+0%</div>
      </div>
    </article>

    <article class="kpi-card">
      <div class="kpi-card__icon">📱</div>
      <div class="kpi-card__meta">
        <h3 class="kpi-card__label">QR SCANS</h3>
        <div class="kpi-card__value">0</div>
        <div class="kpi-card__delta kpi-card__delta--up">+0%</div>
      </div>
    </article>

    <article class="kpi-card">
      <div class="kpi-card__icon">🧲</div>
      <div class="kpi-card__meta">
        <h3 class="kpi-card__label">ENGAGEMENTS</h3>
        <div class="kpi-card__value">0</div>
        <div class="kpi-card__delta">—</div>
      </div>
    </article>

    <article class="kpi-card is-soon">
      <div class="kpi-card__icon">👥</div>
      <div class="kpi-card__meta">
        <h3 class="kpi-card__label">CUSTOMERS</h3>
        <div class="kpi-card__value">—</div>
        <span class="badge badge-soon">Soon</span>
      </div>
    </article>
  </section>

  <!-- Main panels grid -->
  <section class="dash-grid">
    <div class="panel span-2">
      <div class="panel__head">
        <h3>Visitors statistics</h3>
        <div class="tabs">
          <button class="tab is-active">Monthly</button>
          <button class="tab" disabled>Yearly</button>
        </div>
      </div>
      <div class="panel__body chart-placeholder">
        <!-- Replace with real chart -->
        <div class="chart-skeleton">
          <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h3>Customers</h3>
      </div>
      <div class="panel__body">
        <div class="donut-skeleton">
          <svg viewBox="0 0 36 36" class="donut">
            <circle class="donut-ring" cx="18" cy="18" r="15.915"></circle>
            <circle class="donut-segment" cx="18" cy="18" r="15.915" stroke-dasharray="70 30"></circle>
          </svg>
          <div class="legend">
            <span class="dot monthly"></span> Monthly
            <span class="dot yearly"></span> Yearly
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h3>Top Links</h3>
      </div>
      <div class="panel__body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Clicks</th>
              <th>Status</th>
              <th>Last Active</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>— No links yet</td>
              <td>0</td>
              <td><span class="status status--pending">Pending</span></td>
              <td>—</td>
              <td><a class="btn btn-ghost" href="/link-create">Create</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel__head">
        <h3>Recent Activity</h3>
      </div>
      <ul class="activity">
        <li class="activity__item">Nothing here yet — start by creating a link or a QR.</li>
      </ul>
    </div>
  </section>
</main>

<?php include __DIR__ . '/partials/app_footer.php'; ?>