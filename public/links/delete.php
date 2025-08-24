<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method not allowed'; exit; }
$uid = (int)($_SESSION['user_id'] ?? 0);
$id  = (int)($_POST['id'] ?? 0);

try {
  $st = $pdo->prepare('DELETE FROM links WHERE id=:id AND user_id=:u');
  $st->execute([':id'=>$id, ':u'=>$uid]);
} catch (Throwable $e) {}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); exit; }
flash_set('links','Link deleted','success');
header('Location: /links');
exit;


