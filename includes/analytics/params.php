<?php
declare(strict_types=1);

function ana_normalize_date(?string $s, string $fallback): string {
    $s = $s ? substr($s, 0, 10) : '';
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d ? $d->format('Y-m-d') : $fallback;
}

function ana_window_guard(string $from, string $to, int $maxDays = 365): void {
    $df = new DateTime($from); $dt = new DateTime($to);
    if ($df > $dt) throw new InvalidArgumentException('from_gt_to');
    $days = (int)$df->diff($dt)->days;
    if ($days > $maxDays) throw new InvalidArgumentException('range_too_large');
}

function ana_scope_sql(string $scope): array {
    switch ($scope) {
        case 'links':
            return [
                "SELECT lc.created_at FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id WHERE l.user_id=:uid AND DATE(lc.created_at) BETWEEN :from AND :to",
                []
            ];
        case 'qrs':
            return [
                "SELECT qs.created_at FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id WHERE q.user_id=:uid AND DATE(qs.created_at) BETWEEN :from AND :to",
                []
            ];
        default:
            return [
                "SELECT created_at FROM (
                    SELECT lc.created_at
                    FROM link_clicks lc INNER JOIN links l ON l.id=lc.link_id WHERE l.user_id=:uid AND DATE(lc.created_at) BETWEEN :from AND :to
                    UNION ALL
                    SELECT qs.created_at
                    FROM qr_scans qs INNER JOIN qr_codes q ON q.id=qs.qr_id WHERE q.user_id=:uid AND DATE(qs.created_at) BETWEEN :from AND :to
                ) t",
                []
            ];
    }
}

function ana_col_exists(PDO $pdo, string $table, string $col): bool {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :c");
        $st->execute([':c'=>$col]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) { return false; }
}

function ana_table_exists(PDO $pdo, string $table): bool {
    try {
        $st = $pdo->prepare('SHOW TABLES LIKE :t');
        $st->execute([':t'=>$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) { return false; }
}

function ana_first_col(PDO $pdo, string $table, array $candidates): ?string {
    foreach ($candidates as $c) if (ana_col_exists($pdo,$table,$c)) return $c; return null;
}

function ana_pick_created(PDO $pdo, string $table): ?string {
    return ana_first_col($pdo, $table, ['created_at','clicked_at','scanned_at','created','timestamp','ts','createdAt']);
}

function ana_pick_device(PDO $pdo, string $table): ?string {
    return ana_first_col($pdo, $table, ['ua_device','device','device_type','ua_device_type']);
}

function ana_pick_ref(PDO $pdo, string $table): ?string {
    return ana_first_col($pdo, $table, ['referrer','referer','ref','referrer_url','referrer_host']);
}

function ana_pick_user(PDO $pdo, string $table): ?string {
    return ana_first_col($pdo, $table, ['user_id','uid','owner_id']);
}

function ana_pick_fk(PDO $pdo, string $table, array $candidates): ?string {
    foreach ($candidates as $c) if (ana_col_exists($pdo,$table,$c)) return $c;
    foreach (['parent_id','item_id','object_id'] as $c) if (ana_col_exists($pdo,$table,$c)) return $c;
    return null;
}


