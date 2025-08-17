<?php
// public/login.php — Whoizme Auth (matches Dashbrd template structure, uses our DS)
declare(strict_types=1);

$bootstrap = __DIR__ . '/_bootstrap.php';
if (is_file($bootstrap)) { require $bootstrap; }
else { require __DIR__ . '/../includes/bootstrap.php'; }

// Already signed in? go to dashboard
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: /dashboard.php');
    exit;
}

$action = is_file(__DIR__ . '/do_login.php') ? '/do_login.php' : '/login.php';
if ($action === '/login.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Dev fallback: sign in as UID=1
    $_SESSION['uid'] = 1;
    header('Location: /dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Sign in · Whoizme</title>
    <link rel="preload" href="/assets/fonts/Objectivity.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/css/app.min.css?v=<?= time() ?>">
  </head>
  <body class="page-auth">

    <main class="auth auth--split">
      <!-- Visual side (keeps aspect similar to Dashbrd template) -->
      <aside class="auth__visual" aria-hidden="true">
        <div class="auth__visual-in">
          <div class="brand-mark">
            <img src="/img/logo.svg" alt="Whoizme" width="32" height="32">
            <span>Whoizme</span>
          </div>
          <h1 class="auth__headline">Welcome back</h1>
          <p class="auth__sub">Access your links, QR codes and analytics in one place.</p>
        </div>
      </aside>

      <!-- Form card -->
      <section class="auth__panel">
        <div class="auth__card card">
          <header class="auth__header">
            <h2 class="h3">Sign in</h2>
            <p class="muted">Use your email and password to continue</p>
          </header>

          <form class="form stack" method="post" action="<?= htmlspecialchars($action, ENT_QUOTES) ?>" autocomplete="off" novalidate>
            <label class="field">
              <span class="label">Email address</span>
              <input class="input" id="email" name="email" type="email" inputmode="email" required placeholder="name@email.com" />
            </label>

            <label class="field">
              <span class="label">Password</span>
              <input class="input" id="password" name="password" type="password" required placeholder="••••••••" />
            </label>

            <div class="row between center">
              <label class="checkbox"><input type="checkbox" name="remember" value="1"> <span>Remember me</span></label>
              <a class="link" href="/forgot.php">Forgot password?</a>
            </div>

            <button class="btn btn--primary btn--lg" type="submit">Continue</button>

            <p class="muted small center">Don’t have an account? <a class="link" href="/register.php">Create one</a></p>
          </form>

          <!-- Optional OAuth row (hidden for now) -->
          <!--
          <div class="oauth row gap-4">
            <button class="btn btn--ghost w-100" type="button">Sign in with Google</button>
            <button class="btn btn--ghost w-100" type="button">GitHub</button>
          </div>
          -->
        </div>

        <footer class="auth__footer muted small center">© <?= date('Y') ?> Whoizme</footer>
      </section>
    </main>

    <script>
      // Apply stored theme preference (dark/light)
      (function(){
        try{
          var t = localStorage.getItem('theme');
          if(t){ document.documentElement.setAttribute('data-theme', t); }
          else if (matchMedia('(prefers-color-scheme: light)').matches) {
            document.documentElement.setAttribute('data-theme','light');
          }
        }catch(e){}
      })();
    </script>
  </body>
</html>