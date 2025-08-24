<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events.php';

$slug = trim($_GET['c'] ?? '');
if ($slug === '') { http_response_code(404); echo 'Not found'; exit; }

$st = $pdo->prepare('SELECT id, destination_url, is_active FROM links WHERE slug=:c LIMIT 1');
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

header('Location: ' . $row['destination_url'], true, 302);
exit;


