<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php'); exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Up — QR Serial</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card card">
    <h1>Create Account</h1>
    <div id="msg"></div>
    <form id="signup-form" novalidate>
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required autocomplete="name" autofocus>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
      </div>
      <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
      <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary btn-block" id="signup-btn">Create Account</button>
    </form>
    <div class="auth-sub">Already have an account? <a href="login.php">Login</a></div>
  </div>
</div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('signup-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('signup-btn');
  const msg = document.getElementById('msg');
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('confirm_password').value;
  msg.innerHTML = '';
  if (pw !== cpw) {
    msg.innerHTML = '<div class="alert alert-error">Passwords do not match.</div>'; return;
  }
  
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= defined("RECAPTCHA_SITE_KEY") ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey) grecaptcha.execute();
  else processSignup('');
});

function onRecaptchaSuccess(token) {
    processSignup(token);
}

async function processSignup(token) {
  const form = document.getElementById('signup-form');
  const btn = document.getElementById('signup-btn');
  const msg = document.getElementById('msg');
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res  = await fetch('api/auth.php?action=signup', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      msg.innerHTML = '<div class="alert alert-success">Account created! Redirecting…</div>';
      setTimeout(() => window.location.href = 'login.php', 1200);
    } else {
      msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      btn.disabled = false; btn.textContent = 'Create Account';
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch {
    msg.innerHTML = '<div class="alert alert-error">Network error.</div>';
    btn.disabled = false; btn.textContent = 'Create Account';
    if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
  }
}
</script>
</body>
</html>
