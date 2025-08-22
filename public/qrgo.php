<?php
declare(strict_types=1);

/**
 * qrgo.php — Minimal QR redirect handler
 * Usage: /qrgo.php?q={id}
 * - Resolves QR target from qr_codes
 * - Logs analytics event (scan)
 * - Redirects (302)
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events.php';

$id = isset($_GET['q']) ? (int)$_GET['q'] : 0;
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) { http_response_code(500); exit('DB unavailable'); }

$stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE id = :id LIMIT 1");
try {
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  http_response_code(404); exit('Not found');
}
if (!$row) { http_response_code(404); exit('Not found'); }

$dest = $row['target_url'] ?? $row['payload'] ?? $row['url'] ?? null;
if (!$dest) { http_response_code(500); exit('No destination'); }

try {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $ua  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
  if ($uid > 0 && $ua && !preg_match('/bot|spider/i', $ua)) {
    $label = (string)($row['title'] ?? $row['name'] ?? 'demo qr');
    wz_log_event($pdo, $uid, 'qr', $id, 'scan', $label);
  }
} catch (Throwable $e) {}

header('Location: ' . $dest, true, 302);
exit;


