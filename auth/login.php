<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . ($_SESSION['role'] === 'admin' ? '/admin/' : '/dashboard'));
    exit;
}
$page_title = 'Login — QAMS';
$hide_auth_header = true;
require_once __DIR__ . '/../includes/auth_header.php'; 
?>

<div class="auth-page">
    <div class="auth-container">
        <!-- Left Column: Info -->
        <div class="auth-info">
            <a href="<?= BASE_URL ?>" class="logo">
                <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo">
            </a>
            <h2>Welcome Back to the Future of Tracking.</h2>
            <p>Manage your hardware assets with precision and ease. Sign in to access your digital registry.</p>
            
            <ul class="auth-features">
                <li><i class="bi bi-shield-check"></i> Secure 2FA Protected Access</li>
                <li><i class="bi bi-lightning-charge"></i> High-Speed QR Generation</li>
                <li><i class="bi bi-graph-up"></i> Real-time Asset Analytics</li>
            </ul>
        </div>

        <!-- Right Column: Form -->
        <div class="auth-form-wrap dark-form">
            <h3>Sign In</h3>
            <p class="subtitle">Enter your credentials to continue</p>
            
            
            <form id="login-form" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email" autofocus placeholder="name@company.com">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••" style="width: 100%; padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 12px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Toggle Password Visibility">
                            <i class="bi bi-eye-fill" style="font-size: 1.15rem;"></i>
                        </button>
                    </div>
                </div>
                
                <?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY): ?>
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>" data-size="invisible" data-callback="onRecaptchaSuccess"></div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-cyan btn-block" id="login-btn">Sign In</button>
                
                <div style="text-align:center; margin-top:1.5rem; font-size:0.85rem;">
                    <a href="<?= BASE_URL ?>/forgot" style="color: #94a3b8;">Forgot Password?</a>
                </div>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Don't have an account? <a href="<?= BASE_URL ?>/signup" style="color: #00ffff; font-weight: 600;">Create Account</a>
            </div>
        </div>
    </div>
</div>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
document.getElementById('login-form').addEventListener('submit', e => {
  e.preventDefault();
  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const siteKey = '<?= (defined("RECAPTCHA_SITE_KEY") && RECAPTCHA_SITE_KEY) ? RECAPTCHA_SITE_KEY : "" ?>';
  if (siteKey && typeof grecaptcha !== 'undefined') {
      try {
          grecaptcha.execute();
      } catch (e) {
          processLogin('');
      }
  } else {
      processLogin('');
  }
});

function onRecaptchaSuccess(token) { processLogin(token); }

async function processLogin(token) {
  const form = document.getElementById('login-form');
  const btn = document.getElementById('login-btn');
  
  const fd = new FormData(form);
  if (token) fd.append('g-recaptcha-response', token);
  
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=login', {method:'POST', body:fd});
    const data = await res.json();

    if (data.success) {
      if (data.requires_2fa) {
        window.location.href = '<?= BASE_URL ?>/2fa';
      } else {
        showToast('Login successful! Redirecting...', 'success');
        setTimeout(() => {
          window.location.href = data.role === 'admin' ? '<?= BASE_URL ?>/admin/' : '<?= BASE_URL ?>/dashboard';
        }, 1000);
      }
    } else {
      if (data.requires_verification) {
          showToast(data.message + ' Redirecting...', 'error');
          setTimeout(() => window.location.href = '<?= BASE_URL ?>/verify-email', 1500);
          return;
      }
      showToast(data.message, 'error');
      btn.disabled = false; btn.textContent = 'Sign In';
      if (typeof grecaptcha !== 'undefined') grecaptcha.reset();
    }
  } catch(err) {
    showToast('Connection failed.', 'error');
    btn.disabled = false; btn.textContent = 'Sign In';
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
