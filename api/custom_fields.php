<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid    = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: list custom fields ────────────────────────── */
if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM custom_fields WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$uid]);
    json_out(true, ['custom_fields' => $stmt->fetchAll()]);
}

/* ── POST: add custom field ─────────────────────────── */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name  = trim($input['field_name'] ?? '');

    if (!$name) json_out(false, [], 'Field name required.');

    $stmt = $pdo->prepare("INSERT INTO custom_fields (user_id, field_name) VALUES (?, ?)");
    $stmt->execute([$uid, $name]);
    $id = $pdo->lastInsertId();
    json_out(true, ['custom_field' => [
        'id' => $id,
        'field_name' => $name,
        'created_at' => date('Y-m-d H:i:s')
    ]]);
}

/* ── DELETE: remove custom field ────────────────────── */
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $params);
    $fid = (int)($params['id'] ?? 0);
    if (!$fid) json_out(false, [], 'Field ID required.');

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM custom_fields WHERE id = ? AND user_id = ?");
    $chk->execute([$fid, $uid]);
    if (!$chk->fetch()) json_out(false, [], 'Not found.');

    $pdo->prepare("DELETE FROM custom_fields WHERE id = ?")->execute([$fid]);
    json_out(true, [], 'Field deleted.');
}

json_out(false, [], 'Method not allowed.');
