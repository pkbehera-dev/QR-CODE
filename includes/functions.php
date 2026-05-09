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
    $prefix = $uStmt->fetchColumn() ?? '';

    // Get product short name
    $pStmt = $pdo->prepare("SELECT short_name FROM products WHERE id = ?");
    $pStmt->execute([$product_id]);
    $short_name = $pStmt->fetchColumn() ?? 'ITEM';

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
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    if ($result === false) return false;
    
    $json = json_decode($result, true);
    return $json['success'] ?? false;
}

function get_scan_base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL;
}
