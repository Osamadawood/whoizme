<?php require __DIR__ . "/_bootstrap.php"; ?>
<?php
// public/templates.php
$templates = [
  [
    'name' => 'Minimal',
    'query'=> '/?tpl=minimal&fg=%230f172a&bg=%23ffffff&frame=none',
    'tag'  => 'Clean',
    'svg'  => '<svg viewBox="0 0 120 120"><rect width="120" height="120" fill="#fff"/><rect x="10" y="10" width="100" height="100" fill="#0f172a"/></svg>'
  ],
  [
    'name' => 'Scan Badge',
    'query'=> '/?tpl=scan-badge&frame=badge&fg=%230d6efd&bg=%23ffffff',
    'tag'  => 'CTA',
    'svg'  => '<svg viewBox="0 0 120 120"><rect width="120" height="120" rx="14" fill="#0d6efd"/><rect x="22" y="22" width="76" height="76" fill="#fff"/></svg>'
  ],
  [
    'name' => 'Ribbon Black',
    'query'=> '/?tpl=ribbon-black&frame=ribbon&fg=%23000000&bg=%23ffffff',
    'tag'  => 'Bold',
    'svg'  => '<svg viewBox="0 0 120 120"><rect width="120" height="120" fill="#fff"/><rect x="15" y="15" width="90" height="90" fill="#000"/></svg>'
  ],
  [
    'name' => 'Brand Blue',
    'query'=> '/?tpl=brand-blue&fg=%230d6efd&bg=%23eef2ff&frame=none',
    'tag'  => 'Brand',
    'svg'  => '<svg viewBox="0 0 120 120"><rect width="120" height="120" fill="#eef2ff"/><rect x="18" y="18" width="84" height="84" fill="#0d6efd"/></svg>'
  ],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Templates – whoiz.me</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/assets/landing.css">
<style>
  body{font-family:ui-sans-serif,system-ui}
  .wrap{max-width:1100px;margin:0 auto;padding:32px 20px}
  .grid{display:grid;gap:16px}
  @media(min-width:900px){.grid{grid-template-columns:repeat(4,1fr)}}
  .card{border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;background:#fff}
  .thumb{aspect-ratio:1/1;background:#f8fafc;display:grid;place-items:center}
  .thumb svg{width:60%;height:60%}
  .meta{padding:12px}
  .tag{font-size:12px;background:#eef2ff;color:#1d4ed8;border-radius:999px;padding:2px 8px}
  .name{font-weight:600}
  .go{display:inline-block;margin-top:8px;text-decoration:none;color:#0d6efd}
</style>
</head>
<body>
<header class="wrap" style="display:flex;justify-content:space-between;align-items:center">
  <a href="/" style="text-decoration:none;font-weight:700;color:#0f172a">whoiz.me</a>
  <nav><a href="/pricing">Pricing</a> · <a href="/help">Help</a></nav>
</header>

<main class="wrap">
  <h1>Pick a template</h1>
  <p class="muted">اضغط على أي قالب وسنملأ الإعدادات أوتوماتيك في الصفحة الرئيسية.</p>
  <div class="grid" style="margin-top:16px">
    <?php foreach ($templates as $t): ?>
      <article class="card">
        <a class="thumb" href="<?= htmlspecialchars($t['query']) ?>" aria-label="Use <?= htmlspecialchars($t['name']) ?>">
          <?= $t['svg'] ?>
        </a>
        <div class="meta">
          <div class="name"><?= htmlspecialchars($t['name']) ?></div>
          <div class="tag"><?= htmlspecialchars($t['tag']) ?></div>
          <a class="go" href="<?= htmlspecialchars($t['query']) ?>">Use template →</a>
        </div>
      </article>
    <?php endforeach ?>
  </div>
</main>

<footer class="wrap" style="margin:40px 0;color:#64748b">
  © <?= date('Y') ?> whoiz.me
</footer>
</body>
</html>