<?php require_once __DIR__ . '/includes/public_header.php'; ?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="bi bi-check-circle-fill"></i> Smart QR Labeling Platform
                </div>
                <h1>QR Asset Management <span>System</span></h1>
                <p>Generate, manage, and track asset labels with smart QR technology. The perfect solution for asset clarity.</p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/signup" class="btn btn-cyan">Generate QR</a>
                    <a href="#features" class="btn btn-outline-white">Explore Features</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="mockup-wrapper">
                    <img src="<?= BASE_URL ?>/assets/img/hero_mockup.jpg" alt="QAMS Tech Mockup">
                    <div class="scan-line"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dynamic Stats Section -->
    <?php
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'stat_%'");
    $stats = [];
    foreach($stmt->fetchAll() as $row) $stats[$row['setting_key']] = $row['setting_value'];
    
    // Fallbacks
    $s1v = $stats['stat_1_value'] ?? '500+';
    $s1l = $stats['stat_1_label'] ?? 'Assets Managed';
    $s2v = $stats['stat_2_value'] ?? '1000+';
    $s2l = $stats['stat_2_label'] ?? 'Happy Users';
    $s3v = $stats['stat_3_value'] ?? '99.9%';
    $s3l = $stats['stat_3_label'] ?? 'System Uptime';
    ?>
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid-public">
                <div class="glass-card">
                    <span class="stat-value"><?= htmlspecialchars($s1v) ?></span>
                    <span class="stat-label"><?= htmlspecialchars($s1l) ?></span>
                </div>
                <div class="glass-card">
                    <span class="stat-value"><?= htmlspecialchars($s2v) ?></span>
                    <span class="stat-label"><?= htmlspecialchars($s2l) ?></span>
                </div>
                <div class="glass-card">
                    <span class="stat-value"><?= htmlspecialchars($s3v) ?></span>
                    <span class="stat-label"><?= htmlspecialchars($s3l) ?></span>
                </div>
            </div>
        </div>
    </section>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
    <script src="<?= BASE_URL ?>/assets/js/landing.js" defer></script>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="features-header">
                <h2>Powerful <span>Features</span></h2>
                <p style="color: #94a3b8; font-size: 1.1rem;">Tailored tools engineered for absolute physical asset accountability.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                    <h3>Asset Registry</h3>
                    <p>Manage physical hardware and corporate product classifications within a sleek, centralized digital catalog.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-qr-code-scan"></i></div>
                    <h3>Smart QR Generation</h3>
                    <p>Instantly compile custom Global Serial Prefixes into unique, high-resolution code matrices.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-diagram-3"></i></div>
                    <h3>Sub-Component Registry</h3>
                    <p>Record nested hardware accessories, individual spec models, and accessory serials under a single master code.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-printer"></i></div>
                    <h3>Precision Multi-Grid Print</h3>
                    <p>Output custom 3-column sticker sheets calibrated down to the millimeter for standard adhesive label stock.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-phone"></i></div>
                    <h3>Mobile Telemetry Lookup</h3>
                    <p>Scan code identifiers from any smartphone camera to pull immediate equipment tracking assignments on screen.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-person-badge"></i></div>
                    <h3>Custodian Mapping & 2FA</h3>
                    <p>Assign exact corporate custodians and departments backed by multi-factor admin verification security.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="features-header">
                <h2>How It <span>Works</span></h2>
                <p style="color: #94a3b8; font-size: 1.1rem;">Four simple steps to total asset control.</p>
            </div>
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Register Assets</h3>
                    <p>Add your hardware details and specs to our high-speed digital registry.</p>
                </div>
                <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Generate QR</h3>
                    <p>Instantly create unique, high-resolution QR labels for every item.</p>
                </div>
                <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Print & Tag</h3>
                    <p>Print the optimized labels and attach them to your physical equipment.</p>
                </div>
                <div class="step-arrow"><i class="bi bi-arrow-right"></i></div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Scan & Track</h3>
                    <p>Scan anytime to view full history, maintenance logs, and current status.</p>
                </div>
            </div>
        </div>
    </section>



    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-grid">
                <div class="about-text">
                    <h2 style="font-size: 3rem; font-weight: 800; margin-bottom: 1.5rem;">
                        Smart & Simple <br><span style="color: #00ffff;">Asset Management</span>
                    </h2>
                    <p style="font-size: 1.15rem; line-height: 1.8; color: #cbd5e1; margin-bottom: 1.5rem;">
                        QAMS provides a clear, reliable system to label and manage your physical items. By pairing uniquely formatted QR codes with an accessible digital catalog, tracking inventory becomes effortless.
                    </p>
                    <p style="font-size: 1.15rem; line-height: 1.8; color: #94a3b8;">
                        Whether you are managing standard corporate equipment, physical components, or general catalog items, our platform helps keep everything neatly documented and organized in one central place.
                    </p>
                </div>
                <div class="about-visual" style="text-align: right; position: relative;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; height: 300px; background: radial-gradient(circle, rgba(0,255,255,0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                    <i class="bi bi-qr-code-scan" style="font-size: 13rem; color: #00ffff; opacity: 0.12; filter: drop-shadow(0 0 30px rgba(0,255,255,0.3)); position: relative; z-index: 2;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="contact-card">
                <h2>Get in <span>Touch</span></h2>
                <form onsubmit="event.preventDefault(); alert('Message sent! We will contact you soon.'); this.reset();">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label>Your Name</label>
                            <input type="text" required placeholder="John Doe" style="width: 100%;">
                        </div>
                        <div class="form-group">
                            <label>Work Email</label>
                            <input type="email" required placeholder="john@company.com" style="width: 100%;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label>Message</label>
                        <textarea rows="5" required placeholder="Tell us about your asset management needs..." style="width: 100%;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-cyan btn-block" style="padding: 18px; font-size: 1.1rem;">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Call To Action Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Simplify <br><span>Asset Tracking?</span></h2>
                <p style="font-size: 1.2rem; opacity: 0.9; margin-bottom: 3rem;">Join hundreds of teams managing their inventory with precision and speed.</p>
                <div class="hero-actions" style="justify-content: center;">
                    <a href="<?= BASE_URL ?>/signup" class="btn btn-cyan" style="background: #fff; color: #2563eb; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">Start Now</a>
                    <a href="<?= BASE_URL ?>/signup" class="btn btn-outline-white" style="border-width: 2px;">Generate QR</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
