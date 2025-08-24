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
    <section class="maincol">
      <div class="panel">
        <div class="panel__body">
          <div class="panel__title u-flex u-ai-center u-jc-between">
            <span>Analytics</span>
            <div class="filters segmented" role="tablist" aria-label="Date range">
              <a href="#" class="segmented__btn is-active" data-range="today">Today</a>
              <a href="#" class="segmented__btn" data-range="7d">7D</a>
              <a href="#" class="segmented__btn" data-range="30d">30D</a>
              <a href="#" class="segmented__btn" data-range="custom">Custom</a>
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
    </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


