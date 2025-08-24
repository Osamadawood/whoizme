<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$id  = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM links WHERE id=:id AND user_id=:u');
$st->execute([':id'=>$id, ':u'=>$uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if(!$row){ http_response_code(404); exit('Not found'); }

$page_title = 'Link details';
include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid">
    <div class="container topbar--inset">
      <?php $breadcrumbs=[['label'=>'Dashboard','href'=>'/dashboard'],['label'=>'Links','href'=>'/links'],['label'=>'Details','href'=>null]]; $topbar=['search'=>['enabled'=>false]]; include __DIR__ . '/../partials/app_topbar.php'; ?>
    </div>
    <section class="maincol">
      <div class="panel"><div class="panel__body">
        <div class="panel__title"><?= htmlspecialchars($row['title']) ?></div>
        <p><strong>URL:</strong> <a class="qr-link" href="<?= htmlspecialchars($row['destination_url'] ?? ($row['url'] ?? ($row['destination'] ?? ''))) ?>" target="_blank" rel="noopener">Open</a></p>
        <p><strong>Short link:</strong> <?= !empty($row['slug']) ? (htmlspecialchars($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/lgo.php?c='.$row['slug'])) : '—' ?></p>
        <p><strong>Clicks:</strong> <?= (int)($row['clicks'] ?? 0) ?></p>
      </div></div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


