<?php
/** Dashboard (protected) — Whoizme */
declare(strict_types=1);

// -------- bootstrap (safe loader) --------
$boot = [
  __DIR__.'/_bootstrap.php',
  __DIR__.'/bootstrap.php',
  dirname(__DIR__).'/includes/bootstrap.php',
  dirname(__DIR__).'/includes/_bootstrap.php',
];
foreach ($boot as $b) { if (is_file($b)) { require_once $b; break; } }

// -------- auth guard (do not redeclare) --------
if (function_exists('require_login')) {
  require_login(); // existing helper from your stack
} elseif (function_exists('auth_guard')) {
  auth_guard();    // fallback if you use a different name
} else {
  // very light fallback if neither exists
  if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
  if (empty($_SESSION['user'])) {
    $ret = urlencode('/dashboard.php');
    header("Location: /login.php?return={$ret}");
    exit;
  }
}

$title = 'Dashboard — Whoizme';

// Optional helpers
$assetVer = defined('ASSET_VER') ? ASSET_VER : time();
?>
<!doctype html>
<html lang="en" dir="ltr" data-theme="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Favicons (to ensure it shows like register.php) -->
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="alternate icon" href="/favicon.ico" sizes="any">

  <!-- Design System CSS (same stack used by register/login) -->
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= $assetVer ?>">
  <link rel="stylesheet" href="/assets/css/utilities.css?v=<?= $assetVer ?>">
</head>
<body class="app-shell">

<!-- ===== Top Bar (outside) ===== -->
<?php
$topbar = __DIR__.'/partials/app_topbar.php';
$landingHeader = __DIR__.'/partials/landing_header.php';
if (is_file($topbar)) {
  include $topbar;
} elseif (is_file($landingHeader)) {
  // reuse your landing header if that’s your shared topbar
  include $landingHeader;
} else {
  // minimal fallback — no inline css
  ?>
  <header class="topbar">
    <div class="container flex align-center gap-6">
      <a class="brand" href="/"><span class="logo">Whoiz.me</span></a>
      <nav class="nav hide-sm">
        <a class="nav__link" href="/features.php">Features</a>
        <a class="nav__link" href="/help.php">Help</a>
      </nav>
      <div class="flex--auto"></div>
      <a class="btn btn--ghost" href="/logout.php">Logout</a>
    </div>
  </header>
  <?php
}
?>

<!-- ===== App Layout: sidebar + main ===== -->
<div class="app-layout container u-mt-8">
  <aside class="sidebar">
    <?php
    $sidebar = __DIR__.'/partials/app_sidebar.php';
    if (is_file($sidebar)) {
      include $sidebar;
    } else {
      // Sidebar fallback (links are examples; adjust to your routes)
      ?>
      <nav class="menu">
        <div class="menu__group">
          <div class="menu__title">Overview</div>
          <a class="menu__link is-active" href="/dashboard.php">Dashboard</a>
          <a class="menu__link" href="/links.php">Links</a>
          <a class="menu__link" href="/qr.php">QR Codes</a>
          <a class="menu__link" href="/analytics.php">Analytics</a>
        </div>
        <div class="menu__group">
          <div class="menu__title">Account</div>
          <a class="menu__link" href="/settings.php">Settings</a>
          <a class="menu__link" href="/billing.php">Billing</a>
        </div>
      </nav>
      <?php
    }
    ?>
  </aside>

  <main class="app-main">

    <!-- Page header -->
    <div class="page-head">
      <div>
        <h1 class="page-title">My portfolio</h1>
        <p class="page-sub sg-muted">Snapshot of your recent activity, balances and performance.</p>
      </div>
      <div class="page-actions">
        <a class="btn btn--primary" href="/links-create.php">Create link</a>
      </div>
    </div>

    <!-- Top stats (3 cards) -->
    <section class="grid grid--3 grid--gap-lg u-mb-8">
      <article class="card">
        <div class="card__head">
          <div class="badge">Bitcoin</div>
          <div class="sg-muted">2024 → 2025</div>
        </div>
        <div class="stat">
          <div class="stat__num">$69,215.05</div>
          <div class="stat__delta is-up">+4.25%</div>
        </div>
        <div class="mini-chart" data-demo="sparkline-1" aria-hidden="true"></div>
        <div class="card__foot"><button class="btn btn--ghost btn--sm">Buy</button></div>
      </article>

      <article class="card">
        <div class="card__head">
          <div class="badge">Solana</div>
          <div class="sg-muted">Last 30d</div>
        </div>
        <div class="stat">
          <div class="stat__num">$23.08</div>
          <div class="stat__delta is-down">−1.27%</div>
        </div>
        <div class="mini-chart" data-demo="sparkline-2" aria-hidden="true"></div>
        <div class="card__foot"><button class="btn btn--ghost btn--sm">Buy</button></div>
      </article>

      <article class="card">
        <div class="card__head">
          <div class="badge">Ethereum</div>
          <div class="sg-muted">YTD</div>
        </div>
        <div class="stat">
          <div class="stat__num">$1,593.47</div>
          <div class="stat__delta is-up">+2.82%</div>
        </div>
        <div class="mini-chart" data-demo="sparkline-3" aria-hidden="true"></div>
        <div class="card__foot"><button class="btn btn--ghost btn--sm">Buy</button></div>
      </article>
    </section>

    <!-- Middle: left exchange block + right area chart -->
    <section class="grid grid--2 grid--gap-lg u-mb-8">
      <article class="card stack">
        <div class="card__head flex align-center gap-4">
          <div class="avatar"><span class="avatar__fallback">JC</span></div>
          <div>
            <div class="sg-muted">BTC to USDT (BTC/USDT)</div>
            <div class="stat__num u-mt-1">$69,215.05 <span class="stat__delta is-up">+4.25%</span></div>
          </div>
        </div>

        <form class="stack u-mt-4" method="post" action="/api/exchange" onsubmit="return false">
          <label class="field">
            <span class="label">You sell</span>
            <div class="row">
              <input class="input" type="number" step="any" placeholder="0.00">
              <select class="input">
                <option>BTC</option>
                <option>USDT</option>
              </select>
            </div>
          </label>
          <label class="field">
            <span class="label">You get</span>
            <div class="row">
              <input class="input" type="number" step="any" placeholder="0.00">
              <select class="input">
                <option>USDT</option>
                <option>BTC</option>
              </select>
            </div>
          </label>
          <button class="btn btn--primary" type="submit">Exchange</button>
        </form>
      </article>

      <article class="card">
        <div class="card__head flex align-center gap-4">
          <div>
            <div class="sg-muted">Bitcoin to USDT (BTC/USDT)</div>
            <div class="stat__num u-mt-1">$69,215.05 <span class="stat__delta is-up">+4.25%</span></div>
          </div>
          <div class="flex--auto"></div>
          <div class="badge">Mar 2025</div>
        </div>
        <div class="chart" data-demo="area-1" aria-hidden="true"></div>
      </article>
    </section>

    <!-- Table -->
    <section class="card">
      <div class="card__head">
        <h2 class="sg-h sg-h--sm">Cryptocurrencies</h2>
        <div class="flex--auto"></div>
        <div class="row gap-2">
          <button class="btn btn--ghost btn--sm" type="button">Prev</button>
          <button class="btn btn--ghost btn--sm" type="button">Next</button>
        </div>
      </div>

      <div class="table table--dense table--hover">
        <div class="table__head">
          <div class="table__row">
            <div class="table__cell">#</div>
            <div class="table__cell">Name</div>
            <div class="table__cell">Price</div>
            <div class="table__cell">24h</div>
            <div class="table__cell">Market cap</div>
            <div class="table__cell">Volume (24h)</div>
            <div class="table__cell">Circulating</div>
          </div>
        </div>
        <div class="table__body">
          <?php
          // demo rows (replace with real data later)
          $rows = [
            ['#1','Bitcoin','BTC','$69,215.05','+4.25%','$59,327B','$24,479,535B','19,265,850 BTC'],
            ['#2','Ethereum','ETH','$1,593.47','+2.82%','$42,684B','$12,498B','122,373,866 ETH'],
            ['#3','Solana','SOL','$23.08','-1.27%','$32,341B','$3,740B','370,460,000 SOL'],
            ['#4','BNB','BNB','$302.64','+2.53%','$52,444B','$2,893B','157,043,793 BNB'],
          ];
          foreach ($rows as $r): ?>
            <div class="table__row">
              <div class="table__cell"><?= htmlspecialchars($r[0]) ?></div>
              <div class="table__cell"><?= htmlspecialchars($r[1]) ?> <span class="sg-muted u-ml-2"><?= htmlspecialchars($r[2]) ?></span></div>
              <div class="table__cell"><?= htmlspecialchars($r[3]) ?></div>
              <div class="table__cell">
                <span class="delta <?= strpos($r[4], '-') === false ? 'is-up':'is-down' ?>">
                  <?= htmlspecialchars($r[4]) ?>
                </span>
              </div>
              <div class="table__cell"><?= htmlspecialchars($r[5]) ?></div>
              <div class="table__cell"><?= htmlspecialchars($r[6]) ?></div>
              <div class="table__cell"><?= htmlspecialchars($r[7]) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <footer class="u-mt-8 u-mb-10 sg-muted u-center">
      © <?= date('Y') ?> Whoizme
    </footer>
  </main>
</div>

<!-- Minimal JS (no inline styles, no libs) -->
<script>
// Persist/collapse sidebar
(function(){
  const KEY = 'whoizme_sidebar';
  const root = document.documentElement;
  try {
    const saved = localStorage.getItem(KEY);
    if (saved === 'collapsed') root.classList.add('sidebar-collapsed');
  } catch(e){}
  document.addEventListener('click', (e)=>{
    const t = e.target.closest('[data-toggle="sidebar"]');
    if (!t) return;
    root.classList.toggle('sidebar-collapsed');
    try {
      localStorage.setItem(KEY, root.classList.contains('sidebar-collapsed') ? 'collapsed' : 'expanded');
    } catch(e){}
  }, {passive:true});
})();
</script>
</body>
</html>