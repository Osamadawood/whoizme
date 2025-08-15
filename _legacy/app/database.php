<?php
/* Guard: don't redeclare the class if this file got included more than once */
if (!class_exists('Database')) {
  class Database {
    private PDO $pdo;

    public function __construct(array $cfg) {
      $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['name']);
      $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
    }

    public function pdo(): PDO {
      return $this->pdo;
    }
  }
}