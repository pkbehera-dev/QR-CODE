<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard');
    exit;
}
if (!isset($_SESSION['verify_email_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}
$page_title = 'Verify Email — QAMS';
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
            
            <h3 style="text-align: center;">Verify Account</h3>
            <p class="subtitle" style="text-align: center;">Enter the 4-digit code sent to your inbox.</p>
            
            
            <form id="verify-form" novalidate>
                <div class="form-group">
                    <label for="otp" style="text-align: center; display: block;">4-Digit Activation Code</label>
                    <input type="text" id="otp" name="otp" required autofocus autocomplete="off" 
                           maxlength="4" pattern="\d{4}" placeholder="0000"
                           style="font-size: 2.5rem; letter-spacing: 12px; text-align: center; padding: 15px; background: rgba(255,255,255,0.08); border-color: rgba(0,255,255,0.3); color: #00ffff; font-weight: 800;">
                </div>
                
                <button type="submit" class="btn btn-cyan btn-block" id="verify-btn" style="margin-top: 1.5rem;">Verify & Activate</button>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <button type="button" id="resend-btn" class="btn btn-ghost btn-sm" style="background: transparent; color: #94a3b8; border-color: rgba(255,255,255,0.1);">Resend Code</button>
                </div>
            </form>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Back to <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('verify-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('verify-btn');
  const otp = document.getElementById('otp').value.trim();
  
  if (otp.length !== 4) {
      showToast('Enter 4 digits.', 'error');
      return;
  }
  
  btn.disabled = true; btn.textContent = 'Verifying…';
  
  const fd = new FormData();
  fd.append('otp', otp);
  
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=verify_email', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast('Email verified successfully!', 'success');
      setTimeout(() => {
          window.location.href = data.role === 'admin' ? '<?= BASE_URL ?>/admin/' : '<?= BASE_URL ?>/dashboard';
      }, 1500);
    } else {
      showToast(data.message, 'error');
      btn.disabled = false; btn.textContent = 'Verify & Activate';
    }
  } catch(err) {
    showToast('Network error.', 'error');
    btn.disabled = false; btn.textContent = 'Verify & Activate';
  }
});

document.getElementById('resend-btn').addEventListener('click', async e => {
    const btn = e.target;
    btn.disabled = true; btn.textContent = 'Resending...';
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/auth?action=resend_verification', {method:'POST'});
        const data = await res.json();
        showToast(data.message, data.success ? 'success' : 'error');
    } catch {
        showToast('Failed to resend.', 'error');
    }
    setTimeout(() => { btn.disabled = false; btn.textContent = 'Resend Code'; }, 30000);
});

// Numeric only
document.getElementById('otp').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
</body>
</html>
