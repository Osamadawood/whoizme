<?php
// public/login.php (styled like Dashbrd X — dark/light ready)

declare(strict_types=1);

$bootstrap = __DIR__ . '/_bootstrap.php';
if (is_file($bootstrap)) { require $bootstrap; }
else { require __DIR__ . '/../includes/bootstrap.php'; }

// If already signed in → go to dashboard
if (function_exists('current_user_id') && current_user_id() > 0) {
    header('Location: /dashboard.php');
    exit;
}

// TEMP: keep simple fallback if do_login.php is not wired yet
$action = is_file(__DIR__ . '/do_login.php') ? '/do_login.php' : '/login.php';

if ($action === '/login.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Dev-only: log in as UID=1
    $_SESSION['uid'] = 1;
    header('Location: /dashboard.php');
    exit;
}

// Detect theme (light/dark) – respects localStorage("theme") if present
$themeAttr = '';
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Sign in · Whoiz.me</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
<link rel="stylesheet" href="/assets/css/app.min.css" />
<style>
/* tiny safety net if SCSS isn't compiled yet */
.auth-page{min-height:100svh;display:grid;place-items:center;background:var(--page-bg,#0b1220);font-family:"IBM Plex Sans Arabic",system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
.auth-shell{position:relative;inline-size:min(1100px,92vw);margin-inline:auto}
.auth-hero{position:absolute;inset-inline:0;top:0;inline-size:100%;block-size:260px;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#0e46ff, #2c6bff)}
.auth-hero img{inline-size:100%;block-size:100%;object-fit:cover;display:block;filter:saturate(110%)}
.auth-card{position:relative;margin:140px auto 32px;inline-size:min(540px,92vw);background:var(--card-bg,#0f1729);color:var(--text,#eaf0ff);border:1px solid var(--card-bd,#1f2a44);border-radius:16px;box-shadow:0 12px 36px rgba(16,23,40,.24);padding:28px 28px 24px}
.auth-title{font-size:clamp(20px,2.2vw,26px);font-weight:700;margin:0 0 8px}
.auth-sub{font-size:14px;opacity:.8;margin:0 0 18px}
.form-row{margin-block:12px}
.label{display:block;font-size:13px;opacity:.85;margin-block:4px}
.input{inline-size:100%;padding:12px 14px;background:var(--field-bg,#0b1325);border:1px solid var(--field-bd,#1f2a44);border-radius:10px;color:inherit}
.btn{inline-size:100%;margin-block:12px 2px;padding:12px 14px;border:0;border-radius:10px;background:#2d5bff;color:#fff;font-weight:700}
.meta{display:flex;justify-content:space-between;gap:8px;font-size:13px;margin-top:6px}
.meta a{color:#8fb3ff;text-decoration:none}
.alt{margin-top:10px;text-align:center;font-size:13px}
.theme-toggle{position:fixed;inset-inline-end:14px;inset-block-start:14px;z-index:2;background:#10182b;color:#cfe1ff;border:1px solid #243251;border-radius:10px;padding:8px 10px;font-size:13px}
:root,[data-theme="light"]{--page-bg:#f7f8fc;--card-bg:#ffffff;--card-bd:#e6e8f0;--field-bg:#ffffff;--field-bd:#dfe3ee;--text:#11203a}
</style>
<script>
(function(){
  try{
    var t = localStorage.getItem('theme');
    if(t){ document.documentElement.setAttribute('data-theme', t); }
    if(!t && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches){
      document.documentElement.setAttribute('data-theme','dark');
    }
  }catch(e){}
})();
</script>
</head>
<body class="auth-page">

<button class="theme-toggle" onclick="
  const r=document.documentElement;
  const cur=r.getAttribute('data-theme')==='dark'?'light':'dark';
  r.setAttribute('data-theme',cur);
  try{localStorage.setItem('theme',cur);}catch(e){}">
  Toggle theme
</button>

<div class="auth-shell">
  <div class="auth-hero" aria-hidden="true">
    <img src="/assets/img/auth-hero.jpg" alt="" />
  </div>

  <main class="auth-card" role="main">
    <h1 class="auth-title">Sign in</h1>
    <p class="auth-sub">Welcome back! Please enter your details to access your dashboard.</p>

    <form method="post" action="<?php echo htmlspecialchars($action, ENT_QUOTES); ?>" autocomplete="off" novalidate>
      <div class="form-row">
        <label class="label" for="email">Email address</label>
        <input class="input" id="email" name="email" type="email" inputmode="email" required />
      </div>

      <div class="form-row">
        <label class="label" for="password">Password</label>
        <input class="input" id="password" name="password" type="password" required />
      </div>

      <button class="btn" type="submit">Continue</button>

      <div class="meta">
        <label><input type="checkbox" name="remember" value="1" /> Remember me</label>
        <a href="/forgot.php">Forgot password?</a>
      </div>
    </form>

    <p class="alt">Don’t have an account? <a href="/register.php">Create one</a></p>
  </main>
</div>

</body>
</html>