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

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $log_id = (int)($input['id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $note = trim($input['note'] ?? '');

    if (!$log_id) json_out(false, [], 'Log ID required.');
    if ($note === '') json_out(false, [], 'Maintenance note cannot be empty.');

    // Verify ownership of the log
    $stmt = $pdo->prepare(
        "SELECT m.id FROM maintenance_logs m 
         JOIN serials s ON m.serial_id = s.id 
         WHERE m.id = ? AND s.user_id = ?"
    );
    $stmt->execute([$log_id, $uid]);
    if (!$stmt->fetch()) {
        json_out(false, [], 'Log not found or access denied.');
    }

    try {
        $up = $pdo->prepare("UPDATE maintenance_logs SET status = ?, note = ? WHERE id = ?");
        $up->execute([$status, $note, $log_id]);
        json_out(true, [], 'Maintenance log updated.');
    } catch (PDOException $e) {
        json_out(false, [], "Database error: " . $e->getMessage());
    }
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $log_id = (int)($input['id'] ?? 0);

    if (!$log_id) json_out(false, [], 'Log ID required.');

    // Verify ownership of the log
    $stmt = $pdo->prepare(
        "SELECT m.id FROM maintenance_logs m 
         JOIN serials s ON m.serial_id = s.id 
         WHERE m.id = ? AND s.user_id = ?"
    );
    $stmt->execute([$log_id, $uid]);
    if (!$stmt->fetch()) {
        json_out(false, [], 'Log not found or access denied.');
    }

    try {
        $del = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
        $del->execute([$log_id]);
        json_out(true, [], 'Maintenance log deleted.');
    } catch (PDOException $e) {
        json_out(false, [], "Database error: " . $e->getMessage());
    }
}

json_out(false, [], 'Method not allowed.');
