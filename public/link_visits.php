<?php
// Visits page for a specific short link code (list + filters + pagination + CSV)
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
function normDate($s){
  if (!$s) return '';
  $s = trim((string)$s);
  if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
    return $m[3] . '-' . $m[2] . '-' . $m[1];
  }
  return $s;
}

$code      = gp('code', '');
if ($code === '') { header('Location: /link_stats.php'); exit; }

$q         = gp('q', '');        // search in ip/ua/ref
$dateFrom  = gp('date_from', '');
$dateTo    = gp('date_to', '');
$dateFromN = normDate($dateFrom);
$dateToN   = normDate($dateTo);
$page      = max(1, (int)gp('page', 1));
$perPage   = min(200, max(1, (int)gp('per_page', 50)));
$export    = (int)gp('export', 0);

// Ensure the code belongs to current user
$sl = $pdo->prepare("SELECT id, code, label, target_url, created_at FROM short_links WHERE code = :c AND user_id = :uid LIMIT 1");
$sl->execute([':c'=>$code, ':uid'=>$uid]);
$link = $sl->fetch(PDO::FETCH_ASSOC);
if (!$link) {
  include __DIR__ . '/../includes/header.php';
  echo "<main class='container' style='padding:24px 0'><h2>Visits</h2><p>Link not found or not owned by you.</p><p><a href='/link_stats.php'>Back to Statistics</a></p></main>";
  include __DIR__ . '/../includes/footer.php';
  exit;
}

// WHERE
$where = ["code = :code"];
$params = [':code' => $code];

if ($q !== '') {
  $where[] = "(ip LIKE :q1 OR ua LIKE :q2 OR ref LIKE :q3)";
  $params[':q1'] = "%$q%";
  $params[':q2'] = "%$q%";
  $params[':q3'] = "%$q%";
}
if ($dateFromN !== '') { $where[] = "created_at >= :date_from"; $params[':date_from'] = $dateFromN . ' 00:00:00'; }
if ($dateToN   !== '') { $where[] = "created_at <= :date_to";   $params[':date_to']   = $dateToN   . ' 23:59:59'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$c = $pdo->prepare("SELECT COUNT(*) FROM short_link_hits $whereSql");
$c->execute($params);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Main
$sql = "SELECT id, created_at, ip, ua, ref FROM short_link_hits
        $whereSql
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$st = $pdo->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k, $v);
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$visits = $st->fetchAll(PDO::FETCH_ASSOC);

// Export CSV (current page)
if ($export === 1) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="visits_'.$code.'_page_'.$page.'.csv"');
  $out = fopen('php://output', 'w');
  fprintf($out, "\xEF\xBB\xBF");
  fputcsv($out, ['Time','IP','User Agent','Referrer']);
  foreach ($visits as $v) {
    fputcsv($out, [
      $v['created_at'],
      $v['ip'],
      $v['ua'],
      $v['ref']
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
?>
<style>
  .v-wrap{--b:#e5e7eb;--muted:#6b7280}
  .v-wrap .filters input, .v-wrap .filters button, .v-wrap .filters a{
    font:inherit; padding:8px 10px; border:1px solid var(--b); border-radius:8px; background:#fff;
  }
  .v-wrap .filters button{background:$brand;color:#fff;border-color:$brand;cursor:pointer}
  .v-wrap table{width:100%;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid var(--b);border-radius:10px;overflow:hidden}
  .v-wrap thead th{background:#f3f4f6;text-align:left;padding:10px 12px;border-bottom:1px solid var(--b);font-weight:600}
  .v-wrap tbody td{padding:10px 12px;border-top:1px solid var(--b);vertical-align:top}
  .muted{color:var(--muted);font-size:13px}
  @media (max-width: 860px){
    .v-wrap .filters{grid-template-columns:repeat(2, minmax(0,1fr))}
    .v-wrap table{font-size:14px}
  }
  .truncate{max-width:520px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .link { color:$brand; text-decoration:none; }
  .link:hover { text-decoration:underline; }
  .v-wrap table tbody tr:nth-child(even){ background:#f9fafb; }
  .v-wrap table tbody tr:hover{ background:#f1f5f9; }
  .v-wrap thead th { position: sticky; top: 0; z-index: 2; }
</style>

<main class="container v-wrap" style="padding:16px 0">
  <h2>Visits — <code><?= h($link['code']) ?></code></h2>
  <p class="muted" style="margin:6px 0 12px">Label: <b><?= h($link['label']) ?></b> — <a href="<?= h($link['target_url']) ?>" target="_blank" rel="noopener"><?= h($link['target_url']) ?></a></p>

  <form method="get" class="filters" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;align-items:end;background:#f6f7f9;padding:12px 16px;border-radius:12px">
    <input type="hidden" name="code" value="<?= h($code) ?>">
    <div>
      <label>Search</label>
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="IP / UA / Referrer">
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
      <input type="number" name="per_page" min="1" max="200" value="<?= (int)$perPage ?>">
    </div>
    <div style="grid-column:1/-1;display:flex;gap:8px;margin-top:6px;align-items:center">
      <button type="submit">Apply filters</button>
      <a href="/link_visits.php?code=<?= urlencode($code) ?>" class="link">Clear</a>
      <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>" class="link">Export CSV (current page)</a>
      <a href="/link_stats.php" class="link">Back to Statistics</a>
    </div>
  </form>

  <p class="muted" style="margin-top:6px">Rows: <b><?= count($visits) ?></b> — Total: <b><?= (int)$total ?></b> — Page <?= (int)$page ?> / <?= (int)$pages ?></p>

  <div style="overflow:auto;margin-top:8px">
    <table>
      <thead>
        <tr>
          <th style="padding:8px;border:1px solid var(--b)">Time</th>
          <th style="padding:8px;border:1px solid var(--b)">IP</th>
          <th style="padding:8px;border:1px solid var(--b)">User Agent</th>
          <th style="padding:8px;border:1px solid var(--b)">Referrer</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$visits): ?>
          <tr><td colspan="4" style="padding:12px;border:1px solid var(--b);text-align:center;color:#666">No visits match the current filters.</td></tr>
        <?php else: foreach ($visits as $v): ?>
          <tr>
            <td style="padding:8px;border:1px solid var(--b)"><?= h($v['created_at']) ?></td>
            <td style="padding:8px;border:1px solid var(--b)"><?= h($v['ip']) ?></td>
            <td class="truncate" style="padding:8px;border:1px solid var(--b)"><?= h($v['ua']) ?></td>
            <td class="truncate" style="padding:8px;border:1px solid var(--b)"><?= h($v['ref']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $pages; $p++):
      $query = $_GET; $query['page'] = $p; $url = '?' . http_build_query($query);
      if ($p == $page): ?>
        <span style="padding:.35rem .65rem;border:1px solid var(--b);border-radius:8px;background:#f3f4f6"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= h($url) ?>" style="padding:.35rem .65rem;border:1px solid var(--b);border-radius:8px;text-decoration:none"><?= $p ?></a>
      <?php endif; endfor; ?>
  </div>

  <div style="margin-top:16px; display:flex; gap:12px;">
    <a href="/link_stats.php" class="link">Back to Statistics</a>
    <a href="/" class="link">Back to Dashboard</a>
  </div>

</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>