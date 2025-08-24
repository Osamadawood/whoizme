<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events.php';

$slug = trim($_GET['c'] ?? '');
if ($slug === '') { http_response_code(404); echo 'Not found'; exit; }

// Detect destination column for compatibility
$destCol = 'destination_url';
try {
    $chk = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination_url'");
    if ($chk && $chk->rowCount() === 0) {
        $alt = $pdo->query("SHOW COLUMNS FROM links LIKE 'url'");
        if ($alt && $alt->rowCount() > 0) { $destCol = 'url'; }
        else {
            $alt2 = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination'");
            if ($alt2 && $alt2->rowCount() > 0) { $destCol = 'destination'; }
        }
    }
} catch (Throwable $e) { /* ignore */ }

$st = $pdo->prepare("SELECT id, $destCol AS dest, is_active FROM links WHERE slug=:c LIMIT 1");
$st->execute([':c'=>$slug]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row || (int)$row['is_active'] !== 1) { http_response_code(404); echo 'Not found'; exit; }

try {
  $pdo->beginTransaction();
  // Best-effort log
  wz_log_link_click($pdo, (int)$row['id'], $_SESSION['user_id'] ?? null);
  $upd = $pdo->prepare('UPDATE links SET clicks=clicks+1, last_click_at=NOW() WHERE id=:id');
  $upd->execute([':id'=>$row['id']]);
  $pdo->commit();
} catch(Throwable $e) { $pdo->rollBack(); }

header('Location: ' . $row['dest'], true, 302);
exit;


