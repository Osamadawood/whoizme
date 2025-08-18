<?php
declare(strict_types=1);

if (!defined('PAGE_PUBLIC')) define('PAGE_PUBLIC', true);
require dirname(__DIR__) . '/includes/bootstrap.php';

// لو داخل بالفعل
if (function_exists('current_user_id') && (int)current_user_id() > 0) {
    header('Location: /dashboard.php', true, 302);
    exit;
}

$page_title = 'Sign in';
$page_class = 'page-auth';
require __DIR__ . '/partials/landing_header.php';

$err   = isset($_GET['err']) ? (string)$_GET['err'] : '';
$regOk = isset($_GET['registered']);
$emailPrefill = isset($_GET['email']) ? (string)$_GET['email'] : '';
$return = isset($_GET['return']) ? (string)$_GET['return'] : '/dashboard.php';

function msg_for(string $err): string {
    return match ($err) {
        'badpass'    => 'The email or password you entered is incorrect.',
        'inactive'   => 'Your account is not active.',
        'exception'  => 'Temporary login issue. Please try again.',
        default      => '',
    };
}
?>
<main class="site-main u-pb-16">
  <div class="container u-mt-10 grid grid-2 gap-8">
    <article class="auth-panel auth-card">
      <h1 class="auth-title">Sign in</h1>
      <p class="auth-sub form-desc">Don’t have an account? <a href="/register.php">Create one</a></p>

      <?php if ($regOk): ?>
        <div class="alert alert--success u-mb-6">Account created. Please sign in.</div>
      <?php endif; ?>

      <?php if ($err && ($m = msg_for($err))): ?>
        <div class="alert alert--error u-mb-6"><?= htmlspecialchars($m) ?></div>
      <?php endif; ?>

      <form class="stack log-form" action="/do_login.php" method="post" novalidate>
        <input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>">

        <label class="field">
          <span class="label">Email address</span>
          <input class="input" type="email" name="email" placeholder="name@email.com"
                 value="<?= htmlspecialchars($emailPrefill) ?>" autocomplete="username" required>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="••••••••"
                 autocomplete="current-password" required>
        </label>

        <div class="row row--between row--align u-mt-2">
          <label class="checkbox remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary u-mt-4" type="submit">Login</button>

        <div class="row u-mt-4">
          <a class="auth-muted" href="/register.php">New to Whoizme? Create account</a>
        </div>
      </form>
    </article>

    <aside class="auth-side login-side">
      <span class="eyebrow sg-muted">WHOIZ.ME</span>
      <h2 class="display u-mt-2">All your links, QR codes & insights — together in one dashboard</h2>
      <p class="sg-muted u-mt-6">Create short links, generate QR codes, and track performance with clean, privacy-first analytics.</p>

      <div class="cta-list u-mt-8">
        <div class="cta-row">
          <div class="cta-icon">✉️</div>
          <div class="cta-body">
            <strong>Contact support</strong>
            <div class="sg-muted">We’re here to help you</div>
          </div>
          <a class="btn btn--ghost" href="mailto:support@whoiz.me">Email us</a>
        </div>

        <div class="cta-row">
          <div class="cta-icon">⭐️</div>
          <div class="cta-body">
            <strong>New to Whoizme?</strong>
            <div class="sg-muted">Create your free account</div>
          </div>
          <a class="btn btn--primary" href="/register.php">Get started</a>
        </div>
      </div>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/partials/landing_footer.php'; ?>