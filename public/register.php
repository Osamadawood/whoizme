<?php
declare(strict_types=1);

/**
 * Register (public)
 * - Public page (PAGE_PUBLIC) so auth guard doesn’t redirect.
 * - POST: validate → hash → safe INSERT.
 * - UI matches login look; keep classes/structure.
 */

if (!defined('PAGE_PUBLIC')) {
    define('PAGE_PUBLIC', true);
}

require dirname(__DIR__) . '/includes/bootstrap.php';

// If already logged-in, go to dashboard
if (function_exists('current_user_id') && (int) current_user_id() > 0) {
    header('Location: /dashboard.php', true, 302);
    exit;
}

/** Helpers */
function f(string $k): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }

/** Discover available password column */
function resolve_password_column(PDO $pdo): ?string {
    $has = static function(string $col) use ($pdo): bool {
        $s = $pdo->prepare("SHOW COLUMNS FROM `users` LIKE ?");
        $s->execute([$col]);
        return (bool)$s->fetch(PDO::FETCH_ASSOC);
    };
    if ($has('password_hash')) return 'password_hash';
    if ($has('pass_hash'))      return 'pass_hash';
    return null;
}

$APP_DEBUG = defined('APP_DEBUG') ? (bool)APP_DEBUG : (getenv('APP_DEBUG') === '1');

$errors = [];
$flash  = null;

// CSRF token (simple per-page token)
if (empty($_SESSION['csrf_reg'])) {
    $_SESSION['csrf_reg'] = bin2hex(random_bytes(16));
}
$csrf_token = (string) $_SESSION['csrf_reg'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf']) || !hash_equals($csrf_token, (string)$_POST['csrf'])) {
        $errors['general'] = 'Your session expired. Please try again.';
    }

    $name      = f('name');
    $email     = mb_strtolower(f('email'));
    $password  = f('password');
    $password2 = f('password_confirm');
    $agree     = isset($_POST['agree']) ? '1' : '';

    // Validation (no style changes)
    if ($name === '' || mb_strlen($name) < 2)        $errors['name'] = 'Please enter your full name.';
    if ($name !== '' && mb_strlen($name) > 100)      $errors['name'] = 'Name is too long.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors['email'] = 'Please enter a valid email address.';
    if ($email !== '' && mb_strlen($email) > 190)    $errors['email'] = 'Email is too long.';
    if (mb_strlen($password) < 8)                    $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $password2)                    $errors['password_confirm'] = 'Passwords do not match.';
    if ($agree !== '1')                              $errors['agree'] = 'Please accept our Terms & Privacy.';

    $pwdCol = resolve_password_column($pdo);
    if ($pwdCol === null) {
        $errors['general'] = 'We couldn’t save the password due to a server field mismatch. Please contact support.';
    }

    if (!$errors) {
        try {
            // Ensure unique email
            $q = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $q->execute([$email]);
            if ($q->fetch(PDO::FETCH_ASSOC)) {
                $errors['email'] = 'This email is already registered.';
            } else {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $sql  = sprintf("INSERT INTO users (name, email, `%s`, is_active, created_at) VALUES (?, ?, ?, 1, NOW())", $pwdCol);
                $ins  = $pdo->prepare($sql);
                $ins->execute([$name, $email, $hash]);

                $pdo->commit();

                // Success → redirect to login with prefilled email
                unset($_SESSION['csrf_reg']);
                header('Location: /login.php?registered=1&email=' . urlencode($email), true, 302);
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $code = $e->getCode();
            $msg  = $e->getMessage();

            if ($code === '23000' || stripos($msg, 'Duplicate') !== false) {
                $errors['email'] = 'This email is already registered.';
            } elseif (stripos($msg, 'pass_hash') !== false || stripos($msg, 'password_hash') !== false) {
                $errors['general'] = 'We couldn’t save the password due to a server field mismatch. Please contact support.';
            } else {
                $errors['general'] = 'We couldn’t create your account right now. Please try again.';
            }
            if ($APP_DEBUG) error_log('[register][PDO] '.$msg);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors['general'] = 'We couldn’t create your account right now. Please try again.';
            if ($APP_DEBUG) error_log('[register][THROWABLE] '.$e->getMessage());
        }
    }
}

// Header (landing)
$page_title = 'Create account';
$page_class = 'page-auth';
require __DIR__ . '/partials/landing_header.php';
?>
<main class="site-main u-pb-16">

  <!-- hero strip (image) -->
  <section class="hero-strip u-mt-8">
    <div class="container">
      <div class="hero-card">
        <img src="/assets/img/hero-blue.jpg" alt="" class="hero-bg" />
      </div>
    </div>
  </section>

  <div class="container u-mt-8 grid grid-2 gap-8">

    <!-- form card -->
    <article class="auth-panel auth-card">

      <h1 class="auth-title">Create your account</h1>
      <p class="auth-sub form-desc">
        Already have an account? <a href="/login.php">Sign in</a>
      </p>

      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert--error u-mb-6">
          <?= htmlspecialchars($errors['general']) ?>
        </div>
      <?php endif; ?>

      <form class="stack log-form" action="/register.php" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_token) ?>" />

        <label class="field">
          <span class="label">Full name</span>
          <input class="input<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                 type="text" name="name" placeholder="Your name"
                 value="<?= htmlspecialchars(f('name')) ?>" required>
          <?php if (isset($errors['name'])): ?><small class="field-error"><?= htmlspecialchars($errors['name']) ?></small><?php endif; ?>
        </label>

        <label class="field">
          <span class="label">Email address</span>
          <input class="input<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                 type="email" name="email" placeholder="name@email.com"
                 value="<?= htmlspecialchars(f('email')) ?>" autocomplete="username" required>
          <?php if (isset($errors['email'])): ?><small class="field-error"><?= htmlspecialchars($errors['email']) ?></small><?php endif; ?>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                 type="password" name="password" placeholder="Create a strong password"
                 autocomplete="new-password" required>
          <?php if (isset($errors['password'])): ?><small class="field-error"><?= htmlspecialchars($errors['password']) ?></small><?php endif; ?>
        </label>

        <label class="field">
          <span class="label">Confirm password</span>
          <input class="input<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>"
                 type="password" name="password_confirm" placeholder="Repeat your password"
                 autocomplete="new-password" required>
          <?php if (isset($errors['password_confirm'])): ?><small class="field-error"><?= htmlspecialchars($errors['password_confirm']) ?></small><?php endif; ?>
        </label>

        <label class="checkbox row u-mt-2">
          <input type="checkbox" name="agree" value="1" <?= f('agree') ? 'checked' : '' ?>>
          <span>I agree to the <a href="/terms.php" target="_blank">Terms</a> and <a href="/privacy.php" target="_blank">Privacy</a>.</span>
        </label>
        <?php if (isset($errors['agree'])): ?><small class="field-error u-mt-1"><?= htmlspecialchars($errors['agree']) ?></small><?php endif; ?>

        <button class="btn btn--primary u-mt-4" type="submit">Create account</button>

        <div class="row u-mt-4">
          <a class="auth-muted" href="/login.php">Have an account? Sign in</a>
        </div>
      </form>
    </article>

    <!-- right column -->
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