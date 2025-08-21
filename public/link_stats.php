<?php require __DIR__ . "/_bootstrap.php"; ?>

<?php
// Stats page for short links (list + filters + pagination + CSV export)
require __DIR__ . '/../includes/auth.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/database.php';
$db  = new Database($config['db']);
$pdo = $db->pdo();

$uid = (int)($_SESSION['uid'] ?? 0);
if ($uid <= 0) { header('Location: /'); exit; }

function h($v){
  if ($v === null) return '';
  if (is_object($v) || is_array($v)) return '';
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function gp(string $k, $def=null){ return isset($_GET[$k]) && $_GET[$k] !== '' ? trim((string)$_GET[$k]) : $def; }

// normalize possible dd/mm/yyyy into yyyy-mm-dd
function normDate($s){
  if (!$s) return '';
  $s = trim((string)$s);
  if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
    return $m[3] . '-' . $m[2] . '-' . $m[1];
  }
  return $s; // assume already yyyy-mm-dd
}

// Inputs / filters (all optional)
$q        = gp('q', '');        // free text in label/target_url/code
$code     = gp('code', '');     // exact code
$label    = gp('label', '');    // exact label
$dateFrom = gp('date_from', '');
$dateTo   = gp('date_to', '');
$dateFromN = normDate($dateFrom);
$dateToN   = normDate($dateTo);
$page     = max(1, (int)gp('page', 1));
$perPage  = min(100, max(1, (int)gp('per_page', 20)));
$export   = (int)gp('export', 0);

// Build WHERE (date filters require joining hits)
$where = ["sl.user_id = :uid"];
$params = [':uid' => $uid];
if ($q !== '') {
  $where[] = "(sl.label LIKE :q1 OR sl.target_url LIKE :q2 OR sl.code LIKE :q3)";
  $params[':q1'] = "%$q%";
  $params[':q2'] = "%$q%";
  $params[':q3'] = "%$q%";
}
if ($code !== '') { $where[] = "sl.code = :code"; $params[':code'] = $code; }
if ($label !== '') { $where[] = "sl.label = :label"; $params[':label'] = $label; }
if ($dateFromN !== '') { $where[] = "sh.created_at >= :date_from"; $params[':date_from'] = $dateFromN . ' 00:00:00'; }
if ($dateToN   !== '') { $where[] = "sh.created_at <= :date_to";   $params[':date_to']   = $dateToN   . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count for pagination
$countSql = "
  SELECT COUNT(*) AS total FROM (
    SELECT sl.id
    FROM short_links sl
    LEFT JOIN short_link_hits sh ON sh.code = sl.code
    $whereSql
    GROUP BY sl.id
  ) t
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalLinks = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($totalLinks / $perPage));
$offset = ($page - 1) * $perPage;

// Main query (one row per link with aggregates)
$sql = "
  SELECT
    sl.id,
    sl.code,
    sl.label,
    sl.target_url,
    sl.created_at AS link_created_at,
    sl.updated_at AS link_updated_at,
    COUNT(sh.id) AS total_hits,
    COUNT(DISTINCT sh.ip) AS unique_ips,
    MAX(sh.created_at) AS last_hit_at
  FROM short_links sl
  LEFT JOIN short_link_hits sh ON sh.code = sl.code
  $whereSql
  GROUP BY sl.id, sl.code, sl.label, sl.target_url, sl.created_at, sl.updated_at
  ORDER BY (MAX(sh.created_at) IS NULL), MAX(sh.created_at) DESC, sl.created_at DESC
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$linkRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SAFE MODE (plain text, no layout) for quick verification
$safe = (int)gp('safe', 0);
if ($safe === 1) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "SAFE MODE: link_stats.php\n";
  echo "uid=".(int)$uid."\n";
  echo "totalLinks=".(int)$totalLinks."\n";
  echo "rows_count=".count($linkRows)."\n";
  echo "rows_sample:\n";
  echo var_export(array_slice($linkRows, 0, 5), true)."\n";
  exit;
}

// Optional DEBUG block (prints after header)
$debug = (int)gp('debug', 0);
$debugHtml = '';
if ($debug === 1) {
  ob_start();
  echo "<pre style='background:#111;color:#0f0;padding:12px;border-radius:8px;white-space:pre-wrap;direction:ltr'>";
  echo "DEBUG link_stats.php\n\n";
  echo "Params:\n" . htmlspecialchars(var_export($params, true)) . "\n\n";
  echo "TotalLinks: ".(int)$totalLinks." | Rows on this page: ".count($linkRows)." | Page: ".(int)$page." / ".(int)$pages."\n\n";
  echo "Count SQL:\n" . htmlspecialchars($countSql) . "\n\n";
  echo "Main SQL:\n" . htmlspecialchars($sql) . "\n\n";
  echo "Rows sample (first 2):\n" . htmlspecialchars(var_export(array_slice($linkRows, 0, 2), true)) . "\n";
  echo "</pre>";
  $debugHtml = ob_get_clean();
}

// CSV export (current page rows)
if ($export === 1) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="link_stats_page_' . $page . '.csv"');
  $out = fopen('php://output', 'w');
  fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
  fputcsv($out, ['Code','Label','Target URL','Total Hits','Unique IPs','Last Hit','Created At','Updated At']);
  foreach ($linkRows as $r) {
    fputcsv($out, [
      $r['code'],
      $r['label'],
      $r['target_url'],
      (int)$r['total_hits'],
      (int)$r['unique_ips'],
      $r['last_hit_at'] ?? '',
      $r['link_created_at'] ?? '',
      $r['link_updated_at'] ?? ''
    ]);
  }
  fclose($out);
  exit;
}

require_once __DIR__ . '/../includes/bootstrap.php';
if (auth_role() === 'admin') {
  include INC_PATH . '/partials/admin_header.php';
} else {
  include INC_PATH . '/partials/user_header.php';
}
$base = rtrim($config['base_url'] ?? '', '/');
?>
<?php $showCards = ((int)gp('cards', 0) === 1); ?>
<style>
  /* --- Polished, lightweight styling --- */
  .stats-wrap{--b:#e5e7eb;--bg:#f8fafc;--muted:#6b7280;--chip:#eef2ff;--chip-t:#3730a3}
  .stats-wrap .filters input, .stats-wrap .filters button, .stats-wrap .filters a{
    font: inherit; padding:8px 10px; border:1px solid var(--b); border-radius:8px; background:#fff;
  }
  .stats-wrap .filters button{background:$brand; color:#fff; border-color:$brand; cursor:pointer}
  .stats-wrap .filters button:hover{filter:brightness(.95)}
  .stats-wrap .filters a{background:var(--chip); color:var(--chip-t); text-decoration:none}
  .stats-wrap .badge{display:inline-block; padding:.25rem .6rem; border-radius:999px; background:var(--chip); color:var(--chip-t)}
  .stats-wrap table{width:100%; border-collapse:separate; border-spacing:0; background:#fff; border:1px solid var(--b); border-radius:10px; overflow:hidden}
  .stats-wrap thead th{background:#f3f4f6; text-align:left; padding:10px 12px; border-bottom:1px solid var(--b); font-weight:600}
  .stats-wrap tbody td{padding:10px 12px; border-top:1px solid var(--b); vertical-align:top}
  .stats-wrap tbody tr:nth-child(even){background:#fcfdff}
  .stats-wrap tbody tr:hover{background:#f9fbff}
  .stats-wrap a.link{color:$brand; text-decoration:none}
  .stats-wrap a.link:hover{text-decoration:underline}
  .stats-wrap .pagination a, .stats-wrap .pagination span{padding:.35rem .65rem; border:1px solid var(--b); border-radius:8px; text-decoration:none}
  .stats-wrap .pagination span{background:#f3f4f6}
  /* actions (kebab) menu */
  .actions-cell{position:relative}
  .menu-btn{border:1px solid var(--b); background:#fff; border-radius:8px; padding:6px 8px; cursor:pointer}
  .menu-btn:hover{background:#f3f4f6}
  .menu{
    position: fixed; /* was absolute */
    right: auto; top: auto; /* will be set via JS */
    background:#fff;
    border:1px solid var(--b);
    border-radius:10px;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
    min-width:180px;
    z-index:9999;
    display:none;
    max-height:50vh;
    overflow:auto;
  }
  .menu a{display:block; padding:9px 12px; text-decoration:none; color:#111}
  .menu a:hover{background:#f6f7f9}
  .muted{color:var(--muted); font-size:13px}
  .quick-ranges{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0 6px}
  .quick-ranges button{
    padding:6px 10px;
    border:1px solid #d1d5db; /* neutral gray */
    border-radius:999px;
    background:#fff;
    color:#374151;
    font-size:13px;
    cursor:pointer
  }
  .quick-ranges button:hover{background:#f3f4f6}
  .quick-ranges button:active{transform:translateY(1px)}
  @media (max-width: 860px){
    .stats-wrap .filters{grid-template-columns:repeat(2, minmax(0,1fr))}
    .stats-wrap table{font-size:14px}
  }
  /* ===== Bitly-like links list ===== */
  .links-list{margin-top:12px;display:flex;flex-direction:column;gap:10px}
  .link-row{
    display:grid;grid-template-columns:1fr auto;gap:14px;
    padding:14px;background:#fff;border:1px solid #e6e8eb;border-radius:12px
  }
  .link-row:hover{box-shadow:0 6px 14px rgba(16,24,40,.06)}
  .row-main{display:flex;gap:12px}
  .favicon{flex:0 0 auto;width:40px;height:40px;border-radius:10px;display:grid;place-items:center;background:#f3f4f6;border:1px solid #e6e8eb}
  .favicon img{width:20px;height:20px}
  .row-text{display:flex;flex-direction:column;gap:4px;min-width:0}
  .row-title{font-weight:600;font-size:15px;color:#0f172a;display:flex;align-items:center;gap:8px}
  .row-title .badge{font-size:12px;padding:2px 8px;background:#eef2ff;color:#3730a3;border-radius:999px}
  .row-short{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
  .row-short a{color:$brand;text-decoration:none;word-break:break-all}
  .row-long{color:#64748b;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
  .row-meta{display:flex;gap:16px;flex-wrap:wrap;color:#475569;font-size:12px;margin-top:6px}
  .row-meta .dot{width:4px;height:4px;background:#cbd5e1;border-radius:50%}
  .row-actions{display:flex;align-items:center;gap:8px}
  .btn-pill{border:1px solid #e6e8eb;background:#fff;border-radius:10px;padding:8px 10px;cursor:pointer;font-size:13px}
  .btn-pill:hover{background:#f8fafc}
  .kebab{width:36px;height:36px;border-radius:10px;border:1px solid #e6e8eb;background:#fff;cursor:pointer}
  .kebab:hover{background:#f3f4f6}
  .menu{min-width:190px} /* reuse previous menu, ensure width */
  @media (max-width:780px){
    .link-row{grid-template-columns:1fr;gap:10px}
    .row-actions{justify-content:flex-start}
  }
</style>
<?= $debugHtml ?? '' ?>
<main class="container stats-wrap" style="padding:16px 0">

  <h2>Statistics</h2>

  <form method="get" class="filters" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:end;background:#f6f7f9;padding:12px 16px;border-radius:12px">
    <div>
      <label>Search</label>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="keyword (code/label/url)">
    </div>
    <div>
      <label>Code</label>
      <input type="text" name="code" value="<?= h($code) ?>">
    </div>
    <div>
      <label>Label</label>
      <input type="text" name="label" value="<?= h($label) ?>">
    </div>
    <div>
      <label>From date</label>
      <input type="date" name="date_from" value="<?= h($dateFrom) ?>">
    </div>
    <div>
      <label>To date</label>
      <input type="date" name="date_to" value="<?= h($dateTo) ?>">
    </div>
    <div>
      <label>Per page</label>
      <input type="number" name="per_page" min="1" max="100" value="<?= (int)$perPage ?>">
    </div>
    <div style="grid-column:1/-1;display:flex;gap:8px;margin-top:6px;align-items:center">
      <button type="submit">Apply filters</button>
      <a class="link" href="/link_stats.php">Clear</a>
      <a class="link" href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>">Export CSV (current page)</a>
    </div>
  </form>

  <p class="badge" style="margin-top:10px">Matched links: <?= (int)$totalLinks ?> — Page <?= (int)$page ?> / <?= (int)$pages ?></p>
  <div class="muted" style="margin-top:6px">Rows on this page: <b><?= count($linkRows) ?></b></div>

  <div class="quick-ranges">
    <button type="button" data-range="today" title="Set From/To to today">Today</button>
    <button type="button" data-range="7" title="Last 7 days">Last 7 days</button>
    <button type="button" data-range="30" title="Last 30 days">Last 30 days</button>
    <button type="button" data-range="month" title="From 1st of this month">This month</button>
    <button type="button" data-range="clear" title="Clear date filters">Clear dates</button>
  </div>

  <!-- Bitly-like links list -->
  <div class="links-list">
    <?php if (!$linkRows): ?>
      <div style="padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;color:#777;text-align:center">
        No results match the current filters (total links: <?= (int)$totalLinks ?>)
      </div>
    <?php else: foreach ($linkRows as $r): ?>
      <?php
        $code   = (string)($r['code'] ?? '');
        $label  = (string)($r['label'] ?? '');
        $target = (string)($r['target_url'] ?? '');
        $domain = parse_url($target, PHP_URL_HOST) ?: $target;
        $shortUrl = $base . '/r/' . $code;
        $lastHit = $r['last_hit_at'] ?? '';
        $created = $r['link_created_at'] ?? '';
        $hits = (int)($r['total_hits'] ?? 0);
        $unique = (int)($r['unique_ips'] ?? 0);
        $favicon = $domain ? ('https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=64') : '';
      ?>
      <div class="link-row">
        <div class="row-main">
          <div class="favicon">
            <?php if ($favicon): ?><img src="<?= h($favicon) ?>" alt=""><?php else: ?><span>🔗</span><?php endif; ?>
          </div>
          <div class="row-text">
            <div class="row-title">
              <span><?= h($label ?: $domain ?: $code) ?></span>
              <?php if ($label): ?><span class="badge">Link</span><?php endif; ?>
            </div>
            <div class="row-short">
              <a href="<?= h($shortUrl) ?>" target="_blank" rel="noopener"><?= h($shortUrl) ?></a>
              <button class="btn-pill" data-copy="<?= h($shortUrl) ?>">Copy</button>
              <a class="btn-pill" href="/link_visits.php?code=<?= urlencode($code) ?>">Visits</a>
            </div>
            <div class="row-long"><?= h($target) ?></div>
            <div class="row-meta">
              <span>💬 Engagements: <b><?= $hits ?></b></span>
              <span class="dot"></span>
              <span>Unique IPs: <b><?= $unique ?></b></span>
              <?php if ($lastHit): ?><span class="dot"></span><span>Last hit: <?= h($lastHit) ?></span><?php endif; ?>
              <?php if ($created): ?><span class="dot"></span><span>Created: <?= h($created) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="row-actions">
          <a class="btn-pill" href="<?= h($shortUrl) ?>" target="_blank" rel="noopener">Open</a>
          <a class="btn-pill" href="/link_edit.php?code=<?= urlencode($code) ?>">Edit</a>
          <button type="button" class="kebab menu-btn" aria-haspopup="true" aria-expanded="false">⋮</button>
          <div class="menu" data-menu>
            <a href="<?= h($shortUrl) ?>" target="_blank" rel="noopener">Open</a>
            <a href="/link_visits.php?code=<?= urlencode($code) ?>">Visits</a>
            <a href="/link_edit.php?code=<?= urlencode($code) ?>">Edit</a>
            <a href="<?= h($target) ?>" target="_blank" rel="noopener">Open target</a>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <?php if ($showCards && $linkRows): ?>
  <!-- Card list (optional via ?cards=1) -->
  <div id="stats-cards" style="margin-top:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px">
    <?php foreach ($linkRows as $r): ?>
      <div style="border:1px solid #e5e7eb;border-radius:10px;padding:12px;background:#fff">
        <div style="font-weight:600;margin-bottom:6px">Code: <code><?= h($r['code'] ?? '') ?></code></div>
        <div><b>Label:</b> <?= h($r['label'] ?? '') ?></div>
        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><b>Target:</b> <a class="link" href="<?= h($r['target_url'] ?? '') ?>" target="_blank" rel="noopener"><?= h($r['target_url'] ?? '') ?></a></div>
        <div style="display:flex;gap:12px;margin-top:6px">
          <span><b>Hits:</b> <?= (int)($r['total_hits'] ?? 0) ?></span>
          <span><b>Unique IPs:</b> <?= (int)($r['unique_ips'] ?? 0) ?></span>
        </div>
        <div style="margin-top:6px"><b>Last Hit:</b> <?= h($r['last_hit_at'] ?? '') ?></div>
        <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
          <?php $shortUrl = $base . '/r/' . ($r['code'] ?? ''); ?>
          <a class="link" href="<?= h($shortUrl) ?>" target="_blank">Open</a>
          <a class="link" href="/link_visits.php?code=<?= urlencode((string)($r['code'] ?? '')) ?>">Visits</a>
          <a class="link" href="/link_edit.php?code=<?= urlencode((string)($r['code'] ?? '')) ?>">Edit</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="pagination" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $pages; $p++):
      $query = $_GET; $query['page'] = $p; $url = '?' . http_build_query($query);
      if ($p == $page): ?>
        <span class="badge" style="padding:.25rem .6rem;border:1px solid #ddd;border-radius:8px;background:#fafafa"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= h($url) ?>" style="padding:.25rem .6rem;border:1px solid #ddd;border-radius:8px;text-decoration:none"><?= $p ?></a>
      <?php endif; endfor; ?>
  </div>

  <p style="margin-top:16px"><a href="/dashboard">Back</a></p>
  <p style="opacity:.7;font-size:12px">Note: on localhost, IPs may be local; country/city will appear once GeoIP is enabled in production.</p>

<script>
  (function(){
    function closeAll(){ document.querySelectorAll('.menu').forEach(m=>m.style.display='none'); }
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.menu-btn');
      if (btn) {
        // In the new Bitly-like list the menu lives beside the button inside `.row-actions`
        const wrap = btn.closest('.row-actions') || btn.parentElement;
        const menu = (wrap && wrap.querySelector('[data-menu]')) || btn.parentElement.querySelector('[data-menu]');
        if (!menu) return;
        const isOpen = menu.style.display === 'block';
        closeAll();
        if (!isOpen) {
          const r = btn.getBoundingClientRect();
          menu.style.position = 'fixed';
          // keep within viewport; menu is ~190px wide
          const left = Math.min(window.innerWidth - 12 - 190, Math.max(12, r.right - 170));
          menu.style.left = left + 'px';
          menu.style.top  = (r.bottom + 6) + 'px';
        }
        menu.style.display = isOpen ? 'none' : 'block';
        return;
      }
      if (!e.target.closest('.actions-cell')) closeAll();
    });
    window.addEventListener('resize', ()=>document.querySelectorAll('.menu').forEach(m=>m.style.display='none'));
    window.addEventListener('scroll', ()=>document.querySelectorAll('.menu').forEach(m=>m.style.display='none'), true);
    window.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeAll(); });
    // Quick date ranges for filters
    function fmt(d){const p=n=>String(n).padStart(2,'0');return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`}
    function applyRange(key){
      const form = document.querySelector('form.filters');
      if(!form) return;
      const f = form.querySelector('input[name="date_from"]');
      const t = form.querySelector('input[name="date_to"]');
      const now = new Date();
      let from = '', to = '';
      if (key === 'today') { from = to = fmt(now); }
      else if (key === '7') { const d=new Date(now); d.setDate(d.getDate()-6); from=fmt(d); to=fmt(now); }
      else if (key === '30') { const d=new Date(now); d.setDate(d.getDate()-29); from=fmt(d); to=fmt(now); }
      else if (key === 'month') { const d=new Date(now.getFullYear(), now.getMonth(), 1); from=fmt(d); to=fmt(now); }
      else if (key === 'clear') { from=''; to=''; }
      if (f) f.value = from; if (t) t.value = to;
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.quick-ranges [data-range]');
      if (btn) { e.preventDefault(); applyRange(btn.getAttribute('data-range')); }
    });
    // Copy short link
    document.addEventListener('click', function(e){
      const copyBtn = e.target.closest('[data-copy]');
      if(!copyBtn) return;
      const txt = copyBtn.getAttribute('data-copy') || '';
      if(!txt) return;
      navigator.clipboard.writeText(txt).then(()=>{
        copyBtn.textContent = 'Copied';
        setTimeout(()=>copyBtn.textContent='Copy', 1200);
      }).catch(()=>{
        // fallback
        const ta = document.createElement('textarea');
        ta.value = txt; document.body.appendChild(ta); ta.select();
        try{ document.execCommand('copy'); copyBtn.textContent='Copied'; setTimeout(()=>copyBtn.textContent='Copy',1200);}catch{}
        document.body.removeChild(ta);
      });
    });
  })();
</script>

</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>