<?php
// لو عايز تحدد الاكتيف حسب الصفحة الحالية
$current = basename($_SERVER['PHP_SELF']);
function is_active($file){ return basename($_SERVER['PHP_SELF']) === $file ? ' is-active' : ''; }
?>
<aside class="side-nav">
  <div class="side-nav__body">
    <div class="side-nav__title">Dashboard</div>

    <nav class="side-nav__list" aria-label="Sidebar">
      <a class="side-nav__link<?= is_active('dashboard.php') ?>" href="/dashboard.php">Overview</a>
      <a class="side-nav__link<?= is_active('links.php') ?>" href="/links.php">Links</a>
      <a class="side-nav__link<?= is_active('qr.php') ?>" href="/qr.php">QR Codes</a>
      <a class="side-nav__link<?= is_active('analytics.php') ?>" href="/analytics.php">Analytics</a>
      <a class="side-nav__link<?= is_active('templates.php') ?>" href="/templates.php">Templates</a>
      <a class="side-nav__link<?= is_active('menus.php') ?>" href="/menus.php">Menus</a>
      <a class="side-nav__link<?= is_active('settings.php') ?>" href="/settings.php">Settings</a>
    </nav>

    <a class="btn btn-primary w-100" href="/create-link.php">Create link</a>
  </div>
</aside>