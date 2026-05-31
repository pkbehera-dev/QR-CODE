<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['requires_2fa'])) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}
$page_title = '2FA Verification — QAMS';
require_once __DIR__ . '/../includes/auth_header.php'; 
?>

<div class="auth-page">
    <div class="auth-container" style="max-width: 480px; grid-template-columns: 1fr;">
        <div class="auth-form-wrap dark-form" style="padding: 3rem 2.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= BASE_URL ?>">
                    <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo" style="height: 40px;">
                </a>
            </div>
            
            <h3 style="text-align: center;">Two-Factor Auth</h3>
            <p class="subtitle" style="text-align: center;">We've sent a 6-digit code to your email.</p>
            
            
            <form id="2fa-form" novalidate>
                <div class="form-group">
                    <label for="code" style="text-align: center; display: block;">Verification Code</label>
                    <input type="text" id="code" name="code" required autofocus autocomplete="off" maxlength="6" 
                           placeholder="000000"
                           style="font-size: 2rem; letter-spacing: 8px; text-align: center; padding: 15px; background: rgba(255,255,255,0.08); border-color: rgba(0,255,255,0.3);">
                </div>
                
                <button type="submit" class="btn btn-cyan btn-block" id="verify-btn" style="margin-top: 1.5rem;">Verify & Secure Access</button>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Having trouble? <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('2fa-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('verify-btn');
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const fd = new FormData(e.target);
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=verify_2fa', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast('2FA Verified! Redirecting...', 'success');
      setTimeout(() => {
        window.location.href = data.role === 'admin' ? '<?= BASE_URL ?>/admin/' : '<?= BASE_URL ?>/dashboard';
      }, 1000);
    } else {
      showToast(data.message, 'error');
      btn.disabled = false; btn.textContent = 'Verify & Secure Access';
    }
  } catch(err) {
    showToast('Connection failed.', 'error');
    btn.disabled = false; btn.textContent = 'Verify & Secure Access';
  }
});

// Auto-focus and numeric only
document.getElementById('code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length === 6) {
        // Optional: auto-submit
    }
});
</script>
</body>
</html>
