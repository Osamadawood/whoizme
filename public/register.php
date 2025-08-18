<?php define('PUBLIC_PAGE', true); ?>
<?php
// public/register.php
$AUTH_PAGE = true;
require __DIR__ . '/partials/app_header.php';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create account · Whoizme</title>
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>">
</head>
<body class="page-auth">

  <section class="auth-hero" aria-hidden="true"></section>

  <main class="auth-wrap">
    <header class="auth-head">
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-lead">Join Whoizme to manage links, QR codes and see analytics.</p>
    </header>

    <section class="auth-card">
      <form class="stack" action="/do_register.php" method="post" novalidate>
        <div class="row">
          <label class="field" style="flex:1">
            <span class="label">Full name</span>
            <input class="input" type="text" name="name" placeholder="John Carter" required>
          </label>
          <label class="field" style="flex:1">
            <span class="label">Email address</span>
            <input class="input" type="email" name="email" placeholder="example@youremail.com" required>
          </label>
        </div>

        <label class="field">
          <span class="label">Password</span>
          <input class="input" type="password" name="password" placeholder="Enter a strong password" required>
        </label>

        <label class="checkbox">
          <input type="checkbox" name="agree" required>
          <span>I have read and agree to the <a class="btn btn--link" href="/terms.php">Terms &amp; Conditions</a></span>
        </label>

        <button class="btn btn--primary" type="submit">Create account</button>

        <div class="row row--between row--center">
          <span class="auth-muted">Already have an account?</span>
          <a class="btn btn--ghost btn--sm" href="/login.php">Sign in</a>
        </div>
      </form>
    </section>

    <footer class="auth-muted u-mt-6">© <?php echo date('Y'); ?> Whoizme</footer>
  </main>
</body>
</html>
