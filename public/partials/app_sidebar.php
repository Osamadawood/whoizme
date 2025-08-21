<?php
// Sidebar – app shell (Whoizme)
// NOTE: no inline styles; all styling lives in SCSS (components/_sidebar.scss)

// Helper: resolve active state by current script name
$__current = basename($_SERVER['PHP_SELF']);
$__is_active = function(string $file) use ($__current) {
  return $__current === $file ? ' is-active' : '';
};

// User (from session) – fallbacks are safe
 $__user_name   = $_SESSION['user']['name'] ?? 'User';
 $__user_avatar = $_SESSION['user']['avatar'] ?? '';
 $__user_initial = strtoupper(mb_substr($__user_name, 0, 1));

function render_icon(string $name): void {
  switch ($name) {
    case 'home':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg>';
      break;
    case 'star':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3.5 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.8 6.4 20.7l1.1-6.2L3 10.1l6.2-.9L12 3.5z"/></svg>';
      break;
    case 'users':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="3.5"/><circle cx="17" cy="9.5" r="2.5"/><path d="M3.5 19.5a5.5 5.5 0 0 1 9-4.2"/><path d="M13.8 19.5a4.2 4.2 0 0 1 6.7-3.3"/></svg>';
      break;
    case 'tag':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V5h7l8.5 8.5a2.1 2.1 0 0 1 0 3L16.5 18a2.1 2.1 0 0 1-3 0L5 9z"/><circle cx="8" cy="8" r="1.2"/></svg>';
      break;
    case 'plug':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7l10 10"/><path d="M9.5 3.5v4M14.5 8.5v-4"/><path d="M3.5 9.5h4M8.5 14.5h-4"/><rect x="9" y="9" width="6" height="6" rx="3"/></svg>';
      break;
    case 'settings':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.7l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.7-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.7.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.7 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.7l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.7.3 1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.7-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.7 1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.6 1.6 0 0 0-1.5 1z"/></svg>';
      break;
    case 'wrench':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.3 7.7a5 5 0 0 1-6.9 6.9l-6.2 6.2-2.1-2.1 6.2-6.2a5 5 0 0 1 6.9-6.9L15 7.1l1.9 1.9 3.4-1.3z"/></svg>';
      break;
    case 'layers':
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 3 8l9 5 9-5-9-5z"/><path d="M3 12l9 5 9-5"/></svg>';
      break;
    default:
      echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>';
  }
}
?>

<div class="sidebar-wrapper">
  <aside class="side-nav" role="navigation" aria-label="Main sidebar">
    <div class="side-nav__inner">

      <!-- Brand / logo -->
      <header class="side-nav__brand">
        <a class="brand" href="/dashboard.php" aria-label="Whoizme Home">
          <span class="brand__logo" aria-hidden="true"></span>
          <span class="brand__name">Whoizme</span>
        </a>
      </header>

      <!-- Search in dashboard -->
      <form class="side-nav__search" action="/search.php" method="get" role="search">
        <label class="visually-hidden" for="sidebar-search">Search dashboard</label>
        <input id="sidebar-search" class="input input--search" type="search" name="q" placeholder="Search dashboard…" autocomplete="off">
      </form>

      <!-- Primary links -->
      <nav class="side-nav__list" aria-label="Sidebar items">
        <a class="side-nav__link<?= $__is_active('dashboard.php') ?>" href="/dashboard.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('home'); ?>
          </span>
          <span class="side-nav__text">Home</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('features.php') ?>" href="/features.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('star'); ?>
          </span>
          <span class="side-nav__text">Features</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('users.php') ?>" href="/users.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('users'); ?>
          </span>
          <span class="side-nav__text">Users</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('pricing.php') ?>" href="/pricing.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('tag'); ?>
          </span>
          <span class="side-nav__text">Pricing</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('integrations.php') ?>" href="/integrations.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('plug'); ?>
          </span>
          <span class="side-nav__text">Integrations</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('settings.php') ?>" href="/settings.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('settings'); ?>
          </span>
          <span class="side-nav__text">Settings</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('utility.php') ?>" href="/utility.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('wrench'); ?>
          </span>
          <span class="side-nav__text">Utility pages</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>

        <a class="side-nav__link<?= $__is_active('webflow.php') ?>" href="/webflow.php">
          <span class="side-nav__icon" aria-hidden="true">
            <?php render_icon('layers'); ?>
          </span>
          <span class="side-nav__text">Webflow pages</span>
          <span class="side-nav__chev" aria-hidden="true"></span>
        </a>
      </nav>

      <!-- Divider -->
      <hr class="side-nav__divider"/>

      <!-- Account card / CTA -->
      <div class="side-nav__account">
        <div class="account">
          <div class="account__avatar">
            <?php if ($__user_avatar): ?>
              <img src="<?= htmlspecialchars($__user_avatar) ?>" alt="<?= htmlspecialchars($__user_name) ?>" loading="lazy">
            <?php else: ?>
              <span class="avatar-badge" aria-hidden="true"><?= $__user_initial ?></span>
            <?php endif; ?>
          </div>
          <div class="account__meta">
            <div class="account__name"><?= htmlspecialchars($__user_name) ?></div>
            <a class="account__link" href="/account.php">Account settings</a>
          </div>
          <button class="account__toggle" type="button" aria-haspopup="menu" aria-expanded="false" data-menu="account-menu"></button>
        </div>
        <a class="btn btn-primary btn-block" href="/create-link.php">Get template</a>
      </div>

    </div>
  </aside>
</div>