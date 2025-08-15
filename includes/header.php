<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// الصفحات العامة اللي مش محتاجة تحقق دخول
$publicPages = [
  'login.php','register.php','forgot.php','reset.php','u.php','r.php','admin/login.php'
];

// اسم الملف الحالي فقط
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// لو الصفحة مش عامة → حمّل auth.php
if (!in_array($currentPage, $publicPages, true)) {
  require_once __DIR__ . '/auth.php';
}

require_once __DIR__ . '/lang.php';

$isAuthed = !empty($_SESSION['user_id']); // استعمل user_id وليس uid
$lang = $_SESSION['lang'] ?? 'en';
$dir  = ($lang === 'ar') ? 'rtl' : 'ltr';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$u = preg_replace('~([?&])lang=(ar|en)~','', $uri);
$u = rtrim($u, '&?');
$toLang = function($l) use ($u) {
  return $u . (strpos($u,'?')===false ? '?' : '&') . 'lang=' . $l;
};

 $config = require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
if (!isset($db) && class_exists('Database')) {
  $db = new Database($config['db']);
}
$pdo = $db->pdo();
$siteName = $config['app_name'] ?? 'whoiz.me';
$brandUrl = '/';
try {
  $q = $pdo->prepare("SELECT k, v FROM settings WHERE k IN ('site_name','brand_url','logo_url','favicon_url','primary_color')");
  $q->execute();
  $rows = $q->fetchAll(PDO::FETCH_KEY_PAIR);
  if (!empty($rows['site_name'])) $siteName = (string)$rows['site_name'];
  if (!empty($rows['brand_url'])) $brandUrl = (string)$rows['brand_url'];
  $logoUrl      = $rows['logo_url']      ?? '';
  $faviconUrl   = $rows['favicon_url']   ?? '';
  $primaryColor = $rows['primary_color'] ?? '';
} catch (Throwable $e) {
  // ignore if table not ready
  $logoUrl = $faviconUrl = $primaryColor = '';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $dir ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($siteName) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if (!empty($faviconUrl)): ?>
  <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
  <?php endif; ?>
  <style>:root{<?= !empty($primaryColor) ? '--bs-primary: '.htmlspecialchars($primaryColor).';' : '' ?>}</style>
  <link rel="stylesheet" href="/assets/css/app.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="with-user-sidebar">

<button id="sbToggle" class="sb-toggle" aria-label="Toggle sidebar" aria-expanded="false"><i class="bi bi-chevron-right"></i></button>

<?php if (!empty($_SESSION['impersonating']) && !empty($_SESSION['impersonate_admin_id'])): ?>
  <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center justify-content-between">
    <div>
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      You are viewing the site as a user.
    </div>
    <a class="btn btn-sm btn-outline-dark" href="/admin/stop_impersonate.php">
      Stop & return to Admin
    </a>
  </div>
<?php endif; ?>

<?php
  $__cur = $_SERVER['REQUEST_URI'] ?? '/';
  $isActive = function(array $needles) use ($__cur) {
    foreach ($needles as $n) { if ($n && str_contains($__cur, $n)) return ' active'; }
    return '';
  };
?>

<!-- Left Sidebar (user) -->
<aside class="user-sidebar" aria-label="User sidebar">
  <div class="side-head">
    <a class="brand" href="<?= htmlspecialchars($brandUrl ?: '/') ?>">
      <img src="/img/logo.svg" alt="<?= htmlspecialchars($siteName) ?>" style="display:block;margin-left:4px;">
    </a>
  </div>

  <a href="/link-create" class="create-btn" title="Create new">
    <i class="bi bi-plus-lg"></i> <span class="txt">Create new</span>
  </a>

  <hr>

  <nav class="sb-list" aria-label="Primary">
    <a href="/dashboard" class="sb-item<?= $isActive(['/dashboard']) ?>" title="Home">
      <span class="ico"><i class="bi bi-house-door"></i></span>
      <span class="txt">Home</span>
    </a>

    <a href="/link-stats" class="sb-item<?= $isActive(['/link-stats','/links']) ?>" title="Links">
      <span class="ico"><i class="bi bi-link-45deg"></i></span>
      <span class="txt">Links</span>
    </a>

    <a href="/qr/" class="sb-item<?= $isActive(['/qr/','/qr']) ?>" title="QR Codes">
      <span class="ico"><i class="bi bi-qr-code"></i></span>
      <span class="txt">QR Codes</span>
    </a>

    <a href="#" class="sb-item" title="Analytics">
      <span class="ico"><i class="bi bi-graph-up"></i></span>
      <span class="txt">Analytics <span class="badge text-bg-light ms-1">Try it</span></span>
    </a>
  </nav>

  <hr>

  <div class="section-bottom">
    <a href="/settings" class="sb-item<?= $isActive(['/settings']) ?>" title="Settings">
      <span class="ico"><i class="bi bi-gear"></i></span>
      <span class="txt">Settings</span>
    </a>

    <a href="/logout" class="sb-item" title="Logout">
      <span class="ico"><i class="bi bi-box-arrow-right"></i></span>
      <span class="txt">Logout</span>
    </a>
  </div>
</aside>

<!-- Top dark header -->
<header class="user-topbar">
  <div class="topHeader d-flex align-items-center justify-content-between">
    <div class="right-actions">
      <?php if ($isAuthed): ?>
        <a class="btn btn-sm btn-outline-secondary" href="/link-stats"><i class="bi bi-graph-up"></i> <span class="d-none d-md-inline">Stats</span></a>
        <a class="btn btn-sm btn btn-outline-primary" href="/link_create.php"><i class="bi bi-plus-lg"></i> New</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-light" href="/login.php">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main>
<script>
  (function() {
    const body = document.body;
    const sb  = document.querySelector('.user-sidebar');
    const btn = document.getElementById('sbToggle');
    if (!sb || !btn) return;
    const KEY = 'sbState'; // 'c' collapsed, 'e' expanded

    function applyState(val) {
      const collapsed = (val === 'c');
      const expanded  = (val === 'e');
      sb.classList.toggle('collapsed', collapsed);
      sb.classList.toggle('expanded',  expanded);
      btn.classList.toggle('collapsed', collapsed);
      btn.classList.toggle('expanded',  expanded);

      body.classList.remove('no-sidebar','with-user-sidebar','expanded');
      if (expanded) {
        body.classList.add('with-user-sidebar','expanded');
      } else {
        body.classList.add('with-user-sidebar'); // rail always visible in collapsed
      }
      const icon = btn.querySelector('i');
      if (icon) { icon.className = expanded ? 'bi bi-chevron-left' : 'bi bi-chevron-right'; }
      btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    // initial (default to collapsed-icons)
    const saved = localStorage.getItem(KEY) || 'c';
    applyState(saved);

    btn.addEventListener('click', function() {
      const current = localStorage.getItem(KEY) || 'c';
      const next = (current === 'c') ? 'e' : 'c';
      localStorage.setItem(KEY, next);
      applyState(next);
    });
  })();
</script></main>