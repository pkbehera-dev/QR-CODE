<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $serial_id = isset($_GET['serial_id']) ? (int)$_GET['serial_id'] : 0;

    $query = "SELECT m.*, s.serial_number, s.custom_fields, p.name as product_name 
              FROM maintenance_logs m 
              JOIN serials s ON m.serial_id = s.id 
              JOIN products p ON s.product_id = p.id 
              WHERE s.user_id = ?";
    $params = [$uid];

    if ($serial_id > 0) {
        $query .= " AND m.serial_id = ?";
        $params[] = $serial_id;
    }

    if ($status !== '') {
        $query .= " AND m.status = ?";
        $params[] = $status;
    }

    if ($search !== '') {
        $query .= " AND (s.serial_number LIKE ? OR p.name LIKE ? OR m.note LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $query .= " ORDER BY m.created_at DESC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        foreach ($logs as &$log) {
            $cf = $log['custom_fields'] ? json_decode($log['custom_fields'], true) : [];
            $log['custodian'] = $cf['user'] ?? '—';
            unset($log['custom_fields']);
        }

        json_out(true, ['logs' => $logs]);
    } catch (PDOException $e) {
        json_out(false, [], "Database error: " . $e->getMessage());
    }
}

json_out(false, [], 'Method not allowed.');
