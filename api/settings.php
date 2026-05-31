<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');
$uid   = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

/* ── GET current user info ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT name, email, role, serial_prefix, logo_url, company_heading, company_subheading, two_factor_enabled FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    json_out(true, ['user' => $stmt->fetch()]);
}

/* ── POST update ────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for file upload (logo)
    if (isset($_FILES['logo_file'])) {
        $file = $_FILES['logo_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
            json_out(false, [], 'Invalid image type.');
        }
        
        $path = 'uploads/logos/';
        if (!is_dir(__DIR__ . '/../' . $path)) mkdir(__DIR__ . '/../' . $path, 0755, true);
        
        $filename = 'logo_' . $uid . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../' . $path . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $logo_url = $path . $filename;
            $pdo->prepare("UPDATE users SET logo_url = ? WHERE id = ?")->execute([$logo_url, $uid]);
            json_out(true, ['logo_url' => $logo_url], 'Logo uploaded successfully.');
        } else {
            json_out(false, [], 'Upload failed.');
        }
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    if ($action === 'update_prefix') {
        $prefix = trim($input['serial_prefix'] ?? '');
        if ($prefix !== '') {
            $chk = $pdo->prepare("SELECT id FROM users WHERE serial_prefix = ? AND id != ?");
            $chk->execute([$prefix, $uid]);
            if ($chk->fetch()) {
                json_out(false, [], 'This Global Serial Prefix is already registered by another organization. Please choose a unique prefix.');
            }
        }
        $pdo->prepare("UPDATE users SET serial_prefix = ? WHERE id = ?")
            ->execute([$prefix, $uid]);
        json_out(true, [], 'Serial prefix updated successfully.');
    }

    if ($action === 'update_heading') {
        $heading = trim($input['company_heading'] ?? '');
        $subheading = trim($input['company_subheading'] ?? '');
        $pdo->prepare("UPDATE users SET company_heading = ?, company_subheading = ? WHERE id = ?")
            ->execute([$heading, $subheading, $uid]);
        json_out(true, [], 'Company headings updated.');
    }

    if ($action === 'update_logo') {
        $logo = trim($input['logo_url'] ?? '');
        $pdo->prepare("UPDATE users SET logo_url = ? WHERE id = ?")
            ->execute([$logo, $uid]);
        json_out(true, [], 'Logo updated.');
    }

    if ($action === 'update_email') {
        $email = trim($input['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            json_out(false, [], 'Invalid email.');
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $uid]);
        if ($chk->fetch()) json_out(false, [], 'Email already in use.');
        $pdo->prepare("UPDATE users SET email = ? WHERE id = ?")
            ->execute([$email, $uid]);
        json_out(true, [], 'Email updated.');
    }

    if ($action === 'update_password') {
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';
        if (!$current || !$new || !$confirm) json_out(false, [], 'All fields required.');
        if ($new !== $confirm) json_out(false, [], 'New passwords do not match.');
        if (strlen($new) < 6) json_out(false, [], 'Minimum 6 characters.');

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) json_out(false, [], 'Current password incorrect.');

        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        json_out(true, [], 'Password updated.');
    }
    
    if ($action === 'update_2fa') {
        $enabled = $input['enabled'] ? 1 : 0;
        // Check if user is admin - if so, they can't disable it
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        if ($stmt->fetchColumn() === 'admin' && !$enabled) {
            json_out(false, [], 'Two-Factor Authentication is compulsory for Administrators.');
        }
        $pdo->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?")
            ->execute([$enabled, $uid]);
        json_out(true, [], '2FA preference updated.');
    }

    if ($action === 'update_ref') {
        $show = ($input['show_reference'] ?? 1) ? 1 : 0;
        $pdo->prepare("UPDATE users SET show_reference = ? WHERE id = ?")
            ->execute([$show, $uid]);
        json_out(true, [], 'Reference preference updated.');
    }

    json_out(false, [], 'Unknown action.');
}

json_out(false, [], 'Method not allowed.');
