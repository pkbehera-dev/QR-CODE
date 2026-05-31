<?php 
http_response_code(404);
require_once __DIR__ . '/includes/public_header.php'; 
?>
<style>
    /* Premium Dark Mode Glassmorphism 404 Theme */
    body {
        background-color: #0b0f1a;
        color: #f8fafc;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Override default public header background if not dark by default */
    .public-header {
        background: rgba(11, 15, 26, 0.8) !important;
        backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .public-header .nav-links a { color: #f8fafc !important; }
    .public-header .nav-links a:hover { color: #00cfe8 !important; }

    .not-found-wrapper {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6rem 1.5rem 3rem 1.5rem;
        position: relative;
        overflow: hidden;
    }

    /* Ambient background glows */
    .ambient-glow-1 {
        position: absolute;
        top: 20%;
        left: 25%;
        width: 30vw;
        height: 30vw;
        background: radial-gradient(circle, rgba(0, 207, 232, 0.15) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(60px);
        animation: pulseGlow 8s infinite alternate ease-in-out;
        z-index: 0;
    }
    .ambient-glow-2 {
        position: absolute;
        bottom: 15%;
        right: 20%;
        width: 25vw;
        height: 25vw;
        background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(60px);
        animation: pulseGlow 6s infinite alternate-reverse ease-in-out;
        z-index: 0;
    }

    .not-found-container {
        max-width: 650px;
        width: 100%;
        z-index: 1;
        text-align: center;
    }

    /* Floating Art Container */
    .art-frame {
        position: relative;
        width: 220px;
        height: 220px;
        margin: 0 auto 2.5rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Outer Orbit Ring */
    .orbit-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 2px dashed rgba(0, 207, 232, 0.25);
        border-radius: 24px;
        animation: spinSlow 20s linear infinite;
    }

    /* Glass Code Box */
    .glass-qr-box {
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        animation: floatItem 6s ease-in-out infinite;
    }

    /* Broken QR Grid SVG */
    .qr-art {
        width: 80px;
        height: 80px;
        opacity: 0.8;
    }

    /* Animated Laser Scan Line */
    .laser-scanner {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, transparent, #00cfe8, transparent);
        box-shadow: 0 0 15px #00cfe8;
        animation: scanPass 2.5s ease-in-out infinite alternate;
    }

    /* Floating Error Badge */
    .floating-badge {
        position: absolute;
        bottom: -10px;
        right: -15px;
        background: #ef4444;
        color: #fff;
        font-weight: 800;
        font-size: 0.8rem;
        padding: 6px 14px;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        transform: rotate(-8deg);
        animation: badgeFloat 4s ease-in-out infinite alternate;
    }

    /* Typography */
    .not-found-container h1 {
        font-size: 5rem;
        font-weight: 900;
        line-height: 1;
        margin: 0;
        background: linear-gradient(135deg, #ffffff 30%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -2px;
    }

    .not-found-container h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #00cfe8;
        margin: 0.5rem 0 1rem 0;
        letter-spacing: -0.5px;
    }

    .not-found-container p {
        color: #94a3b8;
        font-size: 1.05rem;
        line-height: 1.6;
        margin-bottom: 2.5rem;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }

    .btn-action-group {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    .btn-gradient-cyan {
        background: linear-gradient(135deg, #00cfe8 0%, #009eb5 100%);
        color: #fff !important;
        font-weight: 700;
        padding: 12px 28px;
        border-radius: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 20px rgba(0, 207, 232, 0.3);
        transition: all 0.3s ease;
    }
    .btn-gradient-cyan:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(0, 207, 232, 0.5);
    }

    .btn-ghost-glass {
        background: rgba(255, 255, 255, 0.05);
        color: #cbd5e1 !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .btn-ghost-glass:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff !important;
    }

    /* Keyframe Animations */
    @keyframes spinSlow {
        100% { transform: rotate(360deg); }
    }
    @keyframes floatItem {
        0%, 100% { transform: translateY(0) rotate(0); }
        50% { transform: translateY(-12px) rotate(2deg); }
    }
    @keyframes badgeFloat {
        0% { transform: translateY(0) rotate(-8deg); }
        100% { transform: translateY(-6px) rotate(-4deg); }
    }
    @keyframes scanPass {
        0% { top: 5%; opacity: 0.3; }
        50% { opacity: 1; }
        100% { top: 95%; opacity: 0.3; }
    }
    @keyframes pulseGlow {
        0% { opacity: 0.4; transform: scale(0.9); }
        100% { opacity: 0.8; transform: scale(1.1); }
    }

    @media (max-width: 640px) {
        .not-found-container h1 { font-size: 4rem; }
        .art-frame { width: 180px; height: 180px; }
        .glass-qr-box { width: 120px; height: 120px; }
        .qr-art { width: 60px; height: 60px; }
        .btn-action-group { flex-direction: column; align-items: stretch; }
    }
</style>

<div class="not-found-wrapper">
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>
    
    <div class="not-found-container">
        
        <!-- Animated 3D Floating QR Sculpture -->
        <div class="art-frame">
            <div class="orbit-ring"></div>
            <div class="glass-qr-box">
                <!-- Decorative QR Code Vector Graphic -->
                <svg class="qr-art" viewBox="0 0 24 24" fill="none" stroke="#00cfe8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="#f8fafc"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="#f8fafc"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="#f8fafc"/>
                    <path d="M14 14h.01M17 14h.01M14 17h.01M20 14h.01M17 17h.01M20 17h.01M20 20h.01" stroke="#00cfe8" stroke-width="2.5"/>
                    <path d="M6 6h.01M17 6h.01M6 17h.01" stroke="#00cfe8" stroke-width="2.5"/>
                    <path d="M10 3v4M3 10h4M21 10h-4M10 21v-4" stroke="rgba(255,255,255,0.2)"/>
                </svg>
                <div class="laser-scanner"></div>
            </div>
            <div class="floating-badge">LINK BROKEN</div>
        </div>

        <h1>404</h1>
        <h2>Destination Unreachable</h2>
        <p>The asset label link or endpoint you are scanning could not be found. It may have been relocated, deleted, or entered incorrectly.</p>
        
        <div class="btn-action-group">
            <a href="<?= BASE_URL ?>/" class="btn-gradient-cyan">
                <i class="bi bi-house-door-fill"></i> Return Home
            </a>
            <a href="<?= BASE_URL ?>/login" class="btn-ghost-glass">
                Workspace Login
            </a>
        </div>

    </div>
</div>

<?php 
// Include custom site landing footer layout if available or generic site footer
?>
<footer style="margin-top: auto; padding: 2rem 0; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); color: #64748b; font-size: 0.85rem; z-index: 1;">
    <div class="container">
        &copy; <?= date('Y') ?> QAMS. High-Speed Asset Labeling Architecture.
    </div>
</footer>
</body>
</html>
