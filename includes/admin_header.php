<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// ----- load config & db -----
if (!isset($config) || !is_array($config)) {
  $config = require __DIR__ . '/../app/config.php';
}
if (!isset($db) || !is_object($db)) {
  require_once __DIR__ . '/../app/database.php';
  if (class_exists('Database')) {
    $db = new Database($config['db']);
  }
}

// ----- dynamic settings -----
$siteName     = $config['app_name'] ?? 'whoiz.me';
$siteLogo     = null;
$siteFavicon  = null;
$primaryColor = '#0d6efd';

try {
  $pdo = $db->pdo();
  $st  = $pdo->query("SELECT k,v FROM settings WHERE k IN ('site_name','logo_url','favicon_url','primary_color')");
  $all = $st->fetchAll(PDO::FETCH_KEY_PAIR);
  if (!empty($all['site_name']))     $siteName     = (string)$all['site_name'];
  if (!empty($all['logo_url']))      $siteLogo     = (string)$all['logo_url'];
  if (!empty($all['favicon_url']))   $siteFavicon  = (string)$all['favicon_url'];
  if (!empty($all['primary_color'])) $primaryColor = (string)$all['primary_color'];
} catch (Throwable $e) {
  // ignore, use defaults
}

// ----- session helpers -----
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? (!empty($_SESSION['is_super']) ? 'super' : 'viewer');
$isSuper   = ($adminRole === 'super' || !empty($_SESSION['is_super']));

if (empty($_SESSION['csrf_admin'])) {
  $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
}
$csrfAdmin = $_SESSION['csrf_admin'];

// permissions helper
$can = function(string $ability) use ($adminRole) {
  if (function_exists('admin_can')) return admin_can($ability);
  return ($adminRole === 'super' || !empty($_SESSION['is_super']));
};

// active helper
$__cur = $_SERVER['REQUEST_URI'] ?? '/';
$navActive = function(array $needles) use ($__cur) {
  foreach ($needles as $n) { if ($n && str_contains($__cur, $n)) return ' active'; }
  return '';
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($siteName) ?> — Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php if (!empty($siteFavicon)): ?>
    <link rel="icon" href="<?= htmlspecialchars($siteFavicon) ?>">
  <?php endif; ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root { --bs-primary: <?= htmlspecialchars($primaryColor) ?>; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .navbar-brand img { height: 28px; width: auto; display: block; }

    /* --- Left Sidebar (admin) --- */
    :root{ --sbw:72px; }
    body.with-admin-sidebar{ padding-left: var(--sbw); }
    @media (max-width: 992px){ body.with-admin-sidebar{ padding-left:0 } }
    .admin-sidebar{ position: fixed; top: 56px; left: 0; bottom: 0; width: var(--sbw); background:#ffffff; border-right:1px solid #e5e7eb; z-index:1030; display:flex; flex-direction:column; align-items:center; padding:12px 0; gap:10px; }
    .admin-sidebar .sb-btn{ width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#0b1220; background:#f3f4f6; border:1px solid #e5e7eb; text-decoration:none; transition:all .15s ease; }
    .admin-sidebar .sb-btn:hover{ background:#eef2ff; border-color:#dbeafe; }
    .admin-sidebar .sb-btn.active{ background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .admin-sidebar .sb-plus{ background:#0d6efd; color:#fff; border-color:#0d6efd; box-shadow:0 6px 18px rgba(13,110,253,.25); }
    .admin-sidebar .sb-group{ display:flex; flex-direction:column; gap:8px; margin-top:4px; }
    .admin-sidebar .sb-bottom{ margin-top:auto; display:flex; flex-direction:column; gap:8px; padding-bottom:8px; }
    @media (max-width: 992px){ .admin-sidebar{ display:none } }
  </style>
</head>
<body class="with-admin-sidebar">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/admin/dashboard.php">
      <?php if (!empty($siteLogo)): ?>
        <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>">
        <span class="d-none d-sm-inline"><?= htmlspecialchars($siteName) ?> Admin</span>
      <?php else: ?>
        <?= htmlspecialchars($siteName) ?> Admin
      <?php endif; ?>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= $navActive(['/admin/dashboard.php']) ?>" href="/admin/dashboard.php">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <?php if ($can('users.view')): ?>
        <li class="nav-item">
          <a class="nav-link<?= $navActive(['/admin/users.php']) ?>" href="/admin/users.php">
            <i class="bi bi-people me-1"></i>Users
          </a>
        </li>
        <?php endif; ?>
        <?php if ($can('links.view')): ?>
        <li class="nav-item">
          <a class="nav-link<?= $navActive(['/admin/links.php']) ?>" href="/admin/links.php">
            <i class="bi bi-link-45deg me-1"></i>Links
          </a>
        </li>
        <?php endif; ?>
        <?php if ($isSuper): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle<?= $navActive(['/admin/admins.php','/admin/logs.php','/admin/settings.php']) ?>" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            More
          </a>
          <ul class="dropdown-menu" aria-labelledby="moreDropdown">
            <li><a class="dropdown-item" href="/admin/admins.php"><i class="bi bi-shield-lock me-2"></i>Admins</a></li>
            <li><a class="dropdown-item" href="/admin/logs.php"><i class="bi bi-clipboard-data me-2"></i>Logs</a></li>
            <li><a class="dropdown-item" href="/admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-outline-light btn-sm" href="/admin/logout.php">
          <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
      </div>
    </div>
  </div>
</nav>

<?php if (!empty($_SESSION['impersonating'])): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center my-3 rounded-0">
    <div>
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <strong>Impersonating</strong> a user session. Some actions will be performed as that user.
    </div>
    <a class="btn btn-sm btn-outline-dark" href="/admin/stop_impersonate.php?csrf=<?= htmlspecialchars($csrfAdmin) ?>">
      Stop & return to Admin
    </a>
  </div>
<?php endif; ?>

<!-- Fixed Left Sidebar -->
<aside class="admin-sidebar" aria-label="Admin sidebar">
  <a href="/admin/create.php" class="sb-btn sb-plus" title="Create new"><i class="bi bi-plus-lg"></i></a>

  <div class="sb-group">
    <a href="/admin/dashboard.php" class="sb-btn<?= $navActive(['/admin/dashboard.php']) ?>" title="Home"><i class="bi bi-house-door"></i></a>
    <a href="/admin/links.php" class="sb-btn<?= $navActive(['/admin/links.php','/admin/link']) ?>" title="Links"><i class="bi bi-link-45deg"></i></a>
    <a href="/admin/qrs.php" class="sb-btn<?= $navActive(['/admin/qrs.php','/admin/qr']) ?>" title="QR Codes"><i class="bi bi-qr-code"></i></a>
    <a href="#" class="sb-btn disabled" title="Landing Pages (coming soon)"><i class="bi bi-file-earmark-richtext"></i></a>
  </div>

  <div class="sb-bottom">
    <a href="/admin/settings.php" class="sb-btn<?= $navActive(['/admin/settings.php']) ?>" title="Settings"><i class="bi bi-gear"></i></a>
  </div>
</aside>

<div class="container py-4">