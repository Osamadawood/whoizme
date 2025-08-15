<?php require_once __DIR__ . "/../_bootstrap.php"; ?>
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/auth.php';

$uid = current_user_id();

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = max(1, min(50, (int)($_GET['per'] ?? 20)));
$off  = ($page - 1) * $per;

$where = 'user_id = :uid';
$params = [':uid' => $uid];

if ($q !== '') {
    $where .= ' AND (title LIKE :kw OR payload LIKE :kw OR code LIKE :kw)';
    $params[':kw'] = '%' . $q . '%';
}

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE {$where}")
                  ->execute($params) ?: 0;
$total = (int)$pdo->query("SELECT COUNT(*) FROM qr_codes WHERE {$where}")
                  ->fetchColumn();

$sql = "SELECT id, code, type, title, payload, is_active, created_at
        FROM qr_codes
        WHERE {$where}
        ORDER BY created_at DESC
        LIMIT :per OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $off, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>My QR Codes</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="with-user-sidebar">
<?php /* TODO: include user header/sidebar لو موجودين */ ?>
<main class="container">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="h4 m-0">My QR Codes</h2>
    <div class="d-flex gap-2">
      <form method="get" class="d-flex" role="search">
        <input type="text" name="q" class="form-control" placeholder="Search name or URL" value="<?= htmlspecialchars($q) ?>">
        <button class="btn btn-primary ms-2">Apply</button>
      </form>
      <a class="btn btn-success" href="/qr/new.php">+ New</a>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="card p-4">No QR codes yet. Click “New”.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Type</th>
          <th>Payload</th>
          <th>Created</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['title']) ?></td>
          <td><?= htmlspecialchars($r['type']) ?></td>
          <td style="max-width:460px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <?= htmlspecialchars($r['payload']) ?>
          </td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/qr/view.php?id=<?= (int)$r['id'] ?>">Open</a>
            <a class="btn btn-sm btn-outline-secondary" href="/qr/new.php?id=<?= (int)$r['id'] ?>">Edit</a>
            <a class="btn btn-sm btn-outline-danger" href="/qr/delete.php?id=<?= (int)$r['id'] ?>"
               onclick="return confirm('Delete this QR?');">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</main>
</body>
</html>