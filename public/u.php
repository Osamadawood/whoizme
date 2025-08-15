<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';
$db = new Database($config['db']);

// Accept ?u=USERNAME or pretty URLs like /u/USERNAME
$username = trim($_GET['u'] ?? '');
if ($username === '') {
  // Try PATH_INFO first (e.g., /USERNAME)
  $pi = $_SERVER['PATH_INFO'] ?? '';
  if ($pi && $pi !== '/') {
    $username = trim(urldecode(basename($pi)));
  }
}
if ($username === '') {
  // Try REQUEST_URI (e.g., /u/USERNAME or /public/u/USERNAME)
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  if ($uri && preg_match('~/(?:u|public/u)/([^/?#]+)~i', $uri, $m)) {
    $username = trim(urldecode($m[1]));
  }
}
if ($username === '' || $username === false) { http_response_code(404); echo "Profile not found."; exit; }

/* اجلب البروفايل مع حالة صاحب الحساب */
$stmt = $db->pdo()->prepare("
  SELECT p.*, u.is_active, u.disable_reason
  FROM profiles p
  JOIN users u ON u.id = p.user_id
  WHERE p.username = ? LIMIT 1
");
$stmt->execute([$username]);
$p = $stmt->fetch();

if (!$p) { http_response_code(404); echo "Profile not found."; exit; }

/* لو الحساب متعطّل: اعرض صفحة توضيح */
if ((int)$p['is_active'] !== 1) {
  http_response_code(403);
  ?>
  <!doctype html><html lang="en"><meta charset="utf-8">
  <title>Profile disabled</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;display:grid;place-items:center;height:100vh;background:#0f172a;color:#e5e7eb;margin:0">
    <div style="max-width:640px;text-align:center;padding:24px">
      <h1 style="margin:0 0 8px;font-size:28px">This profile is disabled</h1>
      <p style="opacity:.8;line-height:1.7">The owner’s account is currently inactive.</p>
    </div>
  </body></html>
  <?php
  exit;
}

/* Resolve avatar path: if stored as a local filename, serve from /uploads/avatars/ */
$avatar = $p['avatar_url'] ?? '';
if ($avatar && stripos($avatar, 'http') !== 0) {
  $avatar = '/uploads/avatars/' . ltrim($avatar, '/');
}

/* helpers */
function normalize_url($u){
  $u = trim($u ?? '');
  if ($u === '') return '';
  if (stripos($u,'http://') === 0 || stripos($u,'https://') === 0) return $u;
  return 'https://' . $u;
}
function esc($v){ return htmlspecialchars($v ?? '', ENT_QUOTES); }
function wa_link($v){
  // keep digits only (handle +20... etc.)
  $v = preg_replace('~\D+~','',$v ?? '');
  return $v ? "https://wa.me/$v" : null;
}
?>
<!doctype html><html lang="en" dir="ltr"><meta charset="utf-8">
<title><?= esc($p['display_name']) ?> - whoiz.me</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<body style="font-family:system-ui;background:#0b1220;color:#fff;margin:0">
  <div style="max-width:640px;margin:0 auto;padding:40px 20px">
    <div style="text-align:center">
      <?php if (!empty($avatar)): ?>
        <img src="<?= esc($avatar) ?>" alt="<?= esc($p['display_name']) ?>"
             style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid #1f2937">
      <?php endif; ?>

      <h1 style="margin:16px 0 8px;font-size:28px"><?= esc($p['display_name']) ?></h1>
      <?php if (!empty($p['bio'])): ?>
        <p style="opacity:.85;line-height:1.7"><?= nl2br(esc($p['bio'])) ?></p>
      <?php endif; ?>
    </div>

    <div style="margin-top:24px;display:grid;gap:12px">
      <?php if (!empty($p['website'])): ?>
        <a href="<?= esc(normalize_url($p['website'])) ?>" target="_blank" rel="noopener"
           style="display:block;text-align:center;padding:12px 16px;background:#1f2937;border-radius:12px;text-decoration:none;color:#fff">Website</a>
      <?php endif; ?>

      <?php if ($wa = wa_link($p['whatsapp'])): ?>
        <a href="<?= esc($wa) ?>" target="_blank" rel="noopener"
           style="display:block;text-align:center;padding:12px 16px;background:#1f2937;border-radius:12px;text-decoration:none;color:#fff">WhatsApp</a>
      <?php endif; ?>

      <?php if (!empty($p['instagram'])): $ig = ltrim((string)$p['instagram'], '@/'); ?>
        <a href="https://instagram.com/<?= esc($ig) ?>" target="_blank" rel="noopener"
           style="display:block;text-align:center;padding:12px 16px;background:#1f2937;border-radius:12px;text-decoration:none;color:#fff">Instagram</a>
      <?php endif; ?>

      <?php if (!empty($p['twitter'])): $tw = ltrim((string)$p['twitter'], '@/'); ?>
        <a href="https://x.com/<?= esc($tw) ?>" target="_blank" rel="noopener"
           style="display:block;text-align:center;padding:12px 16px;background:#1f2937;border-radius:12px;text-decoration:none;color:#fff">X / Twitter</a>
      <?php endif; ?>

      <?php if (!empty($p['linkedin'])): ?>
        <a href="<?= (stripos($p['linkedin'],'http')===0)?esc($p['linkedin']):'https://www.linkedin.com/in/'.esc($p['linkedin']) ?>" target="_blank" rel="noopener"
           style="display:block;text-align:center;padding:12px 16px;background:#1f2937;border-radius:12px;text-decoration:none;color:#fff">LinkedIn</a>
      <?php endif; ?>
    </div>

    <p style="margin-top:28px;text-align:center;opacity:.6">whoiz.me</p>
  </div>
</body>
</html>