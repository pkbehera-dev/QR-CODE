<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

/* ── LOGIN ─────────────────────────────────────────────── */
if ($action === 'login') {
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        json_out(false, [], 'reCAPTCHA verification failed.');
    }
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) json_out(false, [], 'All fields are required.');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] === 'admin' || $user['two_factor_enabled'] == 1) {
            // Generate 6-digit 2FA code
            $code = sprintf("%06d", mt_rand(1, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $pdo->prepare("UPDATE users SET two_factor_code = ?, two_factor_expires = ? WHERE id = ?")
                ->execute([$code, $expires, $user['id']]);
            
            require_once __DIR__ . '/../includes/mailer.php';
            $body = "<h2>Admin Login Verification</h2><p>Your 2FA code is: <b style='font-size:1.5em;color:#2563eb'>$code</b></p><p>This code will expire in 15 minutes.</p>";
            send_mail($user['email'], 'Your Admin 2FA Code', $body);
            
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['requires_2fa'] = true;
            
            // Mask email for UI
            $parts = explode('@', $user['email']);
            $masked = substr($parts[0], 0, 2) . '***@' . $parts[1];
            
            json_out(true, ['requires_2fa' => true, 'email' => $masked]);
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];
            json_out(true, ['requires_2fa' => false, 'role' => $user['role']]);
        }
    }
    json_out(false, [], 'Invalid email or password.');
}

/* ── VERIFY 2FA ────────────────────────────────────────── */
if ($action === 'verify_2fa') {
    $code = trim($_POST['code'] ?? '');
    $tid  = $_SESSION['temp_user_id'] ?? null;
    if (!$tid || !$code) json_out(false, [], 'Invalid request or session expired.');
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$tid]);
    $user = $stmt->fetch();
    
    if (!$user || $user['two_factor_code'] !== $code) {
        json_out(false, [], 'Invalid 2FA code.');
    }
    if (strtotime($user['two_factor_expires']) < time()) {
        json_out(false, [], 'Code has expired. Please login again.');
    }
    
    // Success
    $pdo->prepare("UPDATE users SET two_factor_code = NULL, two_factor_expires = NULL WHERE id = ?")->execute([$tid]);
    unset($_SESSION['temp_user_id']);
    unset($_SESSION['requires_2fa']);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    json_out(true, ['role' => $user['role']]);
}

/* ── SIGNUP ────────────────────────────────────────────── */
if ($action === 'signup') {
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        json_out(false, [], 'reCAPTCHA verification failed.');
    }
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password || !$confirm)
        json_out(false, [], 'All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        json_out(false, [], 'Invalid email address.');
    if ($password !== $confirm)
        json_out(false, [], 'Passwords do not match.');
    if (strlen($password) < 6)
        json_out(false, [], 'Password must be at least 6 characters.');

    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chk->execute([$email]);
    if ($chk->fetch()) json_out(false, [], 'Email already registered.');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins  = $pdo->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')"
    );
    $ins->execute([$name, $email, $hash]);
    json_out(true, [], 'Account created.');
}

/* ── FORGOT PASSWORD ───────────────────────────────────── */
if ($action === 'forgot_pw') {
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        json_out(false, [], 'reCAPTCHA verification failed.');
    }
    $email = trim($_POST['email'] ?? '');
    if (!$email) json_out(false, [], 'Email is required.');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Always return success to prevent email enumeration
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user['id']]);
            
        require_once __DIR__ . '/../includes/mailer.php';
        $reset_link = BASE_URL . "/reset.php?token=" . $token;
        $body = "<h2>Password Reset Request</h2><p>Click the link below to reset your password. It expires in 1 hour.</p><p><a href='$reset_link'>$reset_link</a></p>";
        send_mail($email, 'Password Reset', $body);
    }
    
    json_out(true, [], 'If that email exists, a reset link has been sent.');
}

/* ── RESET PASSWORD ────────────────────────────────────── */
if ($action === 'reset_pw') {
    $token    = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    
    if (!$token || !$password || !$confirm) json_out(false, [], 'All fields are required.');
    if ($password !== $confirm) json_out(false, [], 'Passwords do not match.');
    if (strlen($password) < 6) json_out(false, [], 'Password must be at least 6 characters.');
    
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) json_out(false, [], 'Invalid or expired token.');
    if (strtotime($user['reset_expires']) < time()) json_out(false, [], 'Token has expired.');
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
        ->execute([$hash, $user['id']]);
        
    json_out(true, [], 'Password has been successfully reset. You can now login.');
}

json_out(false, [], 'Unknown action.');
