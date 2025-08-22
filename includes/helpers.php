<?php
// --- Link/Visit tracking helpers ---

if (!function_exists('client_ip')) {
    function client_ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // خُد أول IP لو في قائمة
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('track_hit')) {
    /**
     * يسجّل زيارة لرابط مختصر/بروفايل
     * @param string $code  الكود الفريد للرابط
     */
    function track_hit(string $code): void {
        if ($code === '') return;
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                INSERT INTO short_link_hits (code, ip, ua, ref)
                VALUES (:code, :ip, :ua, :ref)
            ");
            $stmt->execute([
                ':code' => $code,
                ':ip'   => substr((string)client_ip(), 0, 45),
                ':ua'   => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
                ':ref'  => substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 1000),
            ]);
        } catch (Throwable $e) {
            // في الديف فقط: ممكن تسجّل في اللوج، لكن متوقفش الصفحة
            if (!empty($GLOBALS['CFG']['dev'])) {
                error_log('[track_hit] ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('wz_url')) {
    function wz_url(string $path, array $query = []): string {
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('~\.php$~i', '', $path);
        if ($query) {
            return $path . '?' . http_build_query($query);
        }
        return $path;
    }
}

if (!function_exists('wz_is_safe_next')) {
    function wz_is_safe_next(?string $next): bool {
        if (!$next) return false;
        if (preg_match('~^https?://~i', $next)) return false;
        if (strpos($next, "\0") !== false) return false;
        return (bool)preg_match('~^/[A-Za-z0-9_\-./?=&]*$~', $next);
    }
}

if (!function_exists('wz_redirect')) {
    function wz_redirect(string $path, array $query = [], int $status = 302): void {
        $url = wz_url($path, $query);
        header('Location: ' . $url, true, $status);
        exit;
    }
}