<?php
function json_out(bool $success, array $data = [], string $message = ''): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function generate_serial_number(PDO $pdo, int $uid, int $product_id): string {
    // Get user prefix
    $uStmt = $pdo->prepare("SELECT serial_prefix FROM users WHERE id = ?");
    $uStmt->execute([$uid]);
    $prefixVal = $uStmt->fetchColumn();
    $prefix = ($prefixVal === false || $prefixVal === null) ? '' : (string)$prefixVal;

    // Get product short name
    $pStmt = $pdo->prepare("SELECT short_name FROM products WHERE id = ?");
    $pStmt->execute([$product_id]);
    $shortVal = $pStmt->fetchColumn();
    $short_name = ($shortVal === false || $shortVal === null) ? 'ITEM' : (string)$shortVal;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM serials WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $count = (int)$stmt->fetchColumn();
    $next = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . $short_name . '-' . $next;
}

function verify_recaptcha($response) {
    if (!defined('RECAPTCHA_SECRET_KEY') || !RECAPTCHA_SECRET_KEY) return true; // Bypass if not configured
    if (empty($response)) return false;
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $response
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Disable SSL verification for XAMPP / localhost testing
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    
    if ($result === false) {
        error_log("reCAPTCHA cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    $json = json_decode($result, true);
    
    if (!isset($json['success']) || $json['success'] !== true) {
        error_log("reCAPTCHA API Error: " . print_r($json, true));
        return false;
    }
    return true;
}

function get_scan_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL;
}
