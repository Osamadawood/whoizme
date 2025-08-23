<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/events.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $uid = current_user_id();
    $id = (int)($_POST['id'] ?? 0);
    
    // Validate required fields
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $payload = trim($_POST['payload'] ?? '');
    
    if (empty($title)) {
        throw new Exception('Title is required');
    }
    
    if (empty($type)) {
        throw new Exception('QR type is required');
    }
    
    if (empty($payload)) {
        throw new Exception('QR content is required');
    }
    
    // Validate type
    $validTypes = ['url', 'vcard', 'text', 'email', 'wifi', 'pdf', 'app', 'image'];
    if (!in_array($type, $validTypes, true)) {
        throw new Exception('Invalid QR type');
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
        try {
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
        } catch (PDOException $e) {
            // If style_json column doesn't exist, try without it
            if (strpos($e->getMessage(), 'style_json') !== false) {
                $sql = "UPDATE qr_codes SET 
                        title = :title,
                        type = :type,
                        payload = :payload,
                        updated_at = NOW()
                        WHERE id = :id AND user_id = :uid";
                
                $st = $pdo->prepare($sql);
                $result = $st->execute([
                    ':title' => $title,
                    ':type' => $type,
                    ':payload' => $payload,
                    ':id' => $id,
                    ':uid' => $uid
                ]);
            } else {
                throw $e;
            }
        }
        
        if (!$result || $st->rowCount() === 0) {
            throw new Exception('QR code not found or update failed');
        }
        
        // Log the update event
        wz_log_event($pdo, $uid, 'qr', $id, 'create', $title);
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'message' => 'QR code updated successfully',
            'short_code' => $shortCode
        ]);
        
    } else {
        // INSERT new QR code
        try {
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
        } catch (PDOException $e) {
            // If columns don't exist, try with basic columns only
            if (strpos($e->getMessage(), 'short_code') !== false || strpos($e->getMessage(), 'style_json') !== false) {
                $sql = "INSERT INTO qr_codes (user_id, type, title, payload, created_at)
                        VALUES (:uid, :type, :title, :payload, NOW())";
                
                $st = $pdo->prepare($sql);
                $result = $st->execute([
                    ':uid' => $uid,
                    ':type' => $type,
                    ':title' => $title,
                    ':payload' => $payload
                ]);
            } else {
                throw $e;
            }
        }
        
        if (!$result) {
            throw new Exception('Failed to create QR code');
        }
        
        $newId = (int)$pdo->lastInsertId();
        
        // Log the creation event
        wz_log_event($pdo, $uid, 'qr', $newId, 'create', $title);
        
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'message' => 'QR code created successfully',
            'short_code' => $shortCode
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
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
        
        // Check if code already exists (handle case where column might not exist)
        try {
            $st = $pdo->prepare("SELECT COUNT(*) FROM qr_codes WHERE short_code = :code");
            $st->execute([':code' => $code]);
            
            if ($st->fetchColumn() == 0) {
                return $code;
            }
        } catch (PDOException $e) {
            // If short_code column doesn't exist, just return the generated code
            if (strpos($e->getMessage(), 'short_code') !== false) {
                return $code;
            }
            throw $e;
        }
    }
    
    // Fallback: use timestamp-based code
    return 'QR' . time();
}
