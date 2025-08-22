<?php
declare(strict_types=1);

/**
 * go.php — Minimal redirect handler for short links
 * Usage: /go.php?l={id}
 * - Resolves destination from links table
 * - Logs analytics event (click)
 * - Redirects (302)
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/events.php';

$id = isset($_GET['l']) ? (int)$_GET['l'] : 0;
if ($id <= 0) { http_response_code(400); exit('Bad request'); }

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo instanceof PDO) { http_response_code(500); exit('DB unavailable'); }

// Try to resolve destination from common column names
$stmt = $pdo->prepare("SELECT * FROM links WHERE id = :id LIMIT 1");
try {
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  http_response_code(404); exit('Not found');
}
if (!$row) { http_response_code(404); exit('Not found'); }

$dest = $row['destination'] ?? $row['url'] ?? $row['target_url'] ?? null;
if (!$dest) { http_response_code(500); exit('No destination'); }

// Non-blocking analytics log (skip bots)
try {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $ua  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
  if ($uid > 0 && $ua && !preg_match('/bot|spider/i', $ua)) {
    $slug = $row['slug'] ?? $row['code'] ?? '';
    $label = (string)($row['title'] ?? $row['name'] ?? ($slug ? ('os.me/'.ltrim($slug,'/')) : ''));
    wz_log_event($pdo, $uid, 'link', $id, 'click', $label);
  }
} catch (Throwable $e) {}

header('Location: ' . $dest, true, 302);
exit;


