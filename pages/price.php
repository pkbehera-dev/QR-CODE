<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Contact Support — QAMS';
require_once __DIR__ . '/../includes/public_header.php';
?>

<div style="background: #0b0f1a; min-height: calc(100vh - 70px); color: #fff; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="padding: 60px 20px; max-width: 800px;">
        <div class="support-card">
            <div class="glow-effect"></div>
            <div class="support-icon-wrap">
                <i class="bi bi-envelope-paper-heart"></i>
            </div>
            <h1 style="font-size: 3rem; font-weight: 900; margin-bottom: 1.5rem; line-height: 1.1; text-align: center;">
                Need <span style="color: #00ffff; text-shadow: 0 0 30px rgba(0,255,255,0.4);">Assistance?</span>
            </h1>
            <p style="color: #94a3b8; font-size: 1.2rem; text-align: center; max-width: 600px; margin: 0 auto 2.5rem; line-height: 1.6;">
                Whether you have questions, feedback, custom plan requests, or need technical support, our team is here to help you.
            </p>
            <div style="text-align: center; position: relative; z-index: 2;">
                <a href="mailto:support@pkbehera.in" class="btn btn-cyan support-btn">
                    <i class="bi bi-envelope-fill"></i> Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.support-card {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 28px;
    padding: 4rem 3rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.glow-effect {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0, 255, 255, 0.05) 0%, transparent 60%);
    z-index: 0;
    pointer-events: none;
}

.support-icon-wrap {
    text-align: center;
    font-size: 4rem;
    color: #00ffff;
    margin-bottom: 1.5rem;
    text-shadow: 0 0 20px rgba(0, 255, 255, 0.4);
    position: relative;
    z-index: 1;
}

.btn-cyan {
    background: #00ffff;
    color: #0b0f1a;
    font-weight: 800;
    padding: 16px 36px;
    border: none;
    border-radius: 14px;
    font-size: 1.1rem;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 255, 255, 0.2);
}

.btn-cyan:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 255, 255, 0.4);
    background: #ffffff;
    color: #0b0f1a;
}
</style>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>

