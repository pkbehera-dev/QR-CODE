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

    $ip = $_SERVER['REMOTE_ADDR'];
    // Check for existing attempts in last 15 mins
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (ip_address = ? OR email = ?) AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$ip, $email]);
    if ($stmt->fetchColumn() >= 3) {
        json_out(false, [], 'Too many login attempts. Please try again after 15 minutes.');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Success: Clear attempts
        $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email = ?")->execute([$ip, $email]);
        if ($user['is_verified'] == 0) {
            $_SESSION['verify_email_id'] = $user['id'];
            json_out(false, ['requires_verification' => true], 'Please verify your email.');
        }

        if ($user['role'] === 'admin' || $user['two_factor_enabled'] == 1) {
            // Generate 6-digit 2FA code
            $code = sprintf("%06d", mt_rand(1, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $pdo->prepare("UPDATE users SET two_factor_code = ?, two_factor_expires = ? WHERE id = ?")
                ->execute([$code, $expires, $user['id']]);
            
            require_once __DIR__ . '/../includes/mailer.php';
            $body = "<h2>Admin Login Verification</h2><p>Your 2FA code is: <b style='font-size:1.5em;color:#2563eb'>$code</b></p><p>This code will expire in 15 minutes.</p>";
            $mail_result = send_mail($user['email'], 'Your Admin 2FA Code', $body);
            
            if (!$mail_result['success']) {
                json_out(false, [], "Failed to send 2FA code: " . $mail_result['message']);
            }
            
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
            $_SESSION['is_verified'] = 1;
            json_out(true, ['requires_verification' => false, 'role' => $user['role']]);
        }
    }
    // Record failure
    $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)")->execute([$ip, $email]);
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
    $_SESSION['is_verified'] = 1;
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
    
    // Generate 4-digit OTP
    $otp = sprintf("%04d", mt_rand(0, 9999));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $ins  = $pdo->prepare(
        "INSERT INTO users (name, email, password, role, is_verified, verification_otp, otp_expires) VALUES (?, ?, ?, 'user', 0, ?, ?)"
    );
    $ins->execute([$name, $email, $hash, $otp, $expires]);
    $new_id = $pdo->lastInsertId();

    require_once __DIR__ . '/../includes/mailer.php';
    $body = "<h2>Welcome to QAMS</h2><p>Your email verification OTP is: <b style='font-size:1.5em;color:#2563eb'>$otp</b></p><p>Please enter this code to complete your registration.</p>";
    $mail_result = send_mail($email, 'Verify Your Email', $body);

    if (!$mail_result['success']) {
        // Option 1: Fail the signup if mail fails
        // json_out(false, [], "Account created but " . $mail_result['message']);
        
        // Option 2: Just log it and proceed (but user won't get OTP)
        // For debugging, let's return error so user knows.
        json_out(false, [], "Account created but verification email failed to send: " . $mail_result['message']);
    }

    $_SESSION['verify_email_id'] = $new_id;
    json_out(true, [], 'Account created. Please verify your email.');
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
        $reset_link = BASE_URL . "/reset?token=" . $token;
        $body = "<h2>Password Reset Request</h2><p>Click the link below to reset your password. It expires in 1 hour.</p><p><a href='$reset_link'>$reset_link</a></p>";
        $mail_result = send_mail($email, 'Password Reset', $body);
        if (!$mail_result['success']) {
            json_out(false, [], "Failed to send reset email: " . $mail_result['message']);
        }
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

/* ── VERIFY EMAIL OTP ───────────────────────────────────── */
if ($action === 'verify_email') {
    $otp = trim($_POST['otp'] ?? '');
    $uid = $_SESSION['verify_email_id'] ?? null;
    if (!$uid || !$otp) json_out(false, [], 'Session expired or invalid OTP.');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!$user || $user['verification_otp'] !== $otp) {
        json_out(false, [], 'Invalid OTP.');
    }
    if (strtotime($user['otp_expires']) < time()) {
        json_out(false, [], 'OTP has expired. Please request a new one.');
    }

    $pdo->prepare("UPDATE users SET is_verified = 1, verification_otp = NULL, otp_expires = NULL WHERE id = ?")
        ->execute([$uid]);
    
    // Automatically log them in
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['is_verified'] = 1;
    unset($_SESSION['verify_email_id']);

    json_out(true, ['role' => $user['role']], 'Email verified successfully!');
}

/* ── RESEND VERIFICATION OTP ────────────────────────────── */
if ($action === 'resend_verification') {
    $uid = $_SESSION['verify_email_id'] ?? null;
    if (!$uid) json_out(false, [], 'Session expired. Please login again.');

    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) json_out(false, [], 'User not found.');

    $otp = sprintf("%04d", mt_rand(0, 9999));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $pdo->prepare("UPDATE users SET verification_otp = ?, otp_expires = ? WHERE id = ?")
        ->execute([$otp, $expires, $uid]);

    require_once __DIR__ . '/../includes/mailer.php';
    $body = "<h2>Email Verification</h2><p>Your NEW verification OTP is: <b style='font-size:1.5em;color:#2563eb'>$otp</b></p>";
    $mail_result = send_mail($user['email'], 'Verify Your Email (New OTP)', $body);

    if (!$mail_result['success']) {
        json_out(false, [], "Failed to send OTP: " . $mail_result['message']);
    }

    json_out(true, [], 'New OTP has been sent to your email.');
}

json_out(false, [], 'Unknown action.');
