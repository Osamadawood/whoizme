<?php
// app/includes/boot.php
$root = dirname(__DIR__, 1); // /app
$projectRoot = dirname(__DIR__, 2); // project root

// config
$config = require $projectRoot.'/app/config.php';
if (!is_array($config)) { $config = []; }

// start session
if (session_status() === PHP_SESSION_NONE) session_start();

// helpers
if (!function_exists('h')) {
  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
function base_url(array $cfg){ return rtrim($cfg['base_url'] ?? '', '/'); }

// flash
function flash(string $key, ?string $val=null){
  if ($val===null) {
    if (!empty($_SESSION['flash'][$key])) { $v=$_SESSION['flash'][$key]; unset($_SESSION['flash'][$key]); return $v; }
    return null;
  }
  $_SESSION['flash'][$key]=$val;
}

// csrf
function csrf_token(){
  if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['_csrf'];
}
function csrf_field(){
  echo '<input type="hidden" name="_csrf" value="'.h(csrf_token()).'">';
}
function csrf_check(){
  if ($_SERVER['REQUEST_METHOD']==='POST') {
    $in = $_POST['_csrf'] ?? '';
    if (!$in || !hash_equals($_SESSION['_csrf'] ?? '', $in)) {
      http_response_code(419);
      exit('Invalid CSRF token.');
    }
  }
}

// pdo
function pdo_conn(array $cfg){
  $dsn="mysql:host={$cfg['db']['host']};port={$cfg['db']['port']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";
  return new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}

// auth helpers
function auth_id(){ return $_SESSION['uid'] ?? null; }
function require_guest(){ if (auth_id()) { header('Location: /dashboard.php'); exit; } }
function require_auth(){ if (!auth_id()) { header('Location: /login.php'); exit; } }