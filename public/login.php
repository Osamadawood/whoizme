<?php
declare(strict_types=1);

/**
 * Public: Sign in
 * NOTE: We keep design markup/classes intact. This only hardens flow & errors.
 */

// Mark this page as public (skip guards in bootstrap/includes)
if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

// Bootstrap (must be loaded before any HTML)
require dirname(__DIR__) . '/includes/bootstrap.php';

// ---------------------------------------------------------------------
// Return target: sanitize & avoid loops (no absolute URLs, no self/do_login)
// ---------------------------------------------------------------------
$raw      = isset($_GET['return']) ? (string)$_GET['return'] : '';
$decoded  = $raw !== '' ? urldecode($raw) : '';
$pathOnly = $decoded !== '' ? (string)(parse_url($decoded, PHP_URL_PATH) ?? '') : '';

$badTargets = ['', '/', '/do_login.php', 'do_login.php', '/login.php', 'login.php'];
if ($pathOnly === '' || in_array($pathOnly, $badTargets, true)) {
    $return_to = '/dashboard.php';
} else {
    // Only allow relative paths that start with '/'
    $return_to = ($pathOnly[0] === '/') ? $pathOnly : '/dashboard.php';
}

// Already signed-in? Go to target (typically /dashboard.php)
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: ' . $return_to, true, 302);
    exit;
}

// Read error flags (e.g. after failed login)
$errCode = isset($_GET['err']) ? (string)$_GET['err'] : '';
$errMsg  = '';
if ($errCode !== '') {
    // Keep messages generic for security
    $errMsg = 'The email or password you entered is incorrect.';
}

// Landing header (prints the document structure)
require __DIR__ . '/partials/landing_header.php';
?>
<main class="site-main">

  <div class="hero-img">
    <img src="/assets/img/auth-hero.jpg" alt="Whoizme hero">
  </div>

  <section class="auth-grid log-card">
    <!-- Left: Form -->
    <article class="auth-panel auth-card">
      <h1 class="auth-title">Sign in</h1>
      <p class="auth-sub form-desc">Don’t have an account? <a href="/register.php">Create one</a></p>

      <?php if ($errMsg): ?>
        <div class="alert alert--danger" role="alert">
          <?= htmlspecialchars($errMsg, ENT_QUOTES) ?>
        </div>
      <?php endif; ?>

      <form class="stack log-form" action="/do_login.php" method="post" novalidate>
        <input type="hidden" name="return" value="<?= htmlspecialchars($return_to, ENT_QUOTES) ?>">

        <label class="field">
          <span class="label">Email address</span>
          <input class="input" type="email" name="email" placeholder="name@email.com"
                 autocomplete="username" required>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="••••••••"
                 autocomplete="current-password" required>
        </label>

        <div class="row row--between row--align">
          <label class="checkbox remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary" type="submit">Login</button>
      </form>
    </article>

    <!-- Right: Marketing -->
    <aside class="auth-side login-side">
      <div class="text-muted">WHOIZ.ME</div>
      <h2 class="big-title">All your links, QR codes & insights — together in one dashboard</h2>
      <p class="lead">Sign in to manage short links, create QR codes, and track performance with clean, privacy‑first analytics — all in one place.</p>

      <div class="auth-sidecards">
        <div class="sidecard">
          <div class="ico" aria-hidden="true">✉</div>
          <div class="meta">
            <div class="title">Contact support</div>
            <div class="muted">We’re here to help you</div>
          </div>
          <a class="btn btn--secondary btn--sm action" href="/contact-us.php">Contact Us</a>
        </div>
      </div>
    </aside>
  </section>
</main>
<?php require __DIR__ . '/partials/landing_footer.php'; ?>