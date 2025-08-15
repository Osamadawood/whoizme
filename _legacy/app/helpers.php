<?php // /includes/helpers.php
declare(strict_types=1);

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header("Location: $url"); exit; }

function view_date(?string $dt): string {
  if (!$dt) return '-';
  return date('Y-m-d H:i:s', strtotime($dt));
}