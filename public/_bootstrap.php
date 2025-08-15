<?php
// public/_bootstrap.php
$inc = __DIR__ . '/../includes/bootstrap.php';
if (!is_file($inc)) {
  http_response_code(500);
  exit('Missing includes/bootstrap.php');
}
require $inc;