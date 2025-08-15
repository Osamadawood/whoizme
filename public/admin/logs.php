<?php require __DIR__ . "/../_bootstrap.php"; ?>
<?php
require __DIR__ . '/../../includes/admin_auth.php';
require __DIR__ . '/../../includes/admin_header.php';

// CSRF (للتصدير لو استخدمناه POST لاحقًا)
if (empty($_SESSION['csrf_admin'])) {
  $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_admin'];

// صلاحيات (الكل يقرأ، بس السوبر/مَنجر يقدروا يعملوا تصدير مثلاً)
$role = $_SESSION['admin_role'] ?? 'super';
$can_export = in_array($role, ['manager','super','editor','viewer']); // الكل يسمح بالـ CSV هنا

// فلاتر
$admin_q   = trim((string)($_GET['admin'] ?? ''));  // id أو جزء من الاسم/الإيميل
$action_q  = trim((string)($_GET['action'] ?? '')); // user_create, user_update, user_delete, user_activate, user_deactivate, bulk_*, impersonate_start ...
$target_q  = trim((string)($_GET['target'] ?? '')); // user id
$date_from = trim((string)($_GET['from'] ?? ''));   // YYYY-MM-DD
$date_to   = trim((string)($_GET['to'] ?? ''));     // YYYY-MM-DD

$where = [];
$params = [];

// فلتر الأكشن
if ($action_q !== '') {
  $where[] = "L.action = ?";
  $params[] = $action_q;
}
// فلتر الهدف
if ($target_q !== '' && ctype_digit($target_q)) {
  $where[] = "L.target_id = ?";
  $params[] = (int)$target_q;
}
// فلتر الأدمن: اسم/إيميل/ID
if ($admin_q !== '') {
  if (ctype_digit($admin_q)) {
    $where[] = "L.admin_id = ?";
    $params[] = (int)$admin_q;
  } else {
    $where[] = "(A.name LIKE ? OR A.email LIKE ?)";
    $params[] = "%{$admin_q}%";
    $params[] = "%{$admin_q}%";
  }
}
// فلتر التاريخ
if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
  $where[] = "DATE(L.created_at) >= ?";
  $params[] = $date_from;
}
if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
  $where[] = "DATE(L.created_at) <= ?";
  $params[] = $date_to;
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// تصدير CSV
if ($can_export && isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="admin_logs.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID','Admin ID','Admin Name','Admin Email','Action','Target ID','Details','At']);

  $csv = $db->pdo()->prepare("
    SELECT L.id, L.admin_id, A.name AS admin_name, A.email AS admin_email,
           L.action, L.target_id, L.details, L.created_at
    FROM admin_logs L
    LEFT JOIN admins A ON A.id = L.admin_id
    $sqlWhere
    ORDER BY L.id DESC
  ");
  $csv->execute($params);
  while ($r = $csv->fetch()) {
    fputcsv($out, [
      (int)$r['id'],
      (int)$r['admin_id'],
      (string)$r['admin_name'],
      (string)$r['admin_email'],
      (string)$r['action'],
      isset($r['target_id']) ? (int)$r['target_id'] : null,
      (string)$r['details'],
      (string)$r['created_at'],
    ]);
  }
  fclose($out);
  exit;
}

// إجمالي
$countStmt = $db->pdo()->prepare("
  SELECT COUNT(*) FROM admin_logs L
  LEFT JOIN admins A ON A.id = L.admin_id
  $sqlWhere
");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// Pagination
$pageSize = 30;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $pageSize;

// قائمة
$stmt = $db->pdo()->prepare("
  SELECT L.id, L.admin_id, A.name AS admin_name, A.email AS admin_email,
         L.action, L.target_id, L.details, L.created_at
  FROM admin_logs L
  LEFT JOIN admins A ON A.id = L.admin_id
  $sqlWhere
  ORDER BY L.id DESC
  LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) {
  // positional لباراميترات الشروط فقط
  $stmt->bindValue($k+1, $v);
}
$stmt->bindValue(':limit', (int)$pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// أكشنات متاحة كاختيارات
$actions = [
  '' => 'Any action',
  'user_create' => 'user_create',
  'user_update' => 'user_update',
  'user_delete' => 'user_delete',
  'user_activate' => 'user_activate',
  'user_deactivate' => 'user_deactivate',
  'bulk_activate' => 'bulk_activate',
  'bulk_deactivate' => 'bulk_deactivate',
  'impersonate_start' => 'impersonate_start',
];
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="mb-0">Admin Logs</h2>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="/admin/users.php">← Back to Users</a>
    <a class="btn btn-outline-primary" href="?<?= http_build_query(array_filter([
      'admin'=>$admin_q ?: null,
      'action'=>$action_q ?: null,
      'target'=>$target_q ?: null,
      'from'=>$date_from ?: null,
      'to'=>$date_to ?: null,
      'export'=>'csv'
    ])) ?>">Export CSV</a>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-12 col-md-3">
        <label class="form-label">Admin (id/name/email)</label>
        <input type="text" class="form-control" name="admin" value="<?= htmlspecialchars($admin_q) ?>">
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label">Action</label>
        <select name="action" class="form-select">
          <?php foreach ($actions as $val=>$label): ?>
            <option value="<?= htmlspecialchars($val) ?>" <?= $action_q===$val?'selected':'' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Target ID</label>
        <input type="text" class="form-control" name="target" value="<?= htmlspecialchars($target_q) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($date_from) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($date_to) ?>">
      </div>
      <div class="col-12">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:80px">ID</th>
            <th style="width:140px">When</th>
            <th style="width:120px">Admin ID</th>
            <th>Admin</th>
            <th style="width:180px">Action</th>
            <th style="width:100px">Target</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center py-4">No logs.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td><?= (int)$r['admin_id'] ?></td>
              <td><?= htmlspecialchars(trim(($r['admin_name'] ?? '').' <'.$r['admin_email'].'>')) ?></td>
              <td><span class="badge text-bg-secondary"><?= htmlspecialchars($r['action']) ?></span></td>
              <td><?= isset($r['target_id']) ? (int)$r['target_id'] : '' ?></td>
              <td><?= nl2br(htmlspecialchars((string)$r['details'])) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer text-muted small">
    <?php
      $pages = max(1, (int)ceil($total / $pageSize));
      $mk = function($p) use ($admin_q,$action_q,$target_q,$date_from,$date_to) {
        $q = [];
        if ($admin_q!=='') $q['admin']=$admin_q;
        if ($action_q!=='') $q['action']=$action_q;
        if ($target_q!=='') $q['target']=$target_q;
        if ($date_from!=='') $q['from']=$date_from;
        if ($date_to!=='') $q['to']=$date_to;
        $q['p']=$p;
        return '?'.http_build_query($q);
      };
    ?>
    <div class="d-flex justify-content-between align-items-center">
      <div>Total: <?= (int)$total ?></div>
      <?php if ($pages > 1): ?>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $mk(max(1,$page-1)) ?>">Prev</a></li>
          <?php for ($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
            <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= $mk($i) ?>"><?= $i ?></a></li>
          <?php endfor; ?>
          <li class="page-item <?= $page>=$pages?'disabled':'' ?>"><a class="page-link" href="<?= $mk(min($pages,$page+1)) ?>">Next</a></li>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>