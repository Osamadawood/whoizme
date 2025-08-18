<?php
declare(strict_types=1);
define('SKIP_AUTH_GUARD', true);
require __DIR__ . '/../includes/bootstrap.php';
logout_user();
header('Location: /login.php', true, 302);
exit;