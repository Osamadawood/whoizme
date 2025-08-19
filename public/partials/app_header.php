<?php
declare(strict_types=1);

/**
 * App Header (for authenticated area)
 * - يعتمد على design system من app.css
 * - فيه toggle للثيم (dark / light) يخزّن الاختيار في كوكي
 */

$page_title = $page_title ?? 'Whoizme';
$themeCookie = $_COOKIE['theme'] ?? 'dark';
$theme = in_array($themeCookie, ['dark','light'], true) ? $themeCookie : 'dark';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($page_title) ?> · Whoizme</title>

  <!-- Main CSS bundle (SCSS output) -->
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
  <!-- (اختياري) styleguide.css لو الصفحة هي الـ styleguide -->
  <?php if (!empty($include_styleguide_css)): ?>
    <link rel="stylesheet" href="/assets/css/styleguide.css?v=<?= time() ?>">
  <?php endif; ?>

  <!-- أي أيقونات/مانيفست مستقبلًا -->
  <link rel="icon" href="/img/logo.png" type="image/svg+xml">
</head>
<body>
  <!-- App Shell -->
  <div class="app">

    <!-- Topbar -->
    <header class="app-topbar">
      <div class="app-topbar__in">
        <a class="brand" href="/dashboard">
          <span class="brand__logo" aria-hidden="true"></span>
          <span class="brand__name">Whoizme</span>
        </a>

        <nav class="topnav">
          <a href="/dashboard" class="topnav__link">Dashboard</a>
          <a href="/link-stats" class="topnav__link">Analytics</a>
          <a href="/qr-codes" class="topnav__link">QR Codes</a>
          <a href="/settings" class="topnav__link">Settings</a>
        </nav>

        <div class="app-topbar__spacer"></div>

        <!-- Theme toggle (داخل الهيدر) -->
        <label class="theme-toggle" title="Toggle theme">
          <input id="themeToggle" type="checkbox" <?= $theme === 'light' ? 'checked' : '' ?> />
          <span class="theme-toggle__label">Light</span>
        </label>

        <div class="user-menu">
          <a class="btn btn--ghost btn--sm" href="/logout">Logout</a>
        </div>
      </div>
    </header>

    <!-- Layout wrapper (اختياري: سايدبار لو محتاج) -->
    <div class="app-main">
      <!-- لو عندك سايدبار ثابت -->
      <!--
      <aside class="app-aside">
        ...
      </aside>
      -->
      <main class="app-content">