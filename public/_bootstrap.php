<?php
$levels = [__DIR__, dirname(__DIR__), dirname(__DIR__,2), dirname(__DIR__,3)];
foreach ($levels as $base) {
  $try = $base . '/includes/bootstrap.php';
  if (is_file($try)) { require_once $try; return; }
}
http_response_code(500);
echo "Bootstrap not found";