<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid    = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: user's subscriptions ─────────────────────── */
if ($method === 'GET') {
    // Admin: get all pending
    if (isset($_GET['admin']) && $_SESSION['role'] === 'admin') {
        $status = $_GET['status'] ?? 'pending';
        $stmt = $pdo->prepare("SELECT s.*, u.name as user_name, u.email as user_email FROM subscriptions s JOIN users u ON s.user_id = u.id WHERE s.status = ? ORDER BY s.created_at DESC");
        $stmt->execute([$status]);
        json_out(true, ['subscriptions' => $stmt->fetchAll()]);
    }

    // User: own subscriptions
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$uid]);
    json_out(true, ['subscriptions' => $stmt->fetchAll()]);
}

/* ── POST: create subscription request ─────────────── */
if ($method === 'POST') {
    // Support both JSON and FormData
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }

    $asset_limit     = max(10, (int)($input['asset_limit'] ?? 50));
    $remove_branding = (int)($input['remove_branding'] ?? 0);
    $billing_cycle   = in_array($input['billing_cycle'] ?? '', ['monthly', 'yearly']) ? $input['billing_cycle'] : 'monthly';
    $amount          = floatval($input['amount'] ?? 0);
    $transaction_id  = trim($input['transaction_id'] ?? '');

    if ($amount <= 0) json_out(false, [], 'Invalid amount.');

    // Enforce mandatory proof (either ID or Screenshot)
    if (empty($transaction_id) && (empty($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK)) {
        json_out(false, [], 'Please provide either a Transaction ID or a Payment Screenshot as proof.');
    }

    // Handle screenshot upload
    $proof_path = null;
    if (!empty($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) json_out(false, [], 'File too large. Max 5MB.');

        // Validate client-side extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed_exts)) {
            json_out(false, [], 'Invalid file extension. Use PNG, JPG, or WebP.');
        }

        // Validate actual file MIME type server-side
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed_mimes)) {
            json_out(false, [], 'Invalid file content type. Use PNG, JPG, or WebP.');
        }

        $upload_dir = __DIR__ . '/../uploads/proofs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $filename = 'proof_' . $uid . '_' . time() . '.' . $ext;
        $dest = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $proof_path = 'uploads/proofs/' . $filename;
        }
    }

    // Get user info
    $u = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $u->execute([$uid]);
    $user = $u->fetch();

    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, asset_limit, remove_branding, billing_cycle, amount, status, user_email, user_name, transaction_id, payment_proof) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
    $stmt->execute([$uid, $asset_limit, $remove_branding, $billing_cycle, $amount, $user['email'], $user['name'], $transaction_id ?: null, $proof_path]);
    $sub_id = $pdo->lastInsertId();

    // Notify admin
    try {
        require_once __DIR__ . '/../includes/send_receipt.php';
        $sub_data = [
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'amount' => $amount,
            'billing_cycle' => $billing_cycle,
            'asset_limit' => $asset_limit,
            'remove_branding' => $remove_branding,
            'transaction_id' => $transaction_id,
            'payment_proof' => $proof_path
        ];
        send_payment_notification($pdo, $sub_data);
    } catch (Exception $e) {}

    json_out(true, ['subscription_id' => $sub_id], 'Payment request submitted. Your plan will be activated within 24 hours after verification.');
}

/* ── PUT: admin approve/reject ─────────────────────── */
if ($method === 'PUT') {
    if ($_SESSION['role'] !== 'admin') json_out(false, [], 'Admin access required.');

    $input  = json_decode(file_get_contents('php://input'), true);
    $sub_id = (int)($input['id'] ?? 0);
    $action = $input['action'] ?? '';

    if (!$sub_id) json_out(false, [], 'Subscription ID required.');
    if (!in_array($action, ['approve', 'reject'])) json_out(false, [], 'Invalid action.');

    $sub = $pdo->prepare("SELECT * FROM subscriptions WHERE id = ?");
    $sub->execute([$sub_id]);
    $subscription = $sub->fetch();
    if (!$subscription) json_out(false, [], 'Subscription not found.');

    if ($action === 'reject') {
        $pdo->prepare("UPDATE subscriptions SET status = 'rejected' WHERE id = ?")->execute([$sub_id]);
        json_out(true, [], 'Subscription rejected.');
    }

    // Approve
    $now = date('Y-m-d H:i:s');
    $cycle = $subscription['billing_cycle'];
    $expires = $cycle === 'yearly'
        ? date('Y-m-d H:i:s', strtotime('+1 year'))
        : date('Y-m-d H:i:s', strtotime('+1 month'));

    // Update subscription
    $pdo->prepare("UPDATE subscriptions SET status = 'active', starts_at = ?, expires_at = ? WHERE id = ?")
        ->execute([$now, $expires, $sub_id]);

    // Update user limits
    $new_limit = $subscription['asset_limit'];
    $show_ref  = $subscription['remove_branding'] ? 0 : 1;
    $pdo->prepare("UPDATE users SET serial_limit = ?, show_reference = ?, subscription_expires = ? WHERE id = ?")
        ->execute([$new_limit, $show_ref, $expires, $subscription['user_id']]);

    // Send email receipts
    try {
        require_once __DIR__ . '/../includes/send_receipt.php';
        send_subscription_receipt($pdo, $subscription, $expires);
    } catch (Exception $e) {
        // Email failure shouldn't block approval
    }

    json_out(true, ['expires_at' => $expires], 'Subscription approved and activated.');
}

json_out(false, [], 'Method not allowed.');
