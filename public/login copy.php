<?php
// view only (لا يوجد منطق تسجيل هنا)
require_once __DIR__ . '/../includes/bootstrap.php';

// لو اليوزر داخل بالفعل ما نرجّعهوش للّوجين
if (function_exists('auth_is_logged_in') && auth_is_logged_in()) {
  header('Location: /dashboard.php'); exit;
}

// رسائل فلاش/كويري
$registered = isset($_GET['registered']) ? 1 : 0;
$email      = isset($_GET['email']) ? trim($_GET['email']) : '';
$err        = isset($_GET['err']) ? $_GET['err'] : '';
$returnTo   = isset($_GET['return']) ? $_GET['return'] : '/dashboard.php';

// خرائط الأخطاء
$errors = [
  'badinput'   => 'Please enter a valid email and password.',
  'badpass'    => 'Email or password is incorrect.',
  'inactive'   => 'Your account is disabled. Please contact support.',
  'exception'  => 'Temporary login issue. Please try again.',
];
$msg = $registered ? 'Account created. Please sign in.' : ($errors[$err] ?? '');

$page_title = 'Sign in';
$page_class = 'page-auth login-page';
require __DIR__ . '/partials/landing_header.php';
?>

<main class="container auth-container" role="main" style="max-width: 1120px;">
  <div class="grid grid--2col gap-8">
    <section class="card p-8">
      <h1 class="h1 mb-2">Sign in</h1>
      <p class="text-muted mb-6">
        Don’t have an account?
        <a href="/register.php">Create one</a>
      </p>

      <?php if ($msg): ?>
        <div class="alert <?= $registered ? 'alert--success' : 'alert--warning' ?> mb-6">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <form class="form" method="post" action="/do_login.php">
        <input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>">
        <?php if (function_exists('csrf_input')) csrf_input(); ?>

        <label class="form__label">Email</label>
        <input name="email" type="email" class="input mb-4" required
               value="<?= htmlspecialchars($email) ?>" placeholder="name@email.com">

        <label class="form__label">Password</label>
        <input name="password" type="password" class="input mb-4" required placeholder="••••••••">

        <div class="flex items-center justify-between mb-6">
          <label class="checkbox">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a class="link" href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary btn--block">Login</button>

        <p class="mt-6 text-muted">
          New to Whoizme? <a href="/register.php">Create account</a>
        </p>
      </form>
    </section>

    <!-- جانب تسويقي بسيط (نفس روح register.php) -->
    <aside class="card p-8">
      <h5 class="overline mb-2">WHOIZ.ME</h5>
      <h2 class="h3 mb-4">All your links, QR codes & insights — together in one dashboard</h2>
      <p class="text-muted mb-6">
        Create short links, generate QR codes, and track performance with clean, privacy-first analytics.
      </p>
      <a class="btn btn--ghost" href="/register.php">Get started</a>
    </aside>
  </div>
</main>

<?php require __DIR__ . '/partials/landing_footer.php'; ?>