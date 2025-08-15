<?php
// public/login.php

$bootstrap = __DIR__ . '/_bootstrap.php';
if (is_file($bootstrap)) { require $bootstrap; }
else { require __DIR__ . '/../includes/bootstrap.php'; }

// لو داخل بالفعل → روح للداشبورد
if (current_user_id() > 0) {
    redirect('/dashboard.php');
}

// Login مؤقت: أي POST يـسجل uid=1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['uid'] = 1;
    redirect('/dashboard.php');
}
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login</title>
<style>
 body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f7f7fb;margin:0;padding:40px}
 .card{max-width:420px;margin:40px auto;background:#fff;border:1px solid #e8e8ef;border-radius:12px;padding:24px;box-shadow:0 6px 24px rgba(16,23,40,.06)}
 h1{margin:0 0 16px;font-size:20px} label{display:block;font-size:13px;margin:12px 0 4px;color:#444}
 input{width:100%;padding:10px 12px;border:1px solid #d9dbe9;border-radius:8px;font-size:14px}
 button{margin-top:16px;width:100%;padding:12px 14px;border:0;border-radius:10px;background:#2859ff;color:#fff;font-weight:600}
 .link{display:block;margin-top:12px;text-align:center;font-size:13px}
</style></head><body>
<div class="card">
  <h1>Sign in</h1>
  <form method="post" action="/login.php" autocomplete="off">
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <button type="submit">Continue</button>
  </form>
  <a class="link" href="/forgot.php">Forgot password?</a>
</div>
</body></html>