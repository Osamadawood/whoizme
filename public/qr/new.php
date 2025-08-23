<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? 0);
$prefill = trim($_GET['prefill_url'] ?? '');

$record = [
  'title'   => '',
  'type'    => 'url',
  'payload' => $prefill,
  'code'    => '',
  'is_active' => 1,
];

if ($id) {
  $st = $pdo->prepare("SELECT * FROM qr_codes WHERE id=:id AND user_id=:uid");
  $st->execute([':id'=>$id, ':uid'=>$uid]);
  $row = $st->fetch();
  if ($row) $record = $row;
}

$page      = 'qr';              // used by sidebar to set active item
$page_slug = 'qr';              // secondary safety for older partials
$page_title = $id ? 'Edit QR' : 'Create new QR';

include __DIR__ . '/../partials/app_header.php';
?>
<main class="dashboard">
  <?php include __DIR__ . '/../partials/app_sidebar.php'; ?>
  <div class="container dash-grid" role="region" aria-label="QR editor">
    <div class="container topbar--inset">
      <?php
        $breadcrumbs = [
          ['label' => 'Dashboard', 'href' => '/dashboard'],
          ['label' => 'QR Codes',  'href' => '/qr'],
          ['label' => $page_title, 'href' => null],
        ];
        $topbar = [ 'search' => [ 'enabled' => false ] ];
        include __DIR__ . '/../partials/app_topbar.php';
      ?>
    </div>
  <section class="maincol">
  <div class="panel"><div class="panel__body">
  <h3 class="h3 u-mt-0"><?= htmlspecialchars($page_title) ?></h3>
  <form action="/qr/save.php" method="post" class="u-stack-16" style="max-width:720px">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div>
      <label class="label">Title</label>
      <input name="title" class="input" required
             value="<?= htmlspecialchars($record['title']) ?>">
    </div>
    <div>
      <label class="label">Type</label>
      <select name="type" class="input">
        <?php foreach (['url','vcard','text'] as $t): ?>
        <option value="<?= $t ?>" <?= $record['type']===$t?'selected':'' ?>><?= strtoupper($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="label">Payload</label>
      <textarea name="payload" class="input" rows="5" placeholder="https://example.com or raw text" required><?= htmlspecialchars($record['payload']) ?></textarea>
    </div>
    <div class="u-flex u-gap-8">
      <button class="btn btn--primary"><?= $id ? 'Save changes' : 'Create' ?></button>
      <a class="btn btn--ghost" href="/qr" role="button">Back</a>
    </div>
  </form>
  </div></div>
  </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/app_footer.php'; ?>