<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT * FROM qr_codes WHERE id=:id AND user_id=:uid");
$st->execute([':id'=>$id, ':uid'=>$uid]);
$row = $st->fetch();
if (!$row) { http_response_code(404); exit('QR not found'); }
?>
<?php $page_title = 'QR Details'; include __DIR__ . '/../partials/app_header.php'; ?>

<main class="dashboard">

  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>

  <div class="container dash-grid">
    <section class="maincol">
      <div class="panel"><div class="panel__body">
        <h3 class="h3 u-mt-0">QR Code</h3>
        <div class="u-flex u-gap-16 u-ai-start u-fw-wrap">
          <div>
            <p><b>Title:</b> <?= htmlspecialchars($row['title']) ?></p>
            <p><b>Type:</b> <?= htmlspecialchars($row['type']) ?></p>
            <p style="max-width:700px;word-break:break-all;"><b>Payload:</b> <?= htmlspecialchars($row['payload']) ?></p>
            <p class="u-mt-8"><a class="btn btn--ghost" href="/qr/">Back to list</a></p>
          </div>
          <div>
            <canvas id="qrCanvas" width="512" height="512" style="background:#fff;padding:8px;border-radius:12px"></canvas>
            <div class="u-mt-8">
              <a class="btn btn--primary" id="dlBtn" download="<?= htmlspecialchars($row['title']) ?>.png">Download PNG</a>
            </div>
          </div>
        </div>
      </div></div>
    </section>
  </div>

</main>

<script>
const payload = <?= json_encode($row['payload']) ?>;
const canvas  = document.getElementById('qrCanvas');
QRCode.toCanvas(canvas, payload, { width: 512, margin:1 }, function (error) {
  if (error) console.error(error);
  document.getElementById('dlBtn').href = canvas.toDataURL('image/png');
});
</script>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>