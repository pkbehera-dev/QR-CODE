<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Invalid or missing reset token.");
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card card">
    <h1>New Password</h1>
    <p style="font-size:0.85rem; color:#555; margin-bottom:1rem; text-align:center;">
        Please enter your new password below.
    </p>
    <div id="msg"></div>
    <form id="reset-form" novalidate>
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="form-group">
        <label for="password">New Password</label>
        <input type="password" id="password" name="password" required autofocus autocomplete="new-password">
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary btn-block" id="reset-btn" style="margin-top:1rem;">Reset Password</button>
    </form>
    <div class="auth-sub" id="login-link" style="display:none;"><a href="login.php">Proceed to Login</a></div>
  </div>
</div>
<script>
document.getElementById('reset-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('reset-btn');
  const msg = document.getElementById('msg');
  btn.disabled = true; btn.textContent = 'Saving…';
  msg.innerHTML = '';
  
  const fd = new FormData(e.target);
  try {
    const res = await fetch('api/auth.php?action=reset_pw', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
      e.target.reset();
      document.getElementById('login-link').style.display = 'block';
      e.target.style.display = 'none';
    } else {
      msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      btn.disabled = false; btn.textContent = 'Reset Password';
    }
  } catch(err) {
    msg.innerHTML = `<div class="alert alert-error">Network error. Try again.</div>`;
    btn.disabled = false; btn.textContent = 'Reset Password';
  }
});
</script>
</body>
</html>
