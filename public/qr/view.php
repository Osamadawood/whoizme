<?php require_once __DIR__ . "/../_bootstrap.php"; ?>
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT * FROM qr_codes WHERE id=:id AND user_id=:uid");
$st->execute([':id'=>$id, ':uid'=>$uid]);
$row = $st->fetch();
if (!$row) { http_response_code(404); exit('QR not found'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($row['title']) ?> — QR</title>
  <link rel="stylesheet" href="/assets/css/app.css">
  <style>
  .qr-wrap { display:flex; gap:24px; align-items:flex-start; }
  canvas { background:#fff; padding:8px; border-radius:8px; }
  </style>
  <script>
  // qrcode.min.js (نسخة خفيفة) — لو عندك نسخة محلية استبدل المسار
  </script>
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body class="with-user-sidebar">
<main class="container">
  <h2 class="h4 mb-3">QR Code</h2>
  <div class="qr-wrap">
    <div>
      <p><b>Title:</b> <?= htmlspecialchars($row['title']) ?></p>
      <p><b>Type:</b> <?= htmlspecialchars($row['type']) ?></p>
      <p style="max-width:700px;word-break:break-all;"><b>Payload:</b> <?= htmlspecialchars($row['payload']) ?></p>
      <p><a class="btn btn-light" href="/qr/">Back to list</a></p>
    </div>
    <div>
      <canvas id="qrCanvas" width="512" height="512"></canvas>
      <div class="mt-2">
        <a class="btn btn-outline-dark" id="dlBtn" download="<?= htmlspecialchars($row['title']) ?>.png">Download PNG</a>
      </div>
    </div>
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
</body>
</html>