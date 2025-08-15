<?php require_once __DIR__ . "/../_bootstrap.php"; ?>
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';

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
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= $id ? 'Edit QR' : 'New QR' ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="with-user-sidebar">
<main class="container">
  <h2 class="h4 mb-3"><?= $id ? 'Edit QR' : 'Create new QR' ?></h2>
  <form action="/qr/save.php" method="post" class="vstack gap-3" style="max-width:720px">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div>
      <label class="form-label">Title</label>
      <input name="title" class="form-control" required
             value="<?= htmlspecialchars($record['title']) ?>">
    </div>
    <div>
      <label class="form-label">Type</label>
      <select name="type" class="form-select">
        <?php foreach (['url','vcard','text'] as $t): ?>
        <option value="<?= $t ?>" <?= $record['type']===$t?'selected':'' ?>><?= strtoupper($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label">Payload</label>
      <textarea name="payload" class="form-control" rows="4" placeholder="https://example.com or raw text" required><?= htmlspecialchars($record['payload']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary"><?= $id ? 'Save changes' : 'Create' ?></button>
      <a class="btn btn-light" href="/qr/">Back</a>
    </div>
  </form>
</main>
</body>
</html>