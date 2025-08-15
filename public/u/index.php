<?php
declare(strict_types=1);
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../u.php';
if (!empty($_SERVER['QUERY_STRING'])) $path .= '?' . $_SERVER['QUERY_STRING'];
header("Location: $path", true, 301);
exit;