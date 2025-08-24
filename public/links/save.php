<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers_links.php';
require_once __DIR__ . '/../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(405); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }
  flash_set('links','Method not allowed','error'); header('Location: /links'); exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$id  = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$url   = wz_link_normalize_url(trim($_POST['destination_url'] ?? ''));
$active = isset($_POST['is_active']) ? 1 : 0;
$utm = trim($_POST['utm_json'] ?? '');

// Detect destination column name compatibility
$destCol = 'destination_url';
try {
  $chk = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination_url'");
  if ($chk && $chk->rowCount() === 0) {
    $alt = $pdo->query("SHOW COLUMNS FROM links LIKE 'url'");
    if ($alt && $alt->rowCount() > 0) $destCol = 'url';
    else {
      $alt2 = $pdo->query("SHOW COLUMNS FROM links LIKE 'destination'");
      if ($alt2 && $alt2->rowCount() > 0) $destCol = 'destination';
    }
  }
} catch (Throwable $e) { /* ignore */ }

// Optional columns
$hasUtm = false; $hasSlug = false;
$hasActive = true; $hasCreatedAt = true; $hasUpdatedAt = true;
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'utm_json'"); $hasUtm = $c && $c->rowCount()>0; } catch(Throwable $e){ $hasUtm=false; }
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'slug'"); $hasSlug = $c && $c->rowCount()>0; } catch(Throwable $e){ $hasSlug=false; }
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'is_active'"); $hasActive = $c && $c->rowCount()>0; } catch(Throwable $e){ $hasActive=true; }
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'created_at'"); $hasCreatedAt = $c && $c->rowCount()>0; } catch(Throwable $e){ $hasCreatedAt=false; }
try { $c = $pdo->query("SHOW COLUMNS FROM links LIKE 'updated_at'"); $hasUpdatedAt = $c && $c->rowCount()>0; } catch(Throwable $e){ $hasUpdatedAt=false; }

if ($title === '' || $url === '') {
  $err = ['ok'=>false,'error'=>'Title and URL are required'];
  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { header('Content-Type: application/json'); echo json_encode($err); exit; }
  flash_set('links','Title and URL are required','error'); header('Location: /links'); exit;
}

// Validate UTM JSON when provided
if ($utm !== '') {
  $decoded = json_decode($utm, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    $errMsg = 'UTM must be valid JSON';
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$errMsg]); exit; }
    flash_set('links',$errMsg,'error'); header('Location: /links'); exit;
  }
}

try {
  if ($id > 0) {
    $sql = 'UPDATE links SET title=:t, ' . $destCol . '=:u' . ($hasActive ? ', is_active=:a' : '') . ($hasUtm ? ', utm_json=:utm' : '') . ($hasUpdatedAt ? ', updated_at=NOW()' : '') . ' WHERE id=:id AND user_id=:uid';
    $st = $pdo->prepare($sql);
    $params = [':t'=>$title, ':u'=>$url, ':id'=>$id, ':uid'=>$uid];
    if ($hasActive) $params[':a'] = $active;
    if ($hasUtm) $params[':utm'] = ($utm !== '' ? $utm : null);
    $st->execute($params);
  } else {
    // ensure slug uniqueness if column exists
    $slug = null;
    if ($hasSlug) {
      do { $slug = wz_generate_slug(6); $chk = $pdo->prepare('SELECT 1 FROM links WHERE slug=:s'); $chk->execute([':s'=>$slug]); } while ($chk->fetchColumn());
    }
    $cols = ['user_id','title',$destCol];
    $vals = [':uid',':t',':u'];
    if ($hasActive) { $cols[]='is_active'; $vals[]=':a'; }
    if ($hasSlug) { $cols[]='slug'; $vals[]=':s'; }
    if ($hasUtm) { $cols[]='utm_json'; $vals[]=':utm'; }
    if ($hasCreatedAt) { $cols[]='created_at'; $vals[]='NOW()'; }
    if ($hasUpdatedAt) { $cols[]='updated_at'; $vals[]='NOW()'; }
    $sql = 'INSERT INTO links ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';
    $st = $pdo->prepare($sql);
    $params = [':uid'=>$uid, ':t'=>$title, ':u'=>$url];
    if ($hasActive) $params[':a'] = $active;
    if ($hasSlug) $params[':s'] = $slug;
    if ($hasUtm)  $params[':utm'] = ($utm !== '' ? $utm : null);
    $st->execute($params);
    $id = (int)$pdo->lastInsertId();
  }
} catch (Throwable $e) {
  error_log('links/save error: '.$e->getMessage());
  if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') { http_response_code(500); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'DB error']); exit; }
  $back = $id ? "/links/edit.php?id={$id}" : '/links/new.php';
  flash_set('links','Database error','error'); header("Location: {$back}"); exit;
}

if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
  header('Content-Type: application/json'); echo json_encode(['ok'=>true,'id'=>$id,'redirect'=>'/links']); exit;
}

flash_set('links','Link saved','success');
header('Location: /links');
exit;


