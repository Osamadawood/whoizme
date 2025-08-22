<?php
// view only (لا يوجد منطق تسجيل هنا)
require_once __DIR__ . '/../includes/bootstrap.php';

// لو اليوزر داخل بالفعل ما نرجّعهوش للّوجين
if (function_exists('auth_is_logged_in') && auth_is_logged_in()) {
  header('Location: /dashboard'); exit;
}

// رسائل فلاش/كويري
$registered = isset($_GET['registered']) ? 1 : 0;
$email      = isset($_GET['email']) ? trim($_GET['email']) : '';
$err        = isset($_GET['err']) ? $_GET['err'] : '';
$returnTo   = isset($_GET['return']) ? $_GET['return'] : '/dashboard';

// خرائط الأخطاء
$errors = [
  'badinput'   => 'Please enter a valid email and password.',
  'badpass'    => 'Email or password is incorrect.',
  'inactive'   => 'Your account is disabled. Please contact support.',
  'exception'  => 'Temporary login issue. Please try again.',
];
$msg = $registered ? 'Account created. Please Log in.' : ($errors[$err] ?? '');

$page_title = 'Log in';
$page_class = 'page-auth login-page';
require __DIR__ . '/partials/landing_header.php';
?>

<main class="site-main u-pb-16">

  <!-- hero strip (image) -->
  <div class="hero-img">
    <img src="/assets/img/auth-hero.jpg" alt="" class="hero-bg" />
  </div>

  <div class="container u-mt-8 grid gap-8">

    <!-- form card -->
    <article class="auth-panel auth-card auth-custom">

      <h1 class="auth-title">Log in</h1>
      <p class="auth-sub form-desc">
        New to Whoiz.me? <a href="/register">Create one</a>
      </p>

        <?php if ($msg): ?>
          <div class="alert <?= $registered ? 'alert--success' : 'alert--warning' ?> mb-6">
            <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>


        <form class="form log-form" method="post" action="/do_login">
          <input type="hidden" name="return" value="<?= htmlspecialchars($returnTo) ?>">
          <?php if (function_exists('csrf_input')) csrf_input(); ?>

            <label class="form__label u-mb-2">
              <span class="label u-mb-2">Email</span>
              <input name="email" type="email" class="input mb-4" required value="<?= htmlspecialchars($email) ?>" placeholder="name@email.com">
              <?php if (isset($errors['name'])): ?><small class="field-error"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?>
            </label>

            <label class="form__label u-mb-2">
              <span class="label u-mb-2">Password</span>
              <input name="password" type="password" class="input mb-4" required placeholder="••••••••">
            </label>

          <label class="checkbox row double-check u-mb-2">
              <div class="flex remember-me">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
              </div>

              <a class="link" href="/forgot-password">Forgot password?</a>
          </label>

          <div class="go-btn">
            <button class="btn btn--primary btn--block">Login</button>
          </div>

        </form>
    </article>

  </div>
</main>
<?php require __DIR__ . '/partials/landing_footer.php'; ?>