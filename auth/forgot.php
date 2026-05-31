<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Forgot Password — QAMS';
require_once __DIR__ . '/../includes/auth_header.php'; 
?>

<div class="auth-page">
    <div class="auth-container" style="max-width: 500px; grid-template-columns: 1fr;">
        <div class="auth-form-wrap dark-form" style="padding: 3rem 2.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= BASE_URL ?>">
                    <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo" style="height: 40px;">
                </a>
            </div>
            
            <h3 style="text-align: center;">Reset Password</h3>
            <p class="subtitle" style="text-align: center;">Enter your email to receive a reset link</p>
            
            <form id="forgot-form" novalidate>
                <div class="form-group">
                    <label for="email">Account Email</label>
                    <input type="email" id="email" name="email" required autofocus autocomplete="email" placeholder="name@company.com">
                </div>
                
                <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-cyan btn-block" id="forgot-btn" style="margin-top:1rem;">Send Reset Link</button>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Remembered your password? <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('forgot-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('forgot-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= defined("RECAPTCHA_SITE_KEY") ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey && typeof grecaptcha !== 'undefined') grecaptcha.execute();
  else processForgot('');
});

function onRecaptchaSuccess(token) { processForgot(token); }

async function processForgot(token) {
  const form = document.getElementById('forgot-form');
  const btn = document.getElementById('forgot-btn');
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=forgot_pw', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      form.reset();
    } else {
      showToast(data.message, 'error');
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch(err) {
    showToast('Network error.', 'error');
    if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
  }
  btn.disabled = false; btn.textContent = 'Send Reset Link';
}
</script>
</body>
</html>
