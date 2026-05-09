<?php
// ==========================================
// SYSTEM CONFIGURATION
// Rename this file to config.php and update 
// the values below before running the app.
// ==========================================

// 1. Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'qr_serial_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// 2. SMTP Mail Settings (For Forgot Password, 2FA, etc.)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@yourdomain.com');

// 3. reCAPTCHA V2 Invisible Settings
define('RECAPTCHA_SITE_KEY', 'your-site-key-here');
define('RECAPTCHA_SECRET_KEY', 'your-secret-key-here');
