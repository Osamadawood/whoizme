<?php
// public/login.php (auth layout – no global header)
$AUTH_PAGE = true; // flag kept if needed elsewhere
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign in · Whoiz.me</title>
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
</head>
<body class="page-auth">

  <div class="auth-brand">
    <a href="/">
      <span class="auth-brand__dot" aria-hidden="true"></span>
      <span class="auth-brand__name">Whoiz.me</span>
    </a>
  </div>

  <!-- Decorative hero band -->
  <section class="auth-hero" aria-hidden="true"></section>

  <!-- Auth container -->
  <main class="auth-wrap">
    <section class="auth-card">
      <form class="stack" action="/do_login.php" method="post" novalidate>
        <div class="auth-intro u-mb-2">
          <h2 class="sg-h sg-h--lg u-mb-2">Sign in</h2>
          <p class="auth-lead sg-muted">Use your email and password to continue</p>
        </div>

        <label class="field">
          <span class="label">Email address</span>
          <input class="input" type="email" name="email" placeholder="name@email.com" required>
        </label>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="••••••••" required>
        </label>

        <div class="row row--between row--center">
          <label class="checkbox"><input type="checkbox" name="remember"> <span>Remember me</span></label>
          <a class="btn btn--ghost btn--sm" href="/forgot.php">Forgot password?</a>
        </div>

        <button class="btn btn--primary" type="submit">Continue</button>

        <div class="row row--between row--center">
          <span class="auth-muted">Don’t have an account?</span>
          <a class="btn btn--primary btn--sm" href="/register.php">Create one</a>
        </div>
      </form>
    </section>

    <footer class="auth-muted u-mt-6">© <?php echo date('Y'); ?> Whoizme</footer>
  </main>
</body>
</html>