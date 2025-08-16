<?php
// public/register.php — standalone Sign Up (view + handler)
// Avoid auth-guard redirects here
if (!defined('SKIP_AUTH_GUARD')) define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';

// Tiny helpers (keeps page self-contained)
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

$errors = [];
$old    = [
    'name'  => trim($_POST['name']  ?? ''),
    'email' => trim($_POST['email'] ?? ''),
];

// If already logged-in, head to dashboard directly
if (function_exists('current_user_id') && current_user_id()) {
    header('Location: /dashboard'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        $name  = $old['name'];
        $email = strtolower($old['email']);
        $pass  = $_POST['password']  ?? '';
        $agree = isset($_POST['agree']);

        if ($name === '')                         { $errors['name'] = 'Please enter your name.'; }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (strlen($pass) < 8)                     { $errors['password'] = 'Password must be at least 8 characters.'; }
        if (!$agree)                               { $errors['agree'] = 'You must agree to the terms to continue.'; }

        if (!$errors) {
            $pdo = db();
            // unique email?
            $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            if ($st->fetch()) {
                $errors['email'] = 'Email already in use. Try signing in.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $now  = date('Y-m-d H:i:s');
                $pdo->prepare('INSERT INTO users (name, email, password_hash, is_active, must_change_password, created_at) VALUES (?, ?, ?, 1, 0, ?)')
                    ->execute([$name, $email, $hash, $now]);
                $uid = (int)$pdo->lastInsertId();

                // login
                session_regenerate_id(true);
                $_SESSION['uid']   = $uid;
                $_SESSION['name']  = $name;
                $_SESSION['email'] = $email;

                header('Location: /dashboard');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign Up · Whoizme</title>
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
  <style>
    /* Minimal safety in case SCSS hasn’t compiled yet */
    .auth{min-height:100vh;display:flex}.auth__left{flex:55%;background:linear-gradient(180deg,#0c2340,#0f2d5b);color:#fff;display:flex;align-items:center;justify-content:center;padding:48px}.auth__right{flex:45%;display:flex;align-items:center;justify-content:center;padding:48px;background:#f8fafc}.auth-card{width:100%;max-width:520px;background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(16,24,40,.08);padding:32px 36px}.auth-title{font-size:28px;font-weight:700;margin:0 0 8px}.auth-sub{color:#667085;margin:0 0 24px;font-size:14px}.form-row{margin-bottom:14px}.form-label{display:block;font-weight:600;margin:0 0 6px;font-size:14px;color:#344054}.form-control{width:100%;border:1px solid #D0D5DD;border-radius:10px;padding:12px 14px;font-size:15px;background:#fff}.form-control:focus{outline:0;border-color:#7AA2FF;box-shadow:0 0 0 4px rgba(99,102,241,.15)}.error{color:#b42318;font-size:13px;margin-top:6px}.btn-primary{width:100%;height:44px;border-radius:10px;background:#2563eb;border:none;color:#fff;font-weight:600}.btn-primary:hover{background:#1d4ed8}.split{margin:16px 0;display:flex;align-items:center;gap:12px;color:#98a2b3;font-size:13px}.split:before,.split:after{content:"";height:1px;background:#e5e7eb;flex:1}.auth-brand{max-width:420px}.auth-logo{width:56px;height:56px;border-radius:12px;background:linear-gradient(135deg,#ff7a18,#af002d 71%);margin:0 0 16px}.auth-welcome{font-size:28px;font-weight:700;margin:0}.auth-desc{color:#cbd5e1;margin:8px 0 0}
  </style>
</head>
<body class="auth">
  <aside class="auth__left">
    <div class="auth-brand">
      <div class="auth-logo"></div>
      <h1 class="auth-welcome">Welcome to Whoizme!</h1>
      <p class="auth-desc">Add a few clicks and make it your personal dashboard.</p>
    </div>
  </aside>
  <main class="auth__right">
    <div class="auth-card" role="region" aria-label="Sign up">
      <h2 class="auth-title">Sign Up</h2>
      <p class="auth-sub">Already have an account? <a href="/login">Sign in</a></p>

      <?php if (!empty($errors['form'])): ?>
        <div class="error" style="margin-bottom:10px;"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <form method="post" action="/register" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="next" value="/dashboard">

        <div class="form-row">
          <label class="form-label" for="name">Full name</label>
          <input class="form-control" id="name" name="name" type="text" placeholder="Enter your full name" value="<?= e($old['name']) ?>" required>
          <?php if (!empty($errors['name'])): ?><div class="error"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
          <label class="form-label" for="email">Email address</label>
          <input class="form-control" id="email" name="email" type="email" placeholder="Enter email address" value="<?= e($old['email']) ?>" required>
          <?php if (!empty($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" id="password" name="password" type="password" placeholder="Enter password" minlength="8" required>
          <?php if (!empty($errors['password'])): ?><div class="error"><?= e($errors['password']) ?></div><?php endif; ?>
        </div>

        <div class="form-row" style="display:flex;align-items:center;gap:10px;">
          <input id="agree" name="agree" type="checkbox" value="1" required>
          <label for="agree" class="form-label" style="margin:0;font-weight:500;">I agree to the <a href="/terms">Terms</a> and <a href="/privacy">Privacy</a></label>
        </div>
        <?php if (!empty($errors['agree'])): ?><div class="error"><?= e($errors['agree']) ?></div><?php endif; ?>

        <div class="form-row">
          <button class="btn-primary" type="submit">Sign Up</button>
        </div>

        <div class="split">or</div>
        <div class="o-auth">
          <button type="button" aria-label="Continue with Google">Continue with Google</button>
        </div>
      </form>
    </div>
  </main>
</body>
</html>