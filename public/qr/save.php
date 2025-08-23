<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/events.php';
require_once __DIR__ . '/../../includes/flash.php';

/** @var PDO $pdo */
$uid = current_user_id();
$id = (int)($_POST['id'] ?? 0);

try {
    // Validate required fields
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
    if (empty($type)) {
        throw new Exception('QR type is required');
    }
    
    // Validate type
    $validTypes = ['url', 'vcard', 'text', 'email', 'wifi', 'pdf', 'app', 'image'];
    if (!in_array($type, $validTypes, true)) {
        throw new Exception('Invalid QR type');
    }
    
    // Build payload based on type
    $payload = buildPayloadFromForm($type, $_POST);
    if (empty($payload)) {
        throw new Exception('Please fill in the required fields');
    }
    
    // Prepare style data
    $styleData = [
        'fg' => $_POST['fg'] ?? '#4B6BFB',
        'bg' => $_POST['bg'] ?? '#ffffff',
        'size' => (int)($_POST['size'] ?? 256),
        'quiet' => (int)($_POST['quiet'] ?? 16),
        'rounded' => (bool)($_POST['rounded'] ?? true)
    ];
    
    $styleJson = json_encode($styleData);
    
    // Generate short code for new QR codes
    $shortCode = null;
    if (!$id) {
        $shortCode = generateShortCode($pdo);
    }
    
    if ($id) {
        // UPDATE existing QR code
        $sql = "UPDATE qr_codes SET 
                title = :title,
                type = :type,
                payload = :payload,
                style_json = :style_json,
                updated_at = NOW()
                WHERE id = :id AND user_id = :uid";
        
        $st = $pdo->prepare($sql);
        $result = $st->execute([
            ':title' => $title,
            ':type' => $type,
            ':payload' => $payload,
            ':style_json' => $styleJson,
            ':id' => $id,
            ':uid' => $uid
        ]);
        
        if (!$result || $st->rowCount() === 0) {
            throw new Exception('QR code not found or update failed');
        }
        
        // Log the update event
        wz_log_event($pdo, $uid, 'qr', $id, 'create', $title);
        
        flash_set('qr', 'QR code updated successfully', 'success');
        
    } else {
        // INSERT new QR code
        $sql = "INSERT INTO qr_codes (user_id, type, title, payload, style_json, short_code, created_at, updated_at)
                VALUES (:uid, :type, :title, :payload, :style_json, :short_code, NOW(), NOW())";
        
        $st = $pdo->prepare($sql);
        $result = $st->execute([
            ':uid' => $uid,
            ':type' => $type,
            ':title' => $title,
            ':payload' => $payload,
            ':style_json' => $styleJson,
            ':short_code' => $shortCode
        ]);
        
        if (!$result) {
            throw new Exception('Failed to create QR code');
        }
        
        $newId = (int)$pdo->lastInsertId();
        
        // Log the creation event
        wz_log_event($pdo, $uid, 'qr', $newId, 'create', $title);
        
        flash_set('qr', 'QR code created successfully', 'success');
    }
    
    // Redirect to QR list
    header('Location: /qr');
    exit;
    
} catch (Exception $e) {
    flash_set('qr', $e->getMessage(), 'error');
    header('Location: /qr/new' . ($id ? "?id=$id" : ''));
    exit;
}

/**
 * Build payload from form data based on QR type
 */
function buildPayloadFromForm(string $type, array $post): string {
    switch ($type) {
        case 'url':
            $url = trim($post['destination_url'] ?? '');
            if (empty($url)) return '';
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }
            return $url;
            
        case 'vcard':
            $fullname = trim($post['fullname'] ?? '');
            if (empty($fullname)) return '';
            
            $org = trim($post['org'] ?? '');
            $title = trim($post['job_title'] ?? '');
            $phone = trim($post['phone'] ?? '');
            $email = trim($post['email'] ?? '');
            $website = trim($post['website'] ?? '');
            $address = trim($post['address'] ?? '');
            
            $vcard = "BEGIN:VCARD\r\nVERSION:3.0\r\n";
            $vcard .= "N:;" . escapeVCardValue($fullname) . ";;;\r\n";
            $vcard .= "FN:" . escapeVCardValue($fullname) . "\r\n";
            
            if ($org) $vcard .= "ORG:" . escapeVCardValue($org) . "\r\n";
            if ($title) $vcard .= "TITLE:" . escapeVCardValue($title) . "\r\n";
            if ($phone) $vcard .= "TEL;TYPE=CELL:" . escapeVCardValue($phone) . "\r\n";
            if ($email) $vcard .= "EMAIL:" . escapeVCardValue($email) . "\r\n";
            if ($website) $vcard .= "URL:" . escapeVCardValue($website) . "\r\n";
            if ($address) $vcard .= "ADR;TYPE=HOME:" . escapeVCardValue($address) . "\r\n";
            
            $vcard .= "END:VCARD";
            return $vcard;
            
        case 'text':
            return trim($post['text'] ?? '');
            
        case 'email':
            $to = trim($post['to'] ?? '');
            if (empty($to)) return '';
            
            $subject = trim($post['subject'] ?? '');
            $body = trim($post['body'] ?? '');
            
            $mailto = "mailto:" . urlencode($to);
            $params = [];
            
            if ($subject) $params[] = "subject=" . urlencode($subject);
            if ($body) $params[] = "body=" . urlencode($body);
            
            if ($params) {
                $mailto .= "?" . implode('&', $params);
            }
            
            return $mailto;
            
        case 'wifi':
            $ssid = trim($post['ssid'] ?? '');
            if (empty($ssid)) return '';
            
            $password = trim($post['password'] ?? '');
            $encryption = $post['encryption'] ?? 'WPA';
            $hidden = isset($post['hidden']);
            
            $wifi = "WIFI:T:$encryption;S:" . escapeWifiValue($ssid) . ";";
            
            if ($password && $encryption !== 'nopass') {
                $wifi .= "P:" . escapeWifiValue($password) . ";";
            }
            
            $wifi .= "H:" . ($hidden ? 'true' : 'false') . ";;";
            return $wifi;
            
        case 'pdf':
            return trim($post['pdf_url'] ?? '');
            
        case 'app':
            $ios = trim($post['ios_url'] ?? '');
            $android = trim($post['android_url'] ?? '');
            $platform = $post['platform_preview'] ?? 'ios';
            
            if ($platform === 'ios' && $ios) {
                return $ios;
            } elseif ($platform === 'android' && $android) {
                return $android;
            }
            
            return $ios ?: $android;
            
        case 'image':
            return trim($post['image_url'] ?? '');
            
        default:
            return '';
    }
}

/**
 * Escape vCard values
 */
function escapeVCardValue(string $value): string {
    return str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $value);
}

/**
 * Escape WiFi values
 */
function escapeWifiValue(string $value): string {
    return str_replace([';', ':', ','], ['\\;', '\\:', '\\,'], $value);
}

/**
 * Generate a unique short code for QR codes
 */
function generateShortCode(PDO $pdo): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $maxAttempts = 10;
    
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Check if code already exists
        $st = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE short_code = :code");
        $st->execute([':code' => $code]);
        
        if ($st->fetchColumn() == 0) {
            return $code;
        }
    }
    
    // Fallback: use timestamp-based code
    return 'QR' . time();
}