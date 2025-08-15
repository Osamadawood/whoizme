<?php
// app/includes/layout.php
if (!function_exists('render_header')) {
  function render_header($title=''){
    ?>
    <!doctype html>
    <html lang="en"><head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= h($title ?: 'Whoiz.me') ?></title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
      <style>
        :root{--primary:#3b63ff;--bg:#f6f7fb;--card:#fff;--text:#0f172a;--muted:#64748b;--ok:#e8f0ff;--err:#fee2e2}
        *{box-sizing:border-box} body{margin:0;background:var(--bg);font:16px/1.5 Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--text)}
        .wrap{max-width:960px;margin:0 auto;padding:32px}
        header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
        header a{color:var(--primary);text-decoration:none;font-weight:600}
        .card{background:var(--card);border:1px solid #eef1f7;border-radius:12px;padding:28px;box-shadow:0 6px 18px rgba(16,24,40,.04)}
        .alert{border-radius:10px;padding:12px 14px;margin:10px 0}
        .ok{background:var(--ok);border:1px solid #cddcff}
        .error{background:var(--err);border:1px solid #fecaca}
        input,button{font:inherit}
        input[type=text],input[type=email],input[type=password]{width:100%;padding:12px 14px;border:1px solid #e5e7ef;border-radius:10px;outline:none}
        input:focus{border-color:#c4cffd;box-shadow:0 0 0 3px #e8edff}
        button{background:var(--primary);border:0;color:#fff;font-weight:600;border-radius:10px;padding:12px 16px;cursor:pointer}
        .button-row{margin-top:16px}
        footer{margin:28px 0 8px;color:var(--muted);font-size:13px;text-align:center}
      </style>
    </head><body><div class="wrap">
      <header>
        <div><strong>Whoiz.me</strong></div>
        <nav>
          <?php if (!empty($_SESSION['uid'])): ?>
            <a href="/dashboard.php">Dashboard</a>
            <span style="color:#cbd5e1"> · </span>
            <a href="/logout.php">Logout</a>
          <?php else: ?>
            <a href="/login.php">Login</a>
            <span style="color:#cbd5e1"> · </span>
            <a href="/register.php">Create account</a>
          <?php endif; ?>
        </nav>
      </header>
    <?php
  }
}
if (!function_exists('render_footer')) {
  function render_footer(){
    echo '<footer>© '.date('Y').' Whoiz.me · <a href="/terms.php">Terms</a> · <a href="/privacy.php">Privacy</a></footer></div></body></html>';
  }
}