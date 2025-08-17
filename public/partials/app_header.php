<?php
// public/partials/app_header.php

// حدد صفحات الـAuth اللي مش عايزين فيها الهيدر
$AUTH_PAGES = ['login.php', 'register.php', 'logout.php'];

// اسم السكريبت الحالي
$current = basename($_SERVER['SCRIPT_NAME']);
$isAuthPage = in_array($current, $AUTH_PAGES) || (!empty($AUTH_PAGE) && $AUTH_PAGE);

// لو صفحة Auth: ما نطبعش هيدر
if ($isAuthPage) {
  echo "<!-- header suppressed for auth pages -->";
  return;
}
?>
<header class="site-header">
  <div class="site-header__in">
    <a class="site-brand" href="/"><span class="logo-dot"></span>Whoizme</a>
    <nav class="site-nav" aria-label="Primary">
      <a href="/#features">Features</a>
      <a href="/#pricing">Pricing</a>
      <a href="/#help">Help</a>
    </nav>
    <div class="site-actions">
      <a class="btn btn--ghost btn--sm" href="/login.php">Log in</a>
      <a class="btn btn--primary btn--sm" href="/register.php">Get Started</a>
    </div>
  </div>
</header>