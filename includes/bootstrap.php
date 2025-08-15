<?php
declare(strict_types=1);

// مسارات أساسية
if (!defined('BASE_PATH')) define('BASE_PATH', realpath(__DIR__ . '/..'));
if (!defined('INC_PATH'))  define('INC_PATH',  __DIR__);
if (!defined('PUB_PATH'))  define('PUB_PATH',  realpath(BASE_PATH . '/public'));

// سيشن
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name('whoizme_sess');
  session_start();
}

// تحميل الكونفيج والداتابيز
$config = require INC_PATH . '/config.php';

require_once INC_PATH . '/db.php';
require_once INC_PATH . '/auth.php';
require_once INC_PATH . '/helpers.php';

// إظهار الأخطاء في اللوكال
if (($config['env'] ?? 'local') === 'local') {
  ini_set('display_errors','1');
  ini_set('display_startup_errors','1');
  error_reporting(E_ALL);
}