<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    json_out(false, [], 'Unauthorized.');
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if ($action === 'update_limit') {
    $limit = (int)($_POST['limit'] ?? 20);
    $stmt = $pdo->prepare("UPDATE users SET serial_limit = ? WHERE id = ?");
    if ($stmt->execute([$limit, $id])) {
        json_out(true, [], 'Limit updated.');
    } else {
        json_out(false, [], 'Database error.');
    }
} elseif ($action === 'update_branding') {
    $status = (int)($_POST['status'] ?? 1);
    $stmt = $pdo->prepare("UPDATE users SET show_reference = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        json_out(true, [], 'Branding setting updated.');
    } else {
        json_out(false, [], 'Database error.');
    }
}

json_out(false, [], 'Invalid action.');
