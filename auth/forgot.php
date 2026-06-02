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
                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                <?php endif; ?>
                
                <button type="submit" class="btn btn-cyan btn-block" id="forgot-btn" style="margin-top:1rem;">Send Reset Link</button>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Remembered your password? <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/enterprise.js?render=<?= RECAPTCHA_SITE_KEY ?>" async defer></script>
<script>
document.getElementById('forgot-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('forgot-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= defined("RECAPTCHA_SITE_KEY") ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
      grecaptcha.enterprise.ready(async () => {
          try {
              const token = await grecaptcha.enterprise.execute(siteKey, {action: 'FORGOT_PASSWORD'});
              processForgot(token);
          } catch (err) {
              console.error("reCAPTCHA Enterprise execution error:", err);
              processForgot('');
          }
      });
  } else {
      processForgot('');
  }
});

async function processForgot(token) {
  const form = document.getElementById('forgot-form');
  const btn = document.getElementById('forgot-btn');
  
  if (token) {
      document.getElementById('g-recaptcha-response').value = token;
  }
  
  const fd = new FormData(form);
  
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=forgot_pw', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      form.reset();
    } else {
      showToast(data.message, 'error');
    }
  } catch(err) {
    showToast('Network error.', 'error');
  }
  btn.disabled = false; btn.textContent = 'Send Reset Link';
}
</script>
</body>
</html>
