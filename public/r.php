<?php require_once __DIR__ . '/../_bootstrap.php'; ?>
<?php
// public/r.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../includes/bootstrap.php';

/**
 * Helper: extract short code from GET or from REQUEST_URI (fallback if rewrite didn't pass ?c=)
 */
function extract_code(): string {
  if (!empty($_GET['c'])) {
    return trim($_GET['c']);
  }
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  // match /r/XXXX (before ? or end)
  if (preg_match('#/r/([^/?#]+)#i', $uri, $m)) {
    return trim($m[1]);
  }
  return '';
}

/**
 * Helper: detect if payload should be left as-is (mailto, tel, WIFI, vCard, etc.)
 */
function is_special_payload(string $p): bool {
  return (bool)preg_match('#^(mailto:|tel:|sms:|geo:|WIFI:|BEGIN:VCARD|MATMSG:)#i', $p);
}

/**
 * Helper: is likely a bare URL without scheme
 */
function is_bare_url(string $p): bool {
  // starts with www. or domain.tld/... without a scheme
  if (preg_match('#^(?:www\\.)?[a-z0-9.-]+\\.[a-z]{2,}(/.*)?$#i', $p)) return true;
  return false;
}

/**
 * Helper: ensure URL has http/https
 */
function normalize_url(string $p): string {
  if (preg_match('#^https?://#i', $p)) return $p;
  if (is_bare_url($p)) return 'https://' . $p;
  return $p;
}

/**
 * Helper: choose app store URL based on user agent
 * Payload is expected to be two lines: iOS on first, Android on second (but we’ll be lenient).
 */
function pick_app_store_target(string $payload): string {
  $lines = preg_split('/\\r?\\n/', trim($payload));
  $ios = $lines[0] ?? '';
  $and = $lines[1] ?? '';

  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $isIOS = (bool)preg_match('/(iPhone|iPad|iPod|iOS|Macintosh;.*(FxiOS|CriOS))/i', $ua);
  $isAndroid = (bool)preg_match('/Android/i', $ua);

  if ($isIOS && $ios) return $ios;
  if ($isAndroid && $and) return $and;

  // fallback: prefer iOS if present, else Android, else first non-empty
  if ($ios) return $ios;
  if ($and) return $and;
  foreach ($lines as $l) { if (trim($l) !== '') return $l; }
  return '';
}

$code = extract_code();
if (!$code) {
  http_response_code(404);
  echo "Not found (missing code)"; exit;
}

// Fetch by short_code
$stmt = $pdo->prepare("SELECT id, type, payload FROM qr_codes WHERE short_code = ? LIMIT 1");
$stmt->execute([$code]);
$row = $stmt->fetch();

if (!$row) {
  http_response_code(404);
  echo "Not found (unknown code)"; exit;
}

// Determine target
$type   = strtolower($row['type'] ?? 'url');
$target = $row['payload'] ?? '';

if ($type === 'app') {
  $target = pick_app_store_target($target);
}

if (!is_special_payload($target)) {
  $target = normalize_url($target);
}

if ($target === '' ) {
  http_response_code(404);
  echo "Not found (empty target)"; exit;
}

// Record scan (best effort; do not block redirect on errors)
try {
  $qr_id = (int)$row['id'];
  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $ip = null;
  if (!empty($_SERVER['REMOTE_ADDR'])) {
    $bin = @inet_pton($_SERVER['REMOTE_ADDR']);
    if ($bin !== false) $ip = $bin; // VARBINARY(16)
  }
  $ins = $pdo->prepare("INSERT INTO qr_scans (qr_id, ip, ua) VALUES (?, ?, ?)");
  $ins->bindValue(1, $qr_id, PDO::PARAM_INT);
  $ins->bindValue(2, $ip, PDO::PARAM_LOB);
  $ins->bindValue(3, $ua, PDO::PARAM_STR);
  $ins->execute();
} catch (\Throwable $e) {
  // ignore logging errors
}

// Debug mode: show info instead of redirect
if (!empty($_GET['debug'])) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "DEBUG r.php\n";
  echo "Short code: {$code}\n";
  echo "Type      : {$type}\n";
  echo "Target    : {$target}\n";
  echo "User-Agent: ".($_SERVER['HTTP_USER_AGENT'] ?? '')."\n";
  exit;
}

// Redirect
// Basic protection against header injection
$target = str_replace(["\r","\n"], '', $target);
header("Location: ".$target, true, 302);
exit;