<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/db.php';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card card">
    <h1>Reset Password</h1>
    <p style="font-size:0.85rem; color:#555; margin-bottom:1rem; text-align:center;">
        Enter your email address and we'll send you a link to reset your password.
    </p>
    <div id="msg"></div>
    <form id="forgot-form" novalidate>
      <div class="form-group">
        <label for="email">Account Email</label>
        <input type="email" id="email" name="email" required autofocus autocomplete="email">
      </div>
      <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
      <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary btn-block" id="forgot-btn" style="margin-top:1rem;">Send Reset Link</button>
    </form>
    <div class="auth-sub"><a href="login.php">Back to Login</a></div>
  </div>
</div>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('forgot-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('forgot-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= defined("RECAPTCHA_SITE_KEY") ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey) grecaptcha.execute();
  else processForgot('');
});

function onRecaptchaSuccess(token) {
    processForgot(token);
}

async function processForgot(token) {
  const form = document.getElementById('forgot-form');
  const btn = document.getElementById('forgot-btn');
  const msg = document.getElementById('msg');
  msg.innerHTML = '';
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res = await fetch('api/auth.php?action=forgot_pw', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      msg.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
      form.reset();
    } else {
      msg.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch(err) {
    msg.innerHTML = `<div class="alert alert-error">Network error. Try again.</div>`;
    if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
  }
  btn.disabled = false; btn.textContent = 'Send Reset Link';
}
</script>
</body>
</html>
