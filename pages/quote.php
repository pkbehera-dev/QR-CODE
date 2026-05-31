<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Request a Quote — QAMS';
require_once __DIR__ . '/../includes/auth_header.php';

$selected_plan = $_GET['plan'] ?? '';
?>

<div class="container" style="padding: 60px 0;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div class="card" style="padding: 40px; border-radius: 20px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 20px; text-align: center;">Request a Custom Quote</h1>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Looking to increase your serial limits or remove branding? Fill out the form below and our team will get back to you shortly.
            </p>

            <form id="quote-form">
                <div class="form-group">
                    <label>Selected Plan / Interest</label>
                    <input type="text" value="<?= htmlspecialchars($selected_plan ?: 'Custom Plan') ?>" readonly style="background: #f8fafc;">
                </div>
                <div class="form-group">
                    <label>Estimated Monthly Serials Needed</label>
                    <select id="needed_limit" class="form-control">
                        <option value="100">Up to 100</option>
                        <option value="500">Up to 500</option>
                        <option value="1000">Up to 1,000</option>
                        <option value="unlimited">Unlimited / Enterprise</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message / Requirements</label>
                    <textarea id="message" style="width: 100%; height: 100px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;" placeholder="Tell us about your business..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 15px; font-size: 1.1rem; margin-top: 20px;">Submit Request</button>
            </form>
            <div id="quote-msg" style="margin-top: 20px; text-align: center;"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('quote-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const msg = document.getElementById('quote-msg');
    btn.disabled = true;
    btn.textContent = 'Submitting...';
    
    // In a real app, this would send an email or save to DB
    setTimeout(() => {
        msg.innerHTML = '<div class="alert alert-success">Your request has been sent! We will contact you at your registered email soon.</div>';
        btn.textContent = 'Request Submitted';
    }, 1500);
});
</script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
