<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Reset Password — QAMS';
require_once __DIR__ . '/../includes/auth_header.php'; 

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<div class='auth-wrap'><div class='auth-card card'><div class='alert alert-error'>Invalid or missing reset token.</div></div></div>";
    require_once __DIR__ . '/../includes/public_footer.php';
    exit;
}
?>

<div class="auth-page">
    <div class="auth-container" style="max-width: 480px; grid-template-columns: 1fr;">
        <div class="auth-form-wrap dark-form" style="padding: 3rem 2.5rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="<?= BASE_URL ?>">
                    <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo" style="height: 40px;">
                </a>
            </div>
            
            <h3 style="text-align: center;">New Password</h3>
            <p class="subtitle" style="text-align: center;">Set a secure password for your account</p>
            
            <form id="reset-form" novalidate>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="password" name="password" required autofocus autocomplete="new-password" placeholder="••••••••" style="width: 100%; padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 12px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Toggle Password Visibility">
                            <i class="bi bi-eye-fill" style="font-size: 1.15rem;"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="••••••••" style="width: 100%; padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" style="position: absolute; right: 12px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;" title="Toggle Password Visibility">
                            <i class="bi bi-eye-fill" style="font-size: 1.15rem;"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-cyan btn-block" id="reset-btn" style="margin-top: 1rem;">Update Password</button>
            </form>
            
            <div id="login-link" style="display:none; text-align:center; margin-top:1.5rem;">
                <a href="<?= BASE_URL ?>/login" class="btn btn-ghost btn-block">Proceed to Login</a>
            </div>
            
            <div style="text-align:center; margin-top:2rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05); font-size:0.9rem; color: #94a3b8;">
                Remembered your password? <a href="<?= BASE_URL ?>/login" style="color: #00ffff; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('reset-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('reset-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  
  const fd = new FormData(e.target);
  try {
    const res = await fetch('<?= BASE_URL ?>/api/auth?action=reset_pw', {method:'POST', body:fd});
    const data = await res.json();
    if (data.success) {
      showToast(data.message, 'success');
      e.target.reset();
      document.getElementById('login-link').style.display = 'block';
      e.target.style.display = 'none';
    } else {
      showToast(data.message, 'error');
      btn.disabled = false; btn.textContent = 'Update Password';
    }
  } catch(err) {
    showToast('Network error. Try again.', 'error');
    btn.disabled = false; btn.textContent = 'Update Password';
  }
});

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
