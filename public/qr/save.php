<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

/** @var PDO $pdo */
$uid = current_user_id();
$id  = (int)($_POST['id'] ?? 0);

// Validate and sanitize inputs
$title = trim($_POST['title'] ?? '');
$type = trim($_POST['type'] ?? '');
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($title) || strlen($title) > 120) {
    header('Location: /qr/new?error=invalid_title');
    exit;
}

if (!in_array($type, ['url', 'vcard', 'text'])) {
    header('Location: /qr/new?error=invalid_type');
    exit;
}

// Build payload based on type
$payload = '';
$code = '';

switch ($type) {
    case 'url':
        $url = trim($_POST['url'] ?? '');
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            header('Location: /qr/new?error=invalid_url');
            exit;
        }
        
        // Build UTM parameters if provided
        $utmParams = [];
        $utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utmFields as $field) {
            if (!empty($_POST[$field])) {
                $utmParams[$field] = trim($_POST[$field]);
            }
        }
        
        if (!empty($utmParams)) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $payload = $url . $separator . http_build_query($utmParams);
        } else {
            $payload = $url;
        }
        break;
        
    case 'vcard':
        $name = trim($_POST['vcard_name'] ?? '');
        if (empty($name)) {
            header('Location: /qr/new?error=invalid_vcard_name');
            exit;
        }
        
        // Build vCard 3.0 format
        $vcard = "BEGIN:VCARD\nVERSION:3.0\n";
        $vcard .= "FN:" . $name . "\n";
        
        // Optional fields
        $optionalFields = [
            'vcard_org' => 'ORG',
            'vcard_title' => 'TITLE',
            'vcard_email' => 'EMAIL',
            'vcard_phone' => 'TEL',
            'vcard_website' => 'URL',
            'vcard_address' => 'ADR',
            'vcard_notes' => 'NOTE'
        ];
        
        foreach ($optionalFields as $field => $vcardField) {
            $value = trim($_POST[$field] ?? '');
            if (!empty($value)) {
                if ($vcardField === 'ADR') {
                    // Format address properly
                    $vcard .= "ADR;TYPE=WORK:;;" . str_replace("\n", ";", $value) . "\n";
                } else {
                    $vcard .= $vcardField . ":" . $value . "\n";
                }
            }
        }
        
        $vcard .= "END:VCARD";
        $payload = $vcard;
        break;
        
    case 'text':
        $text = trim($_POST['text_content'] ?? '');
        if (empty($text) || strlen($text) > 1000) {
            header('Location: /qr/new?error=invalid_text');
            exit;
        }
        $payload = $text;
        break;
}

// Generate short code if not provided
if (empty($code)) {
    $code = generateShortCode();
}

try {
    if ($id) {
        // Update existing QR code
        $stmt = $pdo->prepare("
            UPDATE qr_codes 
            SET title = :title, type = :type, payload = :payload, is_active = :is_active, updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':title' => $title,
            ':type' => $type,
            ':payload' => $payload,
            ':is_active' => $is_active,
            ':id' => $id,
            ':uid' => $uid
        ]);
        
        if ($stmt->rowCount() === 0) {
            header('Location: /qr?error=not_found');
            exit;
        }
        
        $qr_id = $id;
    } else {
        // Insert new QR code
        $stmt = $pdo->prepare("
            INSERT INTO qr_codes (user_id, type, title, payload, short_code, is_active, created_at)
            VALUES (:uid, :type, :title, :payload, :code, :is_active, NOW())
        ");
        $stmt->execute([
            ':uid' => $uid,
            ':type' => $type,
            ':title' => $title,
            ':payload' => $payload,
            ':code' => $code,
            ':is_active' => $is_active
        ]);
        
        $qr_id = $pdo->lastInsertId();
        
        // Log creation event
        if (function_exists('wz_log_event')) {
            wz_log_event($pdo, $uid, 'qr', $qr_id, 'create', $title);
        }
    }
    
    // Generate QR code image (optional)
    generateQRImage($qr_id, $payload);
    
    // Redirect with success
    header('Location: /qr?created=1');
    exit;
    
} catch (PDOException $e) {
    error_log("QR save error: " . $e->getMessage());
    header('Location: /qr/new?error=save_failed');
    exit;
}

/**
 * Generate a unique short code
 */
function generateShortCode(): string {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $length = 8;
    
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Check if code already exists
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE short_code = :code");
        $stmt->execute([':code' => $code]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    
    return $code;
}

/**
 * Generate QR code image (optional)
 */
function generateQRImage(int $qr_id, string $payload): void {
    // This is optional - you can implement QR generation here
    // For now, we'll skip it to keep it simple
    // You can use libraries like phpqrcode or call external APIs
    
    /*
    // Example with phpqrcode library (if installed)
    if (class_exists('QRcode')) {
        $filename = __DIR__ . "/../qr/{$qr_id}.png";
        QRcode::png($payload, $filename, QR_ECLEVEL_L, 10);
    }
    */
}