<?php
declare(strict_types=1);

$title = $title ?? 'Dashboard · Whoizme';
$theme = $theme ?? 'dark';
$APP_CSS = '/assets/css/app.min.css';

// حراسة الدخول
if (!function_exists('current_user_id') || !current_user_id()) {
  header('Location: /login');
  exit;
}

$uid = (int)current_user_id();

// مساعدة بسيطة لاكتشاف الصفحة النشطة
$__path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$active = function(string $slug) use ($__path): string {
  return rtrim($__path, '/') === $slug ? ' aria-current="page"' : '';
};
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= htmlspecialchars($title) ?></title>

  <link rel="stylesheet" href="<?= $APP_CSS ?>?v=<?= time() ?>"/>
</head>
<body>

<!-- Top bar -->
<header class="topbar">
  <div class="topbar__inner">
    <a class="brand" href="/dashboard"><span class="sr-only">Whoizme</span>Whoizme</a>
    <nav class="nav">
      <a href="/dashboard"<?= $active('/dashboard') ?>>Overview</a>
      <a href="/link-stats"<?= $active('/link-stats') ?>>Links</a>
      <a href="/qr-codes"<?= $active('/qr-codes') ?>>QR Codes</a>
      <a href="/link-visits"<?= $active('/link-visits') ?>>Analytics</a>
      <a class="btn btn--ghost" href="/settings"<?= $active('/settings') ?>>Settings</a>
      <a class="btn btn--danger" href="/logout">Logout</a>
    </nav>
  </div>
</header>

<!-- App shell -->
<div class="page-section">
  <div class="container">