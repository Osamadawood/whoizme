<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../app/helpers.php';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

// تحديد المود: من الكويري أو من فلاش (عند الريدايركت بعد خطأ)
$mode = ($_GET['mode'] ?? '') === 'signup' ? 'signup' : 'login';
$forced = flash('form_mode');
if ($forced) $mode = $forced;

// احفظ مسار الرجوع بشكل آمن (منع open redirect)
$next_raw = $_GET['next'] ?? '/dashboard.php';
$next = preg_match('/^\\/[A-Za-z0-9_\\-\\/\\.]*$/', $next_raw) ? $next_raw : '/dashboard.php';

// لو المستخدم داخل بالفعل، رجّعه على طول (بيحترم next)
if (!empty($_SESSION['uid'])) {
  header('Location: ' . $next);
  exit;
}

$page_title = ($mode === 'signup' ? 'Create account' : 'Login') . ' · Whoiz.me';
require __DIR__ . '/partials/landing_header.php';

$errs = errors(); clear_errors();
?>
<div class="grid">
  <div class="card">
    <div class="tabs">
      <a class="tablink <?= $mode==='login'?'active':'' ?>" href="/login.php?mode=login&amp;next=<?= htmlspecialchars($next) ?>">Login</a>
      <a class="tablink <?= $mode==='signup'?'active':'' ?>" href="/login.php?mode=signup&amp;next=<?= htmlspecialchars($next) ?>">Create account</a>
    </div>

    <?php if ($msg = flash('flash_ok')): ?>
      <div class="alert ok"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('flash_error')): ?>
      <div class="alert error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if ($mode==='login'): ?>
      <form method="post" action="/do_login.php" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required>
          <?php if ($e = $errs['email'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label>Password</label>
          <input type="password" name="password" autocomplete="current-password" required>
          <?php if ($e = $errs['password'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px">
          <button class="btn" type="submit">Login</button>
          <a class="muted" href="/reset.php">Forgot password?</a>
        </div>
      </form>

    <?php else: ?>
      <form method="post" action="/register.php" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

        <div class="field">
          <label>Name</label>
          <input type="text" name="name" value="<?= old('name') ?>" autocomplete="name" required>
          <?php if ($e = $errs['name'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= old('email') ?>" autocomplete="email" required>
          <?php if ($e = $errs['email'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label>Password <span class="muted">At least 8 characters.</span></label>
          <input type="password" name="password" minlength="8" autocomplete="new-password" required>
          <?php if ($e = $errs['password'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label>Confirm password</label>
          <input type="password" name="password2" minlength="8" autocomplete="new-password" required>
          <?php if ($e = $errs['password2'] ?? null): ?><div class="field-error"><?= htmlspecialchars($e) ?></div><?php endif; ?>
        </div>

        <label class="checkbox" style="margin-top:12px">
          <input type="checkbox" name="agree" value="1" <?= old('agree') ? 'checked' : '' ?>>
          <span>I agree to the <a href="/terms.php" target="_blank">terms</a> and <a href="/privacy.php" target="_blank">privacy</a>.</span>
        </label>
        <?php if ($e = $errs['agree'] ?? null): ?><div class="field-error" style="margin-top:6px"><?= htmlspecialchars($e) ?></div><?php endif; ?>

        <div style="margin-top:12px">
          <button class="btn" type="submit">Create account</button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3><?= $mode==='signup' ? 'Create your account' : 'Welcome back' ?></h3>
    <p class="muted">Quickly generate and manage QR codes & short links. Your public profile is one click away.</p>
  </div>
</div>
<?php
clear_old();
require __DIR__ . '/partials/landing_footer.php';