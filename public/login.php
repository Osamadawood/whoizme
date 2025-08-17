<?php
declare(strict_types=1);

// صفحة عامة
$page_title = "Sign in";
$page_class = "page-auth";

// نفعل SKIP_AUTH_GUARD (لو عندك أي فلاتر تعتمد عليه)
if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

// تحميل البوتستراب مبكرًا
require dirname(__DIR__) . '/includes/bootstrap.php';

// --- إدارة return بأمان ومنع أي loop/open redirect ---
$raw     = (string)($_GET['return'] ?? '');
$decoded = $raw !== '' ? urldecode($raw) : '';
$path    = $decoded !== '' ? (string)(parse_url($decoded, PHP_URL_PATH) ?? '') : '';

$bad = ['', '/', '/do_login.php', 'do_login.php', '/login.php', 'login.php'];
if ($path === '' || in_array($path, $bad, true)) {
    $return_to = '/dashboard.php';
} else {
    $return_to = ($path[0] === '/') ? $path : '/dashboard.php';
}

// لو داخل بالفعل → روح للداشبورد
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: ' . $return_to, true, 302);
    exit;
}

// هيدر اللاندينج (لا يطبع PHP قبل <html>)
require __DIR__ . '/partials/landing_header.php';
?>
<main class="site-main">

  <div class="hero-img">
    <img src="/assets/img/auth-hero.jpg" alt="Whoizme">
  </div>

  <section class="auth-grid log-card">
    <!-- Left: Form -->
    <article class="auth-panel auth-card">
      <h1 class="auth-title">Sign in</h1>
      <p class="auth-sub form-desc">Don’t have an account? <a href="/register.php">Create one</a></p>

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
      <p class="lead">Sign in to manage short links, create QR codes, and track performance with clean, privacy-first analytics — all in one place.</p>

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