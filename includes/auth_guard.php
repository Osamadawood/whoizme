<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
require_once __DIR__.'/auth.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}