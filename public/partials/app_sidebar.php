<?php
// public/partials/app_sidebar.php
?>
<aside class="sidebar" role="navigation" aria-label="Sidebar">
  <div class="sidebar__brand">
    <a href="/dashboard.php" class="brand">
      <span class="brand__logo"></span>
      <span class="brand__name">Whoizme</span>
    </a>
  </div>

  <nav class="sidebar__nav">
    <a class="nav__item <?= basename($_SERVER['PHP_SELF'])==='dashboard.php' ? 'is-active' : '' ?>" href="/dashboard.php">
      <span class="i i-home"></span><span>Home</span>
    </a>
    <a class="nav__item" href="/features.php">
      <span class="i i-stars"></span><span>Features</span>
    </a>
    <a class="nav__item" href="/users.php">
      <span class="i i-users"></span><span>Users</span>
    </a>
    <a class="nav__item" href="/pricing.php">
      <span class="i i-tag"></span><span>Pricing</span>
    </a>
    <a class="nav__item" href="/integrations.php">
      <span class="i i-plug"></span><span>Integrations</span>
    </a>

    <div class="nav__section">Settings</div>
    <a class="nav__item" href="/settings.php">
      <span class="i i-gear"></span><span>General</span>
    </a>
    <a class="nav__item" href="/webflow-pages.php">
      <span class="i i-layers"></span><span>Utility pages</span>
    </a>
  </nav>

  <div class="sidebar__footer">
    <div class="account-mini">
      <img src="/assets/img/avatar-pic.png" alt="User avatar" class="avatar__img" />
      <div class="account-mini__meta">
        <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></strong>
        <a href="/account.php" class="link--muted">Account settings</a>
      </div>
      <a class="btn btn--sm btn-secondary" href="/templates.php">Get template</a>
    </div>
  </div>
</aside>