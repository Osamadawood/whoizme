<?php
declare(strict_types=1);

/**
 * Public: Landing (home)
 * - الصفحة عامة: لا تجبر على تسجيل الدخول.
 * - لو المستخدم مسجّل دخول بالفعل => نحوله للـ Dashboard.
 * - نحافظ على ترويسة/فوتر اللاندنج بدون لمس SCSS.
 * - لو حابب تشوف اللاندنج وأنت مسجّل دخول، استخدم ?preview=1.
 */

// 0) Constants for guards (cover both old/new flags)
if (!defined('PAGE_PUBLIC'))   define('PAGE_PUBLIC', true);   // new flag
if (!defined('PUBLIC_PAGE'))   define('PUBLIC_PAGE', true);   // legacy flag
if (!defined('SKIP_AUTH_GUARD')) define('SKIP_AUTH_GUARD', true); // skip any global guard for public pages

// 1) Bootstrap: session + constants + PDO + helpers
require dirname(__DIR__) . '/includes/bootstrap.php';

// 2) Optional preview of landing even if logged in
$preview = isset($_GET['preview']) && $_GET['preview'] === '1';

// 3) If already authenticated and not previewing -> go to dashboard
if (!$preview && function_exists('current_user_id') && (int)current_user_id() > 0) {
    header('Location: /dashboard.php', true, 302);
    exit;
}

// 4) Some older headers may call this; define a no-op here to avoid redirects
if (!function_exists('auth_redirect_if_logged_in')) {
    function auth_redirect_if_logged_in(): void { /* no-op on landing */ }
}

// 5) Page meta
$page_title = 'Whoizme — Short links, QR & Analytics';
$page_class = 'page-landing';

// 6) Landing header
require __DIR__ . '/partials/landing_header.php';
?>
<main class="site-main">
  <!-- ضع/احتفظ بمحتوى اللاندنج الخاص بك هنا.
       لو عندك قالب جاهز، خليه كما هو — الكود أعلاه لا يلمس الـ CSS. -->

  <section class="hero u-py-12">
    <div class="container">
      <h1 class="display">Smarter links &amp; QR for your brand</h1>
      <p class="lead u-mt-4">Create short links, design QR codes, and track privacy-first analytics — all in one clean dashboard.</p>
      <div class="u-mt-6">
        <a class="btn btn--primary" href="/register.php">Get started</a>
        <a class="btn btn--ghost u-ml-4" href="/login.php">Sign in</a>
      </div>
    </div>
  </section>

  <!-- أمثلة بطاقات بسيطة (اختياري — احذفها لو عندك لاندنجك الخاصة) -->
  <section class="u-py-10">
    <div class="container grid grid-3">
      <article class="card">
        <h3 class="h5">Short links</h3>
        <p class="sg-muted">Branded, trackable and fast.</p>
      </article>
      <article class="card">
        <h3 class="h5">QR codes</h3>
        <p class="sg-muted">High-res, themed and export-ready.</p>
      </article>
      <article class="card">
        <h3 class="h5">Insights</h3>
        <p class="sg-muted">Clean analytics without the noise.</p>
      </article>
    </div>
  </section>
</main>
<?php require __DIR__ . '/partials/landing_footer.php'; ?>
