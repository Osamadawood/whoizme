<?php define('PUBLIC_PAGE', true); ?>
<?php
declare(strict_types=1);

/**
 * Public: Sign in
 * لا نلمس الـ HTML/الديزاين أسفل. فقط منطق التوجيه والتحقق بالأعلى.
 */

// 1) تعطيل الحارس هنا لأن صفحة لوج إن عامة
if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

// 2) حمل البوتستراب (سيشن + دوال + PDO + CONFIG)
require dirname(__DIR__) . '/includes/bootstrap.php';

// 3) دالة صغيرة لتنظيف return ومنع أي دورات (login/do_login/index)
function clean_return(?string $raw): string {
    $raw      = (string)($raw ?? '');
    $decoded  = $raw !== '' ? urldecode($raw) : '';
    $pathOnly = $decoded !== '' ? (string)(parse_url($decoded, PHP_URL_PATH) ?? '') : '';

    $bad = ['', '/', '/index', '/index.php', '/login', '/login.php', '/do_login', '/do_login.php'];
    if ($pathOnly === '' || in_array($pathOnly, $bad, true)) {
        return '/dashboard.php';
    }
    return $pathOnly[0] === '/' ? $pathOnly : '/dashboard.php';
}

// 4) احسب الهدف بأمان
$rawReturn = isset($_GET['return']) ? (string)$_GET['return'] : '';
$return_to = clean_return($rawReturn);

// 5) لو المستخدم مسجل دخول بالفعل, وده على الهدف
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: ' . $return_to, true, 302);
    exit;
}

// 6) رسالة خطأ عامة (لو do_login رجّع err=1)
$errMsg = isset($_GET['err']) && $_GET['err'] !== ''
    ? 'The email or password you entered is incorrect.'
    : '';

// 7) إعدادات للهيدر (لو محتاجها)
$page_title = 'Sign in';
$page_class = 'page-auth';

// 8) اطبع هيدر اللاندنج (لا تغييرات على المارك أب)
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

      <form class="stack log-form" method="post" action="/do_login.php" accept-charset="UTF-8" novalidate>
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
      <h2 class="big-title">All your links, QR codes &amp; insights — together in one dashboard</h2>
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
