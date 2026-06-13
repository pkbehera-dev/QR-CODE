<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid    = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: list products ─────────────────────────────── */
if ($method === 'GET') {
    $stmt = $pdo->prepare(
        "SELECT p.*, (SELECT COUNT(*) FROM serials s WHERE s.product_id = p.id) as serial_count
         FROM products p WHERE p.user_id = ? ORDER BY p.created_at DESC"
    );
    $stmt->execute([$uid]);
    $products = $stmt->fetchAll();
    // Ensure icon has a default
    foreach ($products as &$p) {
        if (empty($p['icon'])) $p['icon'] = 'bi-box-seam';
    }
    json_out(true, ['products' => $products]);
}

/* ── POST: add product or update ─────────────────────── */
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pid   = (int)($input['id'] ?? 0);
    $name  = trim($input['name'] ?? '');
    $short = strtoupper(trim($input['short_name'] ?? ''));
    $icon  = trim($input['icon'] ?? 'bi-box-seam');

    if ($pid > 0) {
        if (!$name || !$short) json_out(false, [], 'Name and short name required.');
        if (!preg_match('/^[A-Z0-9]+$/', $short)) json_out(false, [], 'Short name: letters/digits only.');

        // Verify ownership
        $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
        $chk->execute([$pid, $uid]);
        if (!$chk->fetch()) json_out(false, [], 'Not found.');

        $stmt = $pdo->prepare("UPDATE products SET name = ?, short_name = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $short, $icon, $pid]);
        
        json_out(true, [], 'Product updated.');
    } else {
        if (!$name || !$short) json_out(false, [], 'Name and short name required.');
        if (!preg_match('/^[A-Z0-9]+$/', $short)) json_out(false, [], 'Short name: letters/digits only.');

        $stmt = $pdo->prepare("INSERT INTO products (user_id, name, short_name, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uid, $name, $short, $icon]);
        $id = $pdo->lastInsertId();
        json_out(true, ['product' => [
            'id' => $id,
            'name' => $name,
            'short_name' => $short,
            'icon' => $icon,
            'serial_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]]);
    }
}

/* ── PUT: update product ────────────────────────────── */
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pid   = (int)($input['id'] ?? 0);
    $name  = trim($input['name'] ?? '');
    $short = strtoupper(trim($input['short_name'] ?? ''));
    $icon  = trim($input['icon'] ?? 'bi-box-seam');

    if (!$pid || !$name || !$short) json_out(false, [], 'ID, Name, and Short name required.');
    if (!preg_match('/^[A-Z0-9]+$/', $short)) json_out(false, [], 'Short name: letters/digits only.');

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
    $chk->execute([$pid, $uid]);
    if (!$chk->fetch()) json_out(false, [], 'Not found.');

    $stmt = $pdo->prepare("UPDATE products SET name = ?, short_name = ?, icon = ? WHERE id = ?");
    $stmt->execute([$name, $short, $icon, $pid]);
    
    json_out(true, [], 'Product updated.');
}

/* ── DELETE: remove product ─────────────────────────── */
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $params);
    $pid = (int)($params['id'] ?? 0);
    if (!$pid) json_out(false, [], 'Product ID required.');

    // Verify ownership
    $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
    $chk->execute([$pid, $uid]);
    if (!$chk->fetch()) json_out(false, [], 'Not found.');

    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
    json_out(true, [], 'Product deleted.');
}

json_out(false, [], 'Method not allowed.');
