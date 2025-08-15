<?php
// Redirect permanently to /u.php to keep a single code path
$path = '/u.php';
if (!empty($_SERVER['QUERY_STRING'])) $path .= '?' . $_SERVER['QUERY_STRING'];
header("Location: $path", true, 301);
exit;