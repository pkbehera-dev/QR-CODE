<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard'); exit;
}
$page_title = 'Sign Up — QAMS';
$hide_auth_header = true;
require_once __DIR__ . '/../includes/auth_header.php'; 
?>

<div class="auth-page">
    <div class="auth-container">
        <!-- Left Column: Benefits -->
        <div class="auth-info">
            <a href="<?= BASE_URL ?>" class="logo">
                <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo">
            </a>
            <h2>Join QAMS Today.</h2>
            <p>Ready to simplify your asset tracking? Start your journey with our enterprise-grade QR management platform.</p>
            
            <ul class="auth-features">
                <li><i class="bi bi-check-circle-fill"></i> Easy QR Labeling</li>
                <li><i class="bi bi-check-circle-fill"></i> Asset Tracking</li>
                <li><i class="bi bi-check-circle-fill"></i> Secure Management</li>
                <li><i class="bi bi-check-circle-fill"></i> Multi-User Support</li>
            </ul>
        </div>

        <!-- Right Column: Form -->
        <div class="auth-form-wrap dark-form">
            <h3>Create Account</h3>
            <p class="subtitle">Join the thousands managing assets smarter</p>
            
            
            <form id="signup-form" novalidate>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required autocomplete="name" autofocus placeholder="John Doe">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="name@company.com">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="Min. 6 characters" style="width: 100%; padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 12px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Toggle Password Visibility">
                            <i class="bi bi-eye-fill" style="font-size: 1.15rem;"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-type password" style="width: 100%; padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; right: 12px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Toggle Password Visibility">
                            <i class="bi bi-eye-fill" style="font-size: 1.15rem;"></i>
                        </button>
                    </div>
                </div>
                
                <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-cyan btn-block" id="signup-btn">Start Free Trial</button>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Already have an account? <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('signup-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('signup-btn');
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('confirm_password').value;
  
  if (pw !== cpw) {
    showToast('Passwords do not match.', 'error'); 
    return;
  }
  
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= defined("RECAPTCHA_SITE_KEY") ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey && typeof grecaptcha !== 'undefined') grecaptcha.execute();
  else processSignup('');
});

function onRecaptchaSuccess(token) { processSignup(token); }

async function processSignup(token) {
  const form = document.getElementById('signup-form');
  const btn = document.getElementById('signup-btn');
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res  = await fetch('<?= BASE_URL ?>/api/auth?action=signup', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast('Account created! Check your email…', 'success');
      setTimeout(() => window.location.href = '<?= BASE_URL ?>/verify-email', 1500);
    } else {
      showToast(data.message, 'error');
      btn.disabled = false; btn.textContent = 'Start Free Trial';
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch {
    showToast('Connection failed.', 'error');
    btn.disabled = false; btn.textContent = 'Start Free Trial';
    if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
  }
}

function togglePasswordVisibility(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        btn.style.color = '#00cfe8';
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        btn.style.color = '#94a3b8';
    }
}
</script>
</body>
</html>
