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
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [
          ['label' => 'Dashboard', 'href' => '/dashboard'],
          ['label' => 'QR Codes',  'href' => '/qr'],
          ['label' => 'Details',   'href' => null],
        ];
        $topbar = [ 'search' => [ 'enabled' => false ] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>
    <section class="maincol qr-view">
      <div class="panel"><div class="panel__body">
        <h3 class="h3 u-mt-0">QR Code</h3>
        <div class="qr-view__grid">
          <div class="qr-view__meta">
            <p><b>Title:</b> <?= htmlspecialchars($row['title']) ?></p>
            <p><b>Type:</b> <?= htmlspecialchars($row['type']) ?></p>
            <p class="qr-view__payload"><b>Payload:</b> <?= htmlspecialchars($row['payload']) ?></p>
            <p class="u-mt-8"><a class="btn btn--ghost" href="/qr/">Back to list</a></p>
          </div>
          <div class="qr-view__preview">
            <div id="qrBox" class="qr-view__qr"></div>
            <div class="qr-view__actions">
              <a class="btn btn--primary" id="dlPng" download="<?= htmlspecialchars($row['title']) ?>.png">Download PNG</a>
              <a class="btn btn--ghost" id="dlJpg" download="<?= htmlspecialchars($row['title']) ?>.jpg">Download JPG</a>
              <button class="btn btn--ghost" id="dlSvgBtn" type="button">Print quality (SVG)</button>
            </div>
          </div>
        </div>
      </div></div>
    </section>
  </div>

</main>

<script src="/assets/js/qrcode.min.js"></script>
<script>
const payload = <?= json_encode($row['payload']) ?>;
const fname = <?= json_encode($row['title']) ?>;
const box = document.getElementById('qrBox');
let qinst = null;
try {
  qinst = new QRCode(box, { text: payload, width: 496, height: 496, correctLevel: QRCode.CorrectLevel.L });
  // Wait a tick for canvas to be inserted
  setTimeout(() => {
    const canvas = box.querySelector('canvas');
    if (canvas) {
      // PNG
      document.getElementById('dlPng').href = canvas.toDataURL('image/png');
      // JPG with white background
      const tmp = document.createElement('canvas');
      tmp.width = canvas.width; tmp.height = canvas.height;
      const ctx = tmp.getContext('2d');
      ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,tmp.width,tmp.height);
      ctx.drawImage(canvas,0,0);
      document.getElementById('dlJpg').href = tmp.toDataURL('image/jpeg', 0.95);
    }
  }, 50);
} catch (e) { console.error(e); }

document.getElementById('dlSvgBtn').addEventListener('click', () => {
  try {
    const model = qinst && qinst._oQRCode;
    if (!model || typeof model.getModuleCount !== 'function') return;
    const n = model.getModuleCount();
    let d = '';
    for (let r=0;r<n;r++) {
      for (let c=0;c<n;c++) {
        if (model.isDark(r,c)) d += `M${c} ${r}h1v1h-1z`;
      }
    }
    // Note: omit XML declaration to avoid PHP short_open_tag conflict
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${n} ${n}" width="2048" height="2048" shape-rendering="crispEdges"><path fill="#000" d="${d}"/></svg>`;
    const blob = new Blob([svg], {type:'image/svg+xml'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href=url; a.download=(fname||'qr')+'.svg'; a.click();
    setTimeout(()=>URL.revokeObjectURL(url),800);
  } catch (e) { console.error(e); }
});
</script>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>