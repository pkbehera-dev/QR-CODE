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
<title>Login — QR Serial</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card card">
    <h1>Sign In</h1>
    <div id="msg"></div>
    <form id="login-form" novalidate>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email" autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
      <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary btn-block" id="login-btn">Login</button>
      <div style="text-align:center; margin-top:1rem; font-size:0.85rem;">
        <a href="forgot.php">Forgot Password?</a>
      </div>
    </form>
    <div class="auth-sub">No account? <a href="signup.php">Sign Up</a></div>
  </div>
</div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('login-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  // If reCAPTCHA is configured, execute it. Otherwise, submit immediately.
  const siteKey = '<?= (defined("RECAPTCHA_SITE_KEY") && RECAPTCHA_SITE_KEY) ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey && typeof grecaptcha !== 'undefined') {
      try {
          grecaptcha.execute();
      } catch (e) {
          console.error('reCAPTCHA error:', e);
          processLogin(''); // Fallback if execute fails
      }
  } else {
      processLogin('');
  }
});

function onRecaptchaSuccess(token) {
    processLogin(token);
}

async function processLogin(token) {
  const form = document.getElementById('login-form');
  const btn = document.getElementById('login-btn');
  const msg = document.getElementById('msg');
  msg.innerHTML = '';
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res = await fetch('api/auth.php?action=login', {method:'POST', body:fd});
    const text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        console.error('Invalid JSON response:', text);
        throw new Error('Invalid server response');
    }

    if (data.success) {
      if (data.requires_2fa) {
        window.location.href = '2fa.php';
      } else {
        window.location.href = data.role === 'admin' ? 'admin/' : 'dashboard.php';
      }
    } else {
      msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      btn.disabled = false; btn.textContent = 'Login';
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch(err) {
    console.error('Login Error:', err);
    msg.innerHTML = `<div class="alert alert-error">Network error or server error. Please check console for details.</div>`;
    btn.disabled = false; btn.textContent = 'Login';
    if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
  }
}
</script>
</body>
</html>
