<?php
require __DIR__ . '/../../includes/admin_auth.php';
require __DIR__ . '/../../includes/admin_header.php';
ini_set('display_errors',1); error_reporting(E_ALL);

// Quick stats
$users = (int)$db->pdo()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$links = (int)$db->pdo()->query("SELECT COUNT(*) FROM short_links")->fetchColumn();
$scans = (int)$db->pdo()->query("SELECT COUNT(*) FROM scans")->fetchColumn();

// Latest 10 users
$latestUsers = $db->pdo()->query("SELECT id,name,email,created_at FROM users ORDER BY id DESC LIMIT 10")->fetchAll();

// Recent activity (try/catch لو الجدول مش موجود)
$recentLogs = [];
try {
  $recentLogs = $db->pdo()->query("
    SELECT id, admin_id, action, target_id, details, created_at
    FROM admin_logs
    ORDER BY id DESC
    LIMIT 10
  ")->fetchAll();
} catch (Throwable $e) { /* ignore */ }
?>

<h2 class="mb-3">Admin Dashboard</h2>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div><div class="text-muted">Users</div><div class="display-6 fw-bold"><?= $users ?></div></div>
          <i class="bi bi-people fs-1 text-secondary"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div><div class="text-muted">Short Links</div><div class="display-6 fw-bold"><?= $links ?></div></div>
          <i class="bi bi-link-45deg fs-1 text-secondary"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div><div class="text-muted">Total Scans</div><div class="display-6 fw-bold"><?= $scans ?></div></div>
          <i class="bi bi-upc-scan fs-1 text-secondary"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex align-items-center justify-content-between">
    <h5 class="mb-0">Latest Users</h5>
    <a href="/admin/users.php" class="btn btn-sm btn-outline-secondary">View all</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr><th style="width:80px">ID</th><th>Name</th><th>Email</th><th style="width:220px">Joined</th></tr>
        </thead>
        <tbody>
          <?php if (!$latestUsers): ?>
            <tr><td colspan="4" class="text-center py-4">No users yet.</td></tr>
          <?php else: foreach ($latestUsers as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['created_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($recentLogs): ?>
  <div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
      <h5 class="mb-0">Recent Activity</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr><th style="width:80px">#</th><th>Action</th><th>Target</th><th>By (Admin ID)</th><th style="width:220px">Time</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentLogs as $lg): ?>
              <tr>
                <td><?= (int)$lg['id'] ?></td>
                <td><code><?= htmlspecialchars($lg['action']) ?></code></td>
                <td><?= htmlspecialchars((string)$lg['target_id']) ?></td>
                <td><?= htmlspecialchars((string)$lg['admin_id']) ?></td>
                <td><?= htmlspecialchars($lg['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>