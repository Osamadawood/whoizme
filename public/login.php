<?php
declare(strict_types=1);

// صفحة عامة + ستايل auth
$page_title = "Sign in";
$page_class = "page-auth";

// مهم: نفعل SKIP_AUTH_GUARD لأننا على صفحة عامة
if (!defined('SKIP_AUTH_GUARD')) {
    define('SKIP_AUTH_GUARD', true);
}

/**
 * نحمّل البوتستراب مبكرًا جدًا (قبل أي HTML)
 * علشان نضمن وجود السيشن + الدوال (current_user_id)
 * ملف bootstrap ما بيطبعش حاجة، فده آمن ومش هيبوّظ الديزاين.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

// التحقق من return + تنظيفه (منع open-redirect واللفات)
$raw      = $_GET['return'] ?? '';
$decoded  = urldecode((string)$raw);
$pathOnly = parse_url($decoded, PHP_URL_PATH) ?: '';

// لو فاضي/روت/مؤدي لنفسه/للهاندلر → خليه الداشبورد
if ($pathOnly === '' || $pathOnly === '/' || $pathOnly === '/do_login.php' || $pathOnly === '/login.php') {
    $return_to = '/dashboard.php';
} else {
    // احتفظ بالـ path فقط (بدون دومين/كويريز خارجية)
    $return_to = $pathOnly;
}

// لو المستخدم داخل بالفعل → حوّله فورًا قبل أي HTML
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: ' . $return_to, true, 302);
    exit;
}

// بعد منطق التوجيه، نحمّل الهيدر الخاص باللاندينج (الديزاين كما هو)
require __DIR__ . "/partials/landing_header.php"; // بيعرّف $base وبيفتح <body class="$page_class">
?>

<main class="site-main">

  <div class="hero-img">
    <img src="/assets/img/auth-hero.jpg" alt="Whoizme">
  </div>

  <section class="auth-grid log-card">
    <!-- Left: Form card -->
    <article class="auth-panel auth-card">
      <h1 class="auth-title">Sign in</h1>
      <p class="auth-sub form-desc">Don’t have an account? <a href="/register.php">Create one</a></p>

      <form class="stack log-form" action="/do_login.php" method="post" novalidate>
        <!-- نمرر الوجهة بأمان -->
        <input type="hidden" name="return" value="<?= htmlspecialchars($return_to, ENT_QUOTES) ?>">

        <label class="field">
          <span class="label">Email address</span>
          <input class="input" type="email" name="email" placeholder="name@email.com" autocomplete="username" required>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </label>

        <div class="row row--between row--align">
          <label class="checkbox remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary" type="submit">Login</button>

        <div class="row">
          <!-- <div class="auth-muted">Don’t have an account? <a href="/register.php">Create one</a></div> -->
        </div>
      </form>
    </article>

    <!-- Right: Content side (marketing) -->
    <aside class="auth-side login-side">
      <div class="text-muted">WHOIZ.ME</div>
      <h2 class="big-title">All your links, QR codes & insights — together in one dashboard</h2>
      <p class="lead">Sign in to manage short links, create QR codes, and track performance with clean, privacy-first analytics — all in one place.</p>

      <div class="auth-sidecards">
        <div class="sidecard">
          <div class="ico" aria-hidden="true">✉</div>
          <div class="meta">
            <div class="title">Contact support</div>
            <div class="muted">We're here to help you</div>
          </div>
          <a class="btn btn--secondary btn--sm action" href="/contact-us.php">Contact Us</a>
        </div>
      </div>
    </aside>
  </section>
</main>

<?php require __DIR__ . "/partials/landing_footer.php"; ?>