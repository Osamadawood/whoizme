<?php
// Bootstrap first (sessions, config, etc.)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Try to load shared auth helpers if present
$__guard = __DIR__ . '/auth_guard.php';
if (file_exists($__guard)) {
  require_once $__guard;
}

// Fallback in case the helper wasn't loaded (prevents fatal errors)
if (!function_exists('auth_redirect_if_logged_in')) {
  function auth_redirect_if_logged_in(): void {
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
      header('Location: /dashboard.php');
      exit;
    }
  }
}

// If the viewer is already authenticated, keep them out of public landing/login pages
auth_redirect_if_logged_in();

// Page meta defaults
if (!isset($page_title)) { $page_title = "Whoizme"; }
if (!isset($page_class)) { $page_class = ""; }
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- App CSS (من SCSS بتاع السيستم) -->
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
</head>
<body class="<?= htmlspecialchars($page_class) ?>">

<header class="landing-header" role="banner">
  <div class="landing-header__bar">
    <a class="landing-header__logo" href="/">
      <img src="/assets/img/logo.svg" alt="" width="28" height="28">
      <span>Whoiz.me</span>
    </a>

    <nav aria-label="Primary" class="nav">
      <a href="/#features">Features</a>
      <a href="/#help">Help</a>
    </nav>

    <div class="nav__spacer"></div>

    <a href="/login.php" class="landing-header__link">Log in</a>
    <a class="btn btn--cta" href="/register.php">Get started</a>
  </div>
</header>
