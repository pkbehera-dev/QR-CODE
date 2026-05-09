<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid = $_SESSION['user_id'];

if (!isset($_FILES['logo_file'])) {
    json_out(false, [], 'No file uploaded.');
}

$file = $_FILES['logo_file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    json_out(false, [], 'Invalid file type. Allowed: ' . implode(', ', $allowed));
}

if ($file['size'] > 2 * 1024 * 1024) {
    json_out(false, [], 'File too large. Max 2MB.');
}

$filename = 'logo_' . $uid . '_' . time() . '.' . $ext;
$target   = __DIR__ . '/../uploads/' . $filename;

if (move_uploaded_file($file['tmp_name'], $target)) {
    $url = 'uploads/' . $filename;
    
    // Delete old local file if it exists
    $stmt = $pdo->prepare("SELECT logo_url FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $old = $stmt->fetchColumn();
    if ($old && strpos($old, 'uploads/') === 0 && file_exists(__DIR__ . '/../' . $old)) {
        @unlink(__DIR__ . '/../' . $old);
    }

    $pdo->prepare("UPDATE users SET logo_url = ? WHERE id = ?")->execute([$url, $uid]);
    json_out(true, ['logo_url' => $url], 'Logo uploaded successfully.');
}

json_out(false, [], 'Failed to save file.');
