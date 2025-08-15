<?php // /includes/db.php
declare(strict_types=1);

/** @var PDO|null $pdo */
static $pdo = null;

function db(): PDO {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  $cfg = require INC_PATH . '/config.php';
  $pdo = new PDO(
    $cfg['db']['dsn'],
    $cfg['db']['user'],
    $cfg['db']['pass'],
    $cfg['db']['options']
  );
  return $pdo;
}