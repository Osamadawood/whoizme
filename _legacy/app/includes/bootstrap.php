<?php // /includes/paths.php
declare(strict_types=1);

// مسارات مطلقة وآمنة
define('BASE_PATH', realpath(__DIR__ . '/..'));                  // root بتاع المشروع
define('INC_PATH',  realpath(__DIR__));                          // /includes
define('PUB_PATH',  realpath(BASE_PATH . '/public'));            // /public

// عنوان الأساس (حرّره لو لزم)
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'whoiz.local:8890';
$baseUrl  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
define('WEB_ROOT', $scheme . '://' . $host . ($baseUrl === '' ? '' : $baseUrl));