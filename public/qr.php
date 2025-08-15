<?php require __DIR__ . '/../includes/auth.php'; ?>
<?php
ini_set('display_errors',1); error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';
$db = new Database($config['db']);

$uid = (int)$_SESSION['uid'];
$st = $db->pdo()->prepare("SELECT username FROM profiles WHERE user_id=? LIMIT 1");
$st->execute([$uid]);
$p = $st->fetch();

if (!$p || empty($p['username'])) {
  echo "<p style='font-family:system-ui;max-width:720px;margin:40px auto;color:#b00'>Complete profile first. <a href='/profile_edit.php'>Edit profile</a></p>";
  exit;
}
$profileUrl = rtrim($config['base_url'],'/').'/u/'.$p['username'];

function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES); }
require_once __DIR__ . '/../includes/bootstrap.php';
if (auth_role() === 'admin') {
  include INC_PATH . '/partials/admin_header.php';
} else {
  include INC_PATH . '/partials/user_header.php';
}
?>
<h2><?php echo __t('qr_code'); ?></h2>
<p>URL: <a href="<?= h($profileUrl) ?>" target="_blank"><?= h($profileUrl) ?></a></p>

<div id="qrcode" style="padding:16px;display:inline-block;background:#fff;border-radius:12px"></div>
<div style="margin-top:16px">
  <button id="downloadBtn">Download PNG</button>
</div>

<script src="/assets/js/qrcode.min.js"></script>
<script>
  const el = document.getElementById('qrcode');
  const qr = new QRCode(el, { text: "<?= h($profileUrl) ?>", width:256, height:256, correctLevel: QRCode.CorrectLevel.M });

  document.getElementById('downloadBtn').addEventListener('click', () => {
    const img = el.querySelector('img');
    const canvas = el.querySelector('canvas');

    const downloadFromCanvas = (cv) => {
      if (cv.toBlob) {
        cv.toBlob((blob) => {
          if (!blob) return alert('Failed to create image.');
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url; a.download = 'whoiz-qr.png';
          document.body.appendChild(a); a.click(); a.remove();
          URL.revokeObjectURL(url);
        }, 'image/png');
      } else {
        const a = document.createElement('a');
        a.href = cv.toDataURL('image/png'); a.download = 'whoiz-qr.png';
        document.body.appendChild(a); a.click(); a.remove();
      }
    };

    if (canvas) downloadFromCanvas(canvas);
    else if (img) {
      const c = document.createElement('canvas');
      c.width = img.naturalWidth || 256; c.height = img.naturalHeight || 256;
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      downloadFromCanvas(c);
    } else alert('QR not found.');
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>