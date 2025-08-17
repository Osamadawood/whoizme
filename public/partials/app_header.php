<?php
declare(strict_types=1);

/**
 * App Header (Authenticated)
 * - Uses Whoizme design system classes
 * - Sticky topbar, active link detection for exact and nested routes
 * - Theme toggle persists in localStorage (handled in footer script)
 */

$title   = $title ?? 'Dashboard · Whoizme';
$theme   = $theme ?? 'dark';
$APP_CSS = $APP_CSS ?? '/assets/css/app.min.css';

/* Auth guard: only app pages include this header */
if (!function_exists('current_user_id') || !current_user_id()) {
  header('Location: /login?return=' . urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard'));
  exit;
}

$uid = (int)current_user_id();

/**
 * Active helpers (safe with existing class attributes)
 * - $activeClass returns a class suffix (e.g. " is-active") to be APPENDED inside class="..."
 * - $activeAria returns aria-current when active
 */
$__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__path = rtrim($__path, '/') ?: '/';

$activeClass = function (string $slug, bool $loose = true) use ($__path): string {
  $slug = rtrim($slug, '/') ?: '/';
  $isActive = $loose
    ? (strpos($__path . '/', $slug . '/') === 0)
    : ($__path === $slug);
  return $isActive ? ' is-active' : '';
};

$activeAria = function (string $slug, bool $loose = true) use ($__path): string {
  $slug = rtrim($slug, '/') ?: '/';
  $isActive = $loose
    ? (strpos($__path . '/', $slug . '/') === 0)
    : ($__path === $slug);
  return $isActive ? ' aria-current="page"' : '';
};
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($APP_CSS) ?>?v=<?= time() ?>"/>
</head>
<body>
  <!-- Topbar (sticky) -->
  <header class="topbar is-sticky">
    <div class="topbar__inner container">
      <button class="icon-btn md:hidden" id="appMenuBtn" aria-label="Open menu" aria-controls="appNav" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z"/></svg>
      </button>

      <a class="brand" href="/dashboard">
        <span class="sr-only">Whoizme</span>
        Whoizme
      </a>

      <nav id="appNav" class="nav">
        <a href="/dashboard" class="nav__link<?= $activeClass('/dashboard') ?>"<?= $activeAria('/dashboard') ?>>Overview</a>
        <a href="/link-stats" class="nav__link<?= $activeClass('/link-stats') ?>"<?= $activeAria('/link-stats') ?>>Links</a>
        <a href="/qr-codes" class="nav__link<?= $activeClass('/qr-codes') ?>"<?= $activeAria('/qr-codes') ?>>QR Codes</a>
        <a href="/link-visits" class="nav__link<?= $activeClass('/link-visits') ?>"<?= $activeAria('/link-visits') ?>>Analytics</a>
        <a href="/settings" class="btn btn--ghost<?= $activeClass('/settings', false) ?>"<?= $activeAria('/settings', false) ?>>Settings</a>
        <a href="/logout" class="btn btn--danger">Logout</a>
      </nav>

      <label class="toggle" for="appThemeToggle" title="Toggle theme">
        <input id="appThemeToggle" type="checkbox" aria-label="Toggle light theme">
        <span>Light</span>
      </label>
    </div>
  </header>

  <!-- App shell -->
  <div class="page-section">
    <div class="container">