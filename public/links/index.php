<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers_links.php';
require_once __DIR__ . '/../../includes/flash.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$filters = wz_links_build_filters_from_query();

$where = 'user_id=:uid';
$params = [':uid'=>$uid];
if ($filters['active'] === '1') { $where .= ' AND is_active=1'; }
elseif ($filters['active'] === '0') { $where .= ' AND is_active=0'; }

// Detect destination URL column name for compatibility
$destCol = 'destination_url';
try {
  $chk = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination_url'");
  if ($chk && $chk->rowCount() === 0) {
    $alt = $pdo->query("SHOW COLUMNS FROM links LIKE 'url'");
    if ($alt && $alt->rowCount() > 0) $destCol = 'url';
    else {
      $alt2 = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination'");
      if ($alt2 && $alt2->rowCount() > 0) $destCol = 'destination';
    }
  }
} catch (Throwable $e) { /* ignore */ }

if ($filters['q'] !== '') { $where .= " AND (title LIKE :q OR $destCol LIKE :q)"; $params[':q'] = '%' . $filters['q'] . '%'; }

$count = $pdo->prepare("SELECT COUNT(*) FROM links WHERE $where");
foreach ($params as $k=>$v) $count->bindValue($k, $v);
$count->execute();
$totalRows = (int)$count->fetchColumn();

$off = ($filters['page']-1) * $filters['per'];
// Detect optional columns (clicks, slug) and link_clicks table
$hasClicksTable = false;
try { $t = $pdo->query("SHOW TABLES LIKE 'link_clicks'"); if ($t && $t->rowCount()>0) $hasClicksTable = true; } catch (Throwable $e) { $hasClicksTable = false; }

$clicksExpr = 'clicks';
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'clicks'"); if (!$c || $c->rowCount()===0) { $clicksExpr = $hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id = links.id)' : '0'; } } catch(Throwable $e) { $clicksExpr = $hasClicksTable ? '(SELECT COUNT(*) FROM link_clicks lc WHERE lc.link_id = links.id)' : '0'; }
$lastClickExpr = $hasClicksTable ? '(SELECT MAX(created_at) FROM link_clicks lc WHERE lc.link_id = links.id)' : 'NULL';

$slugExpr = 'slug';
try { $s = $pdo->query("SHOW COLUMNS FROM links LIKE 'slug'"); if (!$s || $s->rowCount()===0) { $slugExpr = 'NULL'; } } catch(Throwable $e) { $slugExpr = 'NULL'; }

$sql = "SELECT id, title, $destCol AS destination_url, $slugExpr AS slug, is_active, $clicksExpr AS clicks,
               $lastClickExpr AS last_click, created_at
        FROM links
        WHERE $where
        ORDER BY created_at DESC
        LIMIT :per OFFSET :off";
$st = $pdo->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k, $v);
$st->bindValue(':per', $filters['per'], PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

$page_title = 'Links';
include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid">
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [
          ['label' => 'Dashboard', 'href' => '/dashboard'],
          ['label' => 'Links',      'href' => null],
        ];
        $topbar = [ 'search' => [ 'enabled' => false ] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>
    <section class="maincol">
      <div class="panel"><div class="panel__body">
        <?php if ($f = flash_get('links')): ?>
          <div class="alert alert--<?= htmlspecialchars($f['t']) ?> u-mb-12">
            <i class="fi fi-rr-info" aria-hidden="true"></i>
            <span><?= htmlspecialchars($f['m']) ?></span>
          </div>
        <?php endif; ?>
        <div class="panel__title u-flex u-ai-center u-jc-between"><span>Links</span><a class="btn btn--primary" href="/links/new.php">Create link</a></div>
        <form class="u-mb-12" method="get" action="/links"><input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search links…"></form>
        <div class="qr-table" id="linksTable">
          <div class="table-wrapper">
            <table class="table" role="table">
              <thead><tr><th>Title</th><th>Destination</th><th>Stats</th><th>Created</th><th>Actions</th></tr></thead>
              <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="u-ta-center u-text-muted">No links found.</td></tr>
              <?php else: foreach ($rows as $r): $host = preg_replace('~^https?://~i','',$r['destination_url']); ?>
                <tr class="link-row" data-id="<?= (int)$r['id'] ?>" data-q="<?= htmlspecialchars(($r['title'].' '.$r['destination_url']), ENT_QUOTES) ?>" data-active="<?= (int)$r['is_active'] ?>">
                  <td><span class="badge">LINK</span> <?= htmlspecialchars($r['title']) ?></td>
                  <td><a class="qr-link" href="<?= htmlspecialchars($r['destination_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($host) ?></a></td>
                  <td class="u-text-muted">
                    <?= number_format((int)$r['clicks']) ?> clicks
                    <?php if (!empty($r['last_click'])): ?>
                      • last <?= date('Y-m-d', strtotime((string)$r['last_click'])) ?>
                    <?php endif; ?>
                  </td>
                  <td class="u-text-muted"><?= date('Y-m-d', strtotime((string)$r['created_at'])) ?></td>
                  <td>
                    <a class="btn btn--ghost btn--sm" href="/links/view.php?id=<?= (int)$r['id'] ?>">View</a>
                    <?php if (!empty($r['slug'])): ?>
                      <button class="btn btn--ghost btn--sm" data-action="copy-link" data-slug="<?= htmlspecialchars($r['slug']) ?>">Copy short link</button>
                    <?php endif; ?>
                    <a class="btn btn--ghost btn--sm" href="/links/edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                    <form method="post" action="/links/delete.php" style="display:inline" onsubmit="return confirm('Delete this link?')">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if ($totalRows > $filters['per']): $totalPages = (int)ceil($totalRows/$filters['per']); ?>
          <div class="pagination-wrapper u-mt-16">
            <div class="pagination">
              <?php if ($filters['page']>1): ?><a class="pagination__link" href="?<?= http_build_query(array_merge($_GET,['page'=>$filters['page']-1])) ?>">&laquo; Prev</a><?php endif; ?>
              <?php for($i=max(1,$filters['page']-2);$i<=min($totalPages,$filters['page']+2);$i++): ?>
                <a class="pagination__link <?= $i===$filters['page']?'is-active':'' ?>" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a>
              <?php endfor; ?>
              <?php if ($filters['page']<$totalPages): ?><a class="pagination__link" href="?<?= http_build_query(array_merge($_GET,['page'=>$filters['page']+1])) ?>">Next &raquo;</a><?php endif; ?>
            </div>
            <div class="pagination-info">Showing <?= number_format($off+1) ?>–<?= number_format(min($off+$filters['per'],$totalRows)) ?> of <?= number_format($totalRows) ?> links</div>
          </div>
        <?php endif; ?>
      </div></div>
    </section>
  </div>
</main>
<script src="/assets/js/links-list.js" defer></script>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


