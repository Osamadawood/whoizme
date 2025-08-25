<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/metrics.php';

$from = isset($_GET['from']) ? trim((string)$_GET['from']) : null;
$to   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : null;

// Defensive: on any error, metrics helper returns zeros
$metrics = [];
try { $metrics = wz_get_analytics_metrics($pdo, $from, $to); } catch (Throwable $e) { $metrics = ['total_engagements'=>0,'active_qr'=>0,'unique_visitors'=>0,'active_links'=>0]; }

$page_title = 'Analytics';
include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid">
    <div class="container topbar--inset" role="region" aria-label="Primary toolbar">
      <?php
        $breadcrumbs = [ ['label' => 'Analytics', 'href' => '/analytics'] ];
        $topbar = [
          'search' => [ 'enabled' => false ],
          'actions' => [ 'theme_toggle' => true, 'language' => ['enabled'=>true,'current'=>($_SESSION['lang'] ?? 'EN'), 'options'=>['EN','AR']] ]
        ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>
    <section class="maincol">
      <div class="panel">
        <div class="panel__body">
          <div class="panel__title u-flex u-ai-center u-jc-between">
            <span>Analytics</span>
            <div class="filters segmented" role="tablist" aria-label="Date range">
              <button type="button" class="segmented__btn is-active" data-range="today">Today</button>
              <button type="button" class="segmented__btn" data-range="7d">7D</button>
              <button type="button" class="segmented__btn" data-range="30d">30D</button>
              <button type="button" class="segmented__btn" data-range="custom">Custom</button>
              <button type="button" class="btn btn--ghost btn--sm" id="range-reset">Reset</button>
            </div>
            <div class="u-text-muted u-mt-8" id="range-details"></div>
          </div>

          <div class="kpis u-mt-12">
            <div class="panel kpi" aria-live="polite">
              <div class="panel__title">Total engagements</div>
              <div class="kpi__value"><?= number_format((int)($metrics['total_engagements'] ?? 0)) ?></div>
            </div>
            <div class="panel kpi" aria-live="polite">
              <div class="panel__title">Active QR codes</div>
              <div class="kpi__value"><?= number_format((int)($metrics['active_qr'] ?? 0)) ?></div>
            </div>
            <div class="panel kpi" aria-live="polite">
              <div class="panel__title">Unique visitors</div>
              <div class="kpi__value"><?= number_format((int)($metrics['unique_visitors'] ?? 0)) ?></div>
            </div>
            <div class="panel kpi" aria-live="polite">
              <div class="panel__title">Active links</div>
              <div class="kpi__value"><?= number_format((int)($metrics['active_links'] ?? 0)) ?></div>
            </div>
          </div>
        </div>
      </div>

      <section class="analytics-grid u-mt-16" id="analytics-charts" data-from="<?= htmlspecialchars(date('Y-m-d', strtotime('-30 days'))) ?>" data-to="<?= htmlspecialchars(date('Y-m-d')) ?>" data-scope="all">
        <article class="panel">
          <div class="panel__body">
            <div class="panel__title">Total engagements over time</div>
            <div class="chart-wrap"><canvas id="chart-trend" height="260" aria-label="Engagements over time" role="img"></canvas></div>
          </div>
        </article>

        <article class="panel">
          <div class="panel__body">
            <div class="panel__title">Engagements by device</div>
            <div class="chart-wrap"><canvas id="chart-devices" height="160" aria-label="Engagements by device" role="img"></canvas></div>
          </div>
        </article>

        <article class="panel" style="grid-column: 1/-1;">
          <div class="panel__body">
            <div class="panel__title">Engagements by referrer</div>
            <div class="chart-wrap"><canvas id="chart-referrers" height="200" aria-label="Engagements by referrer" role="img"></canvas></div>
          </div>
        </article>
      </section>
    </section>
  </div>
</main>
<script src="/assets/js/vendor/chart.min.js" defer></script>
<script src="/assets/js/analytics-charts.js" defer></script>
<div class="modal" id="dr-modal" aria-hidden="true">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="dr-title">
    <div class="modal__title" id="dr-title">Custom date range</div>
    <form id="dr-form">
      <div class="u-mb-8">
        <label for="dr-from">From</label>
        <input type="date" id="dr-from">
      </div>
      <div class="u-mb-8">
        <label for="dr-to">To</label>
        <input type="date" id="dr-to">
      </div>
      <p class="u-text-danger" id="dr-error" hidden></p>
      <div class="modal__actions">
        <button type="button" class="btn btn--ghost" id="dr-cancel" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn--primary" id="dr-apply">Apply</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


