<?php
// public/partials/app_topbar.php
// Breadcrumb ذكي: Dashboard فقط كـ "home"؛ وإلا يستخدم $breadcrumb إن وُجد
$page_title = $page_title ?? 'Dashboard';
$is_dash    = (strtolower($page_title) === 'dashboard') || (basename($_SERVER['PHP_SELF']) === 'dashboard.php');

$crumbs = (isset($breadcrumb) && is_array($breadcrumb) && !empty($breadcrumb))
  ? $breadcrumb
  : ($is_dash ? ['Dashboard' => null] : ['Features' => '/features.php', $page_title => null]);

// CSRF للـ logout داخل المينيو
$csrf_token = $csrf_token ?? '';
if (!$csrf_token && file_exists(__DIR__ . '/../../includes/csrf.php')) {
  require_once __DIR__ . '/../../includes/csrf.php';
  if (function_exists('csrf_token')) $csrf_token = csrf_token();
}

$user_email = $_SESSION['user']['email'] ?? '';
?>
<header class="app-topbar" role="banner">
  <div class="app-topbar__left">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <?php
        $i = 0; $t = count($crumbs);
        foreach ($crumbs as $label => $href): $i++;
          if ($href && $i < $t): ?>
            <a class="breadcrumb__item" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
          <?php else: ?>
            <span class="breadcrumb__item is-current" aria-current="page"><?= htmlspecialchars($label) ?></span>
          <?php endif; endforeach; ?>
    </nav>
  </div>

  <div class="app-topbar__right">
    <!-- CTA pill زي المرجع -->
    <button class="btn btn-primary btn-pill btn--sm" id="qcOpen" aria-haspopup="dialog" aria-controls="quickCreate" aria-expanded="false">
      Create new
    </button>

    <!-- Theme switch كبير -->
    <button class="theme-switch" id="themeToggle" aria-label="Toggle dark/light mode" aria-pressed="false">
      <span class="theme-switch__track" aria-hidden="true"></span>
      <span class="theme-switch__thumb" aria-hidden="true"></span>
    </button>

    <!-- Avatar + Dropdown -->
    <div class="account" id="account">
      <button class="avatar-btn" id="accountBtn" aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">
        <img class="avatar__img" src="/assets/img/avatar-pic.png" alt="User avatar" loading="lazy" />
        <span class="avatar__fallback" aria-hidden="true">
          <?= $user_email ? strtoupper(substr($user_email,0,1)) : 'U' ?>
        </span>
      </button>
      <div class="account__menu" id="accountMenu" role="menu" aria-labelledby="accountBtn">
        <a class="account__item" role="menuitem" href="/profile.php">Profile</a>
        <a class="account__item" role="menuitem" href="/settings.php">Settings</a>
        <form class="account__item account__item--danger" role="none" action="/logout.php" method="post">
          <?php if ($csrf_token): ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token) ?>">
          <?php endif; ?>
          <button type="submit" role="menuitem">Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>

<!-- Quick Create modal زي المثال -->
<div class="modal" id="quickCreate" role="dialog" aria-modal="true" aria-labelledby="qcTitle" aria-hidden="true">
  <div class="modal__overlay" data-close="qc"></div>
  <div class="modal__dialog" role="document" tabindex="-1">
    <div class="modal__header">
      <h2 class="modal__title" id="qcTitle">What do you want to create?</h2>
      <button class="btn-icon" id="qcClose" aria-label="Close dialog"><span class="icon-x" aria-hidden="true"></span></button>
    </div>
    <div class="modal__body">
      <div class="qc-grid">
        <a class="qc-card" href="/links.php?action=new">
          <span class="qc-card__icon icon-link" aria-hidden="true"></span>
          <span class="qc-card__title">Shorten a link</span>
          <span class="qc-card__kbd">L</span>
        </a>
        <a class="qc-card" href="/qr.php?action=new">
          <span class="qc-card__icon icon-qr" aria-hidden="true"></span>
          <span class="qc-card__title">Create a QR Code</span>
          <span class="qc-card__kbd">Q</span>
        </a>
        <a class="qc-card" href="/templates.php?action=new">
          <span class="qc-card__icon icon-layout" aria-hidden="true"></span>
          <span class="qc-card__title">Build a landing page</span>
          <span class="qc-card__kbd">P</span>
        </a>
      </div>
    </div>
  </div>
</div>