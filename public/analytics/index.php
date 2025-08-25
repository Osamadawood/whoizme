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
            </div>
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
            <div class="chart-wrap"><canvas id="chart-trend" height="160" aria-label="Engagements over time" role="img"></canvas></div>
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
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


