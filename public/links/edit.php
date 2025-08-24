<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers_links.php';
require_once __DIR__ . '/../../includes/flash.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM links WHERE id=:id AND user_id=:u');
$st->execute([':id'=>$id, ':u'=>(int)($_SESSION['user_id'] ?? 0)]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if(!$row){ http_response_code(404); exit('Not found'); }

$page_title = 'Edit link';
include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid">
    <div class="container topbar--inset">
      <?php $breadcrumbs = [ ['label'=>'Dashboard','href'=>'/dashboard'], ['label'=>'Links','href'=>'/links'], ['label'=>'Edit','href'=>null] ]; $topbar=['search'=>['enabled'=>false]]; include __DIR__ . '/../partials/app_topbar.php'; ?>
    </div>
    <section class="maincol">
      <div class="panel"><div class="panel__body">
        <?php if ($f = flash_get('links')): ?>
          <div class="alert alert--<?= htmlspecialchars($f['t']) ?> u-mb-12"><i class="fi fi-rr-info" aria-hidden="true"></i><span><?= htmlspecialchars($f['m']) ?></span></div>
        <?php endif; ?>
        <div class="panel__title">Edit link</div>
        <form action="/links/save" method="post" class="form">
          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
          <div class="form__row"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" required></div>
          <div class="form__row"><label>Destination URL</label><input type="url" name="destination_url" value="<?= htmlspecialchars($row['destination_url'] ?? ($row['url'] ?? ($row['destination'] ?? ''))) ?>" required></div>
          <div class="form__row"><label>UTM (JSON)</label><textarea name="utm_json" rows="3"><?= htmlspecialchars($row['utm_json'] ?? '') ?></textarea></div>
          <div class="form__row"><label><input type="checkbox" name="is_active" value="1" <?= ((int)($row['is_active'] ?? 1)===1)?'checked':''; ?>> Active</label></div>
          <?php if (!empty($row['slug'])): ?><div class="form__row"><label>Short code</label><input type="text" value="<?= htmlspecialchars($row['slug']) ?>" readonly></div><?php endif; ?>
          <div class="u-mt-12"><button class="btn btn--primary" type="submit">Save</button> <a class="btn btn--ghost" href="/links">Cancel</a></div>
        </form>
      </div></div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>


