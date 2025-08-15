<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config.php';
$dev = !empty($config['dev']);
if ($dev){ ini_set('display_errors',1); error_reporting(E_ALL); }

$to = $_GET['to'] ?? 'test@example.com';
$subject = 'Whoiz.me test';
$body = "If you can read this, mail pipeline works.\nBase URL: ".($config['base_url'] ?? 'N/A');

// dev => log to file
if ($dev) {
    $logDir = $root . '/storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    file_put_contents($logDir.'/mail.log', "[".date('c')."] To: {$to}\n{$subject}\n{$body}\n\n", FILE_APPEND);
    echo "DEV: logged to storage/logs/mail.log";
    exit;
}

$headers = "From: ".($config['mail']['from_name'] ?? 'Whoiz.me')." <".($config['mail']['from_email'] ?? 'no-reply@whoiz.me').">\r\n";
if (@mail($to, $subject, $body, $headers)) echo "Mail sent";
else echo "Mail failed";