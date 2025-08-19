<?php
$user = $_SESSION['user'] ?? null;
?>
<header class="app-topbar" role="banner">
  <div class="app-topbar__left">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <span class="breadcrumb__item">Features</span>
      <span class="breadcrumb__sep">›</span>
      <span class="breadcrumb__item is-current"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></span>
    </nav>
  </div>

  <div class="app-topbar__right">
    <a class="btn btn-secondary" href="/links.php">Create report</a>
    <div class="avatar" title="<?= htmlspecialchars($user['email'] ?? 'Account') ?>"></div>
  </div>
</header>