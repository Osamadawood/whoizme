<?php
// includes/auth_guard.php
require_once __DIR__ . '/bootstrap.php';
if (empty($_SESSION['user_id'])) {
  $ret = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
  header('Location: /login.php?return=' . urlencode($ret));
  exit;
}