<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['requires_2fa'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin 2FA Verification</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card card">
    <h1>Two-Factor Verification</h1>
    <p style="font-size:0.85rem; color:#555; margin-bottom:1rem; text-align:center;">
        A 6-digit code has been sent to your email. It expires in 15 minutes.
    </p>
    <div id="msg"></div>
    <form id="2fa-form" novalidate>
      <div class="form-group">
        <label for="code">Enter 6-Digit Code</label>
        <input type="text" id="code" name="code" required autofocus autocomplete="off" style="font-size:1.5rem; letter-spacing:4px; text-align:center; padding:12px;">
      </div>
      <button type="submit" class="btn btn-primary btn-block" id="verify-btn" style="margin-top:1rem; padding:12px;">Verify Code</button>
    </form>
    <div class="auth-sub"><a href="login.php">Cancel and return to login</a></div>
  </div>
</div>
<script>
document.getElementById('2fa-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('verify-btn');
  const msg = document.getElementById('msg');
  btn.disabled = true; btn.textContent = 'Verifying…';
  msg.innerHTML = '';
  
  const fd = new FormData(e.target);
  try {
    const res = await fetch('api/auth.php?action=verify_2fa', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      window.location.href = data.role === 'admin' ? 'admin/' : 'dashboard.php';
    } else {
      msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      btn.disabled = false; btn.textContent = 'Verify Code';
    }
  } catch(err) {
    msg.innerHTML = `<div class="alert alert-error">Network error. Try again.</div>`;
    btn.disabled = false; btn.textContent = 'Verify Code';
  }
});
</script>
</body>
</html>
