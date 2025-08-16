<?php
if (!defined('SKIP_AUTH_GUARD')) { define('SKIP_AUTH_GUARD', true); }
// ------------------------------------------------------------ 
// Whoizme · Design System Preview 
// هذه الصفحة مرجع أساسي للألوان، التايبوجرافي، الكومبوننتس، والـ Utilities 
// ------------------------------------------------------------
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php'; // لو اسم اللودر عندك مختلف عدّله

// عنوان الصفحة
$title = 'Style Guide · Whoizme';
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>

  <!-- CSS العام للمشروع -->
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- لو بتجيب الخطوط محلياً من الـ SCSS سيبه زي ما هو؛ اللينك ده احتياطي أثناء التطوير -->
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.min.css?v=<?php echo time(); ?>"/>

  <!-- ستايل داخلي بسيط لتخطيط صفحة الـ Style Guide فقط -->
  <style>
    .sg-wrap{max-width:1200px;margin-inline:auto;padding:2rem;display:grid;gap:2rem}
    .sg-head{display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .sg-pill{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .8rem;border-radius:999px;background:color-mix(in oklab,var(--surface),var(--text)/8%);font:500 0.875rem/1.4 var(--font, system-ui)}
    .sg-grid{display:grid;gap:1rem}
    .sg-grid.cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sg-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
    .sg-card{border-radius:1rem;padding:1.25rem;background:var(--surface);box-shadow:0 1px 0 color-mix(in oklab,var(--text),transparent 90%) inset}
    .sg-card h3{margin:0 0 .75rem}
    .sg-muted{color:var(--muted)}
    .swatch{height:76px;border-radius:.75rem;border:1px solid color-mix(in oklab,var(--text),transparent 85%);display:flex;align-items:end;padding:.5rem;font:600 0.8rem/1 var(--font, system-ui)}
    .row{display:flex;flex-wrap:wrap;gap:.75rem}
    .stack>*+*{margin-top:.75rem}
    .sg-footer{padding:2rem 0;color:var(--muted);text-align:center}
    /* ترويسة ثابتة بسيطة */
    .sg-topbar{position:sticky;top:0;z-index:5;background:color-mix(in oklab,var(--page-bg),transparent 0%);backdrop-filter:saturate(120%) blur(4px);border-bottom:1px solid color-mix(in oklab,var(--text),transparent 90%)}
    .sg-topbar-inner{max-width:1200px;margin-inline:auto;display:flex;gap:1rem;align-items:center;justify-content:space-between;padding:1rem 2rem}
    .sg-brand{display:flex;align-items:center;gap:.6rem;font-weight:700}
    .sg-brand-badge{width:28px;height:28px;border-radius:8px;background:linear-gradient(145deg,color-mix(in oklab,var(--brand),#fff 6%), color-mix(in oklab,var(--brand-700, var(--brand)),#000 8%))}
    /* زر السويتشر */
    .theme-switch{display:inline-flex;align-items:center;gap:.5rem;border:1px solid color-mix(in oklab,var(--text),transparent 85%);padding:.4rem .75rem;border-radius:999px;background:color-mix(in oklab,var(--surface),var(--text)/6%);cursor:pointer;font:500 .9rem var(--font, system-ui)}
    .theme-switch input{display:none}
  </style>
</head>
<body>

<header class="sg-topbar">
  <div class="sg-topbar-inner">
    <div class="sg-brand">
      <span class="sg-brand-badge"></span>
      <span>Whoizme · Style Guide</span>
    </div>

    <label class="theme-switch" title="Toggle theme">
      <input id="themeToggle" type="checkbox" />
      <span>Light</span>
    </label>
  </div>
</header>

<main class="sg-wrap">

  <section class="sg-head">
    <div>
      <div class="sg-pill">Design System Reference</div>
      <h1 style="margin:.5rem 0 0">Styles &amp; Components</h1>
      <p class="sg-muted">This page showcases the core tokens, typography, components and utilities driven by CSS variables and SCSS mixins.</p>
    </div>
  </section>

  <!-- Colors / Tokens -->
  <section class="sg-card">
    <h3>Colors · Tokens</h3>
    <div class="sg-grid cols-3">
      <div class="stack">
        <div class="swatch" style="background:var(--page-bg);">--page-bg</div>
        <div class="swatch" style="background:var(--surface);">--surface</div>
        <div class="swatch" style="background:var(--muted-bg, color-mix(in oklab,var(--surface),var(--text)/6%));">--muted-bg</div>
      </div>
      <div class="stack">
        <div class="swatch" style="background:var(--brand);">--brand</div>
        <div class="swatch" style="background:var(--brand-600, color-mix(in oklab,var(--brand),#000 8%));">--brand-600</div>
        <div class="swatch" style="background:var(--accent, color-mix(in oklab,var(--brand),#fff 20%));">--accent</div>
      </div>
      <div class="stack">
        <div class="swatch" style="background:var(--text); color:var(--page-bg)">--text</div>
        <div class="swatch" style="background:var(--muted); color:var(--page-bg)">--muted</div>
        <div class="swatch" style="background:var(--success, #16a34a);">--success</div>
      </div>
    </div>
  </section>

  <!-- Typography -->
  <section class="sg-card">
    <h3>Typography</h3>
    <div class="stack">
      <h1>Display / H1 — سطر عنوان رئيسي</h1>
      <h2>Heading / H2 — عنوان ثانوي</h2>
      <h3>Heading / H3 — عنوان قسم</h3>
      <p>Body text / Paragraph — نص تجريبي لعرض الخط <strong>IBM Plex Sans Arabic</strong> مع دعم العربية والإنجليزية بسلاسة.</p>
      <p class="sg-muted">Muted text — نص ثانوي للتوضيح.</p>
      <a class="btn btn--link" href="#">Inline link</a>
    </div>
  </section>

  <!-- Buttons -->
  <section class="sg-card">
    <h3>Buttons</h3>
    <div class="row">
      <button class="btn">Default</button>
      <button class="btn btn--primary">Primary</button>
      <button class="btn btn--ghost">Ghost</button>
      <button class="btn btn--danger">Danger</button>
      <button class="btn" disabled>Disabled</button>
      <button class="btn btn--primary" style="--btn-size:sm">Small</button>
      <button class="btn btn--primary" style="--btn-size:lg">Large</button>
    </div>
  </section>

  <!-- Forms -->
  <section class="sg-card">
    <h3>Forms</h3>
    <form class="stack" action="#" method="post" onsubmit="return false">
      <label class="field">
        <span class="label">Full name</span>
        <input class="input" type="text" placeholder="John Carter">
      </label>

      <label class="field">
        <span class="label">Email</span>
        <input class="input" type="email" placeholder="name@email.com">
      </label>

      <label class="field">
        <span class="label">Password</span>
        <input class="input" type="password" placeholder="••••••••">
      </label>

      <div class="row">
        <label class="checkbox"><input type="checkbox"> <span>I agree to the terms</span></label>
        <label class="switch"><input type="checkbox" checked> <span>Subscribe</span></label>
      </div>

      <div class="row">
        <button class="btn btn--primary" type="submit">Submit</button>
        <button class="btn btn--ghost" type="button">Cancel</button>
      </div>
    </form>
  </section>

  <!-- Cards / Layout -->
  <section class="sg-card">
    <h3>Cards &amp; Layout</h3>
    <div class="sg-grid cols-3">
      <article class="card">
        <header class="card__header">
          <h4 class="card__title">Basic card</h4>
          <div class="card__meta sg-muted">Meta</div>
        </header>
        <div class="card__body">
          <p>Content area inside a neutral surface using design tokens.</p>
        </div>
        <footer class="card__footer"><button class="btn btn--primary btn--sm">Action</button></footer>
      </article>

      <article class="card">
        <header class="card__header"><h4 class="card__title">Stats</h4></header>
        <div class="card__body">
          <div style="font:700 2rem/1 var(--font, system-ui)">12,680</div>
          <div class="sg-muted">Monthly scans</div>
        </div>
      </article>

      <article class="card">
        <header class="card__header"><h4 class="card__title">List</h4></header>
        <ul class="card__body" style="display:grid;gap:.5rem;padding-left:1rem">
          <li>Item one</li><li>Item two</li><li>Item three</li>
        </ul>
      </article>
    </div>
  </section>

  <!-- Utilities preview (مثال بسيط) -->
  <section class="sg-card">
    <h3>Utilities</h3>
    <div class="row">
      <span class="badge">.badge</span>
      <span class="badge badge--brand">.badge--brand</span>
      <span class="badge badge--muted">.badge--muted</span>
    </div>
  </section>

  <p class="sg-footer">© <?= date('Y') ?> Whoizme · Design System preview</p>
</main>

<script>
  // Theme toggle: يضيف/يشيل data-theme="light"
  const toggle = document.getElementById('themeToggle');
  const root = document.documentElement;
  // دايفولت: dark (حسب variables في :root)
  toggle.addEventListener('change', () => {
    if (toggle.checked) {
      root.setAttribute('data-theme','light');
    } else {
      root.removeAttribute('data-theme'); // يرجع للـ :root (dark)
    }
  });
</script>
</body>
</html>