<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/' : '/dashboard.php'));
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>QR Serial Manager</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="landing">
  <div class="site-logo" style="font-size:1.8rem;margin-bottom:.5rem">📦 QR Serial</div>
  <h1>Serial Number &amp; QR Management</h1>
  <p>Generate unique serial numbers, attach QR codes, and track your assets — fast and simple.</p>
  <div class="landing-actions">
    <a href="login.php" class="btn btn-primary">Login</a>
    <a href="signup.php" class="btn btn-ghost">Create Account</a>
  </div>
</div>
</body>
</html>
