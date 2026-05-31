<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = 'Pricing — QAMS';
require_once __DIR__ . '/../includes/public_header.php';

$is_logged_in = isset($_SESSION['user_id']);
?>

<div style="background: #0b0f1a; min-height: calc(100vh - 70px); color: #fff;">
    <div class="container" style="padding: 100px 20px;">

        <!-- HERO -->
        <div style="text-align: center; margin-bottom: 80px;">
            <div class="hero-badge">Pay As You Need</div>
            <h1 style="font-size: 4rem; font-weight: 900; margin-bottom: 1.5rem; line-height: 1.1;">
                Build <span style="color: #00ffff; text-shadow: 0 0 30px rgba(0,255,255,0.4);">Your Plan</span>
            </h1>
            <p style="color: #94a3b8; font-size: 1.25rem; max-width: 600px; margin: 0 auto;">
                Choose your asset limit and billing cycle. Prices are calculated in real-time based on your exact needs.
            </p>
        </div>

        <!-- CALCULATOR -->
        <div class="calc-card">
            <div class="glow-effect"></div>
            
            <div class="calc-grid">
                
                <!-- LEFT: CONFIGURATOR -->
                <div class="calc-left">
                    <!-- BILLING TOGGLE -->
                    <div class="billing-nav">
                        <button id="btn-monthly" class="billing-toggle active" onclick="setBilling('monthly')">
                            Monthly
                        </button>
                        <button id="btn-yearly" class="billing-toggle" onclick="setBilling('yearly')">
                            Yearly <span class="badge-save">SAVE</span>
                        </button>
                    </div>

                    <!-- ASSET COUNT -->
                    <div class="form-group mb-lg">
                        <label class="label-flex">
                            <span>Total Assets Needed</span>
                            <span id="qty-display" class="highlight-cyan">50</span>
                        </label>
                        <input type="range" id="calc-qty" min="10" max="1000" step="10" value="50" oninput="calculateTotal()" class="custom-range">
                        <div class="range-labels">
                            <span>10</span>
                            <span>250</span>
                            <span>500</span>
                            <span>750</span>
                            <span>1000</span>
                        </div>
                    </div>

                    <!-- OR CUSTOM INPUT -->
                    <div class="form-group mb-lg">
                        <label class="label-sub">Or enter exact number:</label>
                        <input type="number" id="calc-qty-input" value="50" min="10" step="1" oninput="syncSlider()" class="custom-input">
                    </div>

                    <!-- BRANDING TOGGLE -->
                    <div class="branding-box">
                        <div>
                            <h4 class="box-title">Remove Branding</h4>
                            <p class="box-desc">Hide "Developed by pkbehera" from scan pages</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="calc-branding" onchange="calculateTotal()">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- RATE INFO -->
                    <div class="rate-info">
                        <div class="rate-row">
                            <span>Per asset rate</span>
                            <span id="rate-display">₹10/asset</span>
                        </div>
                        <div class="rate-row" id="branding-rate-row" style="display:none;">
                            <span>Branding removal</span>
                            <span id="branding-rate-display">₹499</span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: PRICE DISPLAY & PAY -->
                <div class="calc-right">
                    <div class="price-sticky-box">
                        <div class="cycle-lbl" id="cycle-label">Monthly</div>
                        <div class="total-price" id="total-display">₹500</div>
                        <div class="breakdown" id="breakdown-text">50 assets × ₹10</div>
                        
                        <?php if ($is_logged_in): ?>
                        <button class="btn btn-cyan btn-pay" onclick="generatePaymentQR()" id="pay-btn">
                            <i class="bi bi-qr-code"></i> Generate Payment QR
                        </button>
                        <?php else: ?>
                        <a href="<?= BASE_URL ?>/login" class="btn btn-cyan btn-pay" style="display: inline-flex;">
                            <i class="bi bi-box-arrow-in-right"></i> Login to Subscribe
                        </a>
                        <?php endif; ?>
                        
                        <!-- PAYMENT QR & PROOF SECTION -->
                        <div id="payment-qr-wrap" class="payment-modal">
                            <p class="modal-title">Scan & Pay via UPI</p>
                            <div id="payment-qr" class="qr-container"></div>
                            <p class="qr-amount" id="qr-amount-label">₹500</p>
                            <p class="upi-id">UPI ID: <b id="upi-display">pkbehera.in@slc</b></p>
                            
                            <!-- PROOF SECTION -->
                            <div class="proof-section">
                                <p class="proof-title">
                                    <i class="bi bi-shield-check"></i> Verification Proof
                                </p>
                                
                                <div class="proof-field">
                                    <label class="field-label">Transaction / UTR ID</label>
                                    <input type="text" id="proof-txn-id" placeholder="e.g. 412345678901" class="proof-input">
                                </div>
                                
                                <div class="proof-field">
                                    <label class="field-label">Upload Screenshot</label>
                                    <div id="proof-drop-zone" class="drop-zone" onclick="document.getElementById('proof-file').click()">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <p>Click or drag screenshot</p>
                                    </div>
                                    <input type="file" id="proof-file" accept="image/*" style="display: none;" onchange="handleProofFile(this)">
                                    <div id="proof-preview" class="proof-preview">
                                        <img id="proof-img">
                                        <button onclick="removeProof()" class="btn-remove-proof"><i class="bi bi-x"></i></button>
                                    </div>
                                </div>

                                <button class="btn btn-cyan btn-submit-proof" id="submit-proof-btn" onclick="submitWithProof()">
                                    <i class="bi bi-send"></i> Submit Request
                                </button>
                            </div>

                            <div class="verification-notice">
                                <i class="bi bi-clock"></i>
                                Activated within <b>24 hours</b> after verification.
                            </div>
                        </div>

                        <!-- SUCCESS STATE -->
                        <div id="payment-submitted" class="success-box" style="display: none;">
                            <i class="bi bi-check-circle-fill"></i>
                            <h3>Request Submitted!</h3>
                            <p>Verification pending by admin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Layout */
.calc-card {
    max-width: 1100px; margin: 0 auto; padding: 3rem; 
    background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); 
    border-radius: 30px; position: relative; overflow: hidden;
}
.glow-effect {
    position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; 
    background: radial-gradient(circle, rgba(0,255,255,0.04) 0%, transparent 70%); z-index: 1;
}
.calc-grid {
    position: relative; z-index: 2; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem; align-items: start;
}

/* Nav & Toggles */
.billing-nav {
    display: flex; background: rgba(255,255,255,0.05); border-radius: 14px; padding: 4px; margin-bottom: 2rem; border: 1px solid rgba(255,255,255,0.08);
}
.billing-toggle {
    flex: 1; padding: 12px; border: none; border-radius: 10px; cursor: pointer;
    font-weight: 700; font-size: 0.9rem; background: transparent; color: #94a3b8; transition: all 0.3s;
}
.billing-toggle.active { background: #00ffff; color: #0b0f1a; box-shadow: 0 0 20px rgba(0, 255, 255, 0.3); }
.badge-save { background: #10b981; color: #fff; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; margin-left: 4px; }

/* Form Elements */
.mb-lg { margin-bottom: 2rem; }
.label-flex { color: #fff; font-size: 0.95rem; margin-bottom: 10px; display: flex; justify-content: space-between; }
.label-sub { color: #94a3b8; font-size: 0.8rem; margin-bottom: 8px; display: block; }
.highlight-cyan { color: #00ffff; font-weight: 900; }
.custom-range { width: 100%; accent-color: #00ffff; background: transparent; cursor: pointer; -webkit-appearance: none; height: 6px; border-radius: 3px; }
.range-labels { display: flex; justify-content: space-between; color: #64748b; font-size: 0.7rem; margin-top: 4px; }
.custom-input { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 15px; color: #fff; font-size: 1.1rem; font-weight: 700; }

/* Branding & Rate Boxes */
.branding-box { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: rgba(0,255,255,0.03); border: 1px solid rgba(0,255,255,0.1); border-radius: 16px; }
.box-title { margin: 0; color: #fff; font-size: 0.95rem; }
.box-desc { margin: 2px 0 0; font-size: 0.75rem; color: #94a3b8; }
.rate-info { margin-top: 1.5rem; padding: 15px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); }
.rate-row { display: flex; justify-content: space-between; color: #64748b; font-size: 0.8rem; margin-bottom: 5px; }

/* Right Column (Sticky) */
.price-sticky-box {
    text-align: center; padding: 2.5rem; background: rgba(0,0,0,0.3); border-radius: 24px; border: 1px solid rgba(0,255,255,0.15);
    position: sticky; top: 100px;
}
.cycle-lbl { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 0.25rem; }
.total-price { font-size: 4rem; font-weight: 900; color: #00ffff; line-height: 1; margin-bottom: 0.5rem; text-shadow: 0 0 25px rgba(0,255,255,0.3); }
.breakdown { color: #64748b; font-size: 0.8rem; margin-bottom: 1.5rem; }
.btn-pay { width: 100%; padding: 15px; font-size: 1rem; border-radius: 12px; }
.btn-pay i { margin-right: 8px; }

/* Payment Modal Style */
.payment-modal { margin-top: 1rem; background: #fff; padding: 1.25rem; border-radius: 18px; text-align: center; color: #0b0f1a; }
.modal-title { font-weight: 800; margin-bottom: 0.75rem; font-size: 0.95rem; }
.qr-container { display: flex; justify-content: center; margin-bottom: 0.25rem; }
.qr-container img { width: 160px; height: 160px; }
.qr-amount { font-weight: 800; margin: 0.25rem 0; font-size: 1.1rem; color: #0b0f1a; }
.upi-id { color: #64748b; font-size: 0.7rem; margin: 0; }

/* Proof Section */
.proof-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #e5e7eb; text-align: left; }
.proof-title { font-weight: 800; font-size: 0.85rem; margin-bottom: 0.75rem; color: #111; }
.proof-title i { color: #10b981; margin-right: 5px; }
.proof-field { margin-bottom: 0.75rem; }
.field-label { display: block; color: #374151; font-size: 0.7rem; font-weight: 700; margin-bottom: 4px; }
.proof-input { width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.85rem; background: #f8fafc; }
.drop-zone { border: 2px dashed #d1d5db; border-radius: 8px; padding: 0.75rem; text-align: center; cursor: pointer; background: #fafafa; transition: all 0.2s; }
.drop-zone:hover { border-color: #00ffff; background: #f0ffff; }
.drop-zone i { font-size: 1.25rem; color: #94a3b8; display: block; margin-bottom: 3px; }
.drop-zone p { color: #6b7280; font-size: 0.7rem; margin: 0; }
.btn-submit-proof { width: 100%; padding: 12px; font-size: 0.9rem; border-radius: 8px; margin-top: 0.5rem; }

/* Utils */
.verification-notice { margin-top: 1rem; padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-size: 0.75rem; text-align: center; }
.success-box { margin-top: 1.5rem; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 16px; text-align: center; }
.success-box i { font-size: 2.5rem; color: #10b981; display: block; margin-bottom: 0.5rem; }
.success-box h3 { color: #10b981; margin-bottom: 0.25rem; font-size: 1.1rem; }
.success-box p { color: #94a3b8; font-size: 0.8rem; margin: 0; }

.proof-preview { display: none; margin-top: 0.75rem; position: relative; }
.proof-preview img { width: 100%; border-radius: 10px; border: 1px solid #e5e7eb; }
.btn-remove-proof { position: absolute; top: 5px; right: 5px; background: #ef4444; color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

/* Responsive */
@media (max-width: 992px) {
    .calc-grid { grid-template-columns: 1fr; gap: 2rem; }
    .price-sticky-box { position: static; padding: 1.5rem; }
    .calc-card { padding: 1.5rem; border-radius: 20px; }
    .container { padding: 40px 15px; }
    h1 { font-size: 2.5rem !important; }
}
@media (max-width: 600px) {
    .total-price { font-size: 3rem; }
    .calc-left, .calc-right { padding: 0; }
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
let billing = 'monthly';
let rates = {};

// Fetch admin-set rates
async function loadRates() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/pricing');
        const data = await res.json();
        if (data.success) {
            rates = data.settings;
            calculateTotal();
        }
    } catch(e) {
        rates = {
            rate_per_asset_monthly: '10',
            rate_per_asset_yearly: '100',
            branding_removal_monthly: '499',
            branding_removal_yearly: '4999',
            upi_id: 'pkbehera.in@slc'
        };
        calculateTotal();
    }
}

function setBilling(cycle) {
    billing = cycle;
    document.getElementById('btn-monthly').classList.toggle('active', cycle === 'monthly');
    document.getElementById('btn-yearly').classList.toggle('active', cycle === 'yearly');
    document.getElementById('cycle-label').textContent = cycle === 'monthly' ? 'Monthly' : 'Yearly';
    calculateTotal();
}

function syncSlider() {
    const val = parseInt(document.getElementById('calc-qty-input').value) || 10;
    document.getElementById('calc-qty').value = Math.min(val, 1000);
    document.getElementById('qty-display').textContent = val;
    calculateTotal();
}

function calculateTotal() {
    const slider = document.getElementById('calc-qty');
    const input = document.getElementById('calc-qty-input');
    const qty = parseInt(input.value) || parseInt(slider.value) || 50;
    
    // Sync slider and input
    document.getElementById('qty-display').textContent = qty;
    if (document.activeElement !== input) input.value = qty;
    if (document.activeElement !== slider) slider.value = Math.min(qty, 1000);
    
    const branding = document.getElementById('calc-branding').checked;
    const rateKey = billing === 'monthly' ? 'rate_per_asset_monthly' : 'rate_per_asset_yearly';
    const brandKey = billing === 'monthly' ? 'branding_removal_monthly' : 'branding_removal_yearly';
    
    const perAsset = parseFloat(rates[rateKey] || 10);
    const brandPrice = parseFloat(rates[brandKey] || 499);
    
    let total = qty * perAsset;
    let breakdown = `${qty} assets × ₹${perAsset}`;
    
    if (branding) {
        total += brandPrice;
        breakdown += ` + ₹${brandPrice} branding`;
    }
    
    document.getElementById('total-display').textContent = '₹' + Math.round(total);
    document.getElementById('breakdown-text').textContent = breakdown;
    document.getElementById('rate-display').textContent = `₹${perAsset}/asset`;
    document.getElementById('branding-rate-display').textContent = `₹${brandPrice}`;
    
    if (rates.upi_id) {
        document.getElementById('upi-display').textContent = rates.upi_id;
    }
    
    // Dynamically calculate save percentage badge
    const mAssetRate = parseFloat(rates.rate_per_asset_monthly || 10);
    const yAssetRate = parseFloat(rates.rate_per_asset_yearly || 100);
    const mBrandRate = parseFloat(rates.branding_removal_monthly || 499);
    const yBrandRate = parseFloat(rates.branding_removal_yearly || 4999);
    
    let cost12Months = qty * mAssetRate * 12;
    let cost1Year = qty * yAssetRate;
    
    if (branding) {
        cost12Months += mBrandRate * 12;
        cost1Year += yBrandRate;
    }
    
    const badgeSave = document.querySelector('.badge-save');
    if (badgeSave && cost12Months > cost1Year && cost12Months > 0) {
        const pct = Math.round(((cost12Months - cost1Year) / cost12Months) * 100);
        badgeSave.textContent = `SAVE ${pct}%`;
    } else if (badgeSave) {
        badgeSave.textContent = 'SAVE';
    }
    
    // Update slider gradient
    const percent = ((Math.min(qty, 1000) - 10) / (1000 - 10)) * 100;
    slider.style.background = `linear-gradient(to right, #00ffff ${percent}%, rgba(255,255,255,0.1) ${percent}%)`;
    
    return total;
}

document.getElementById('calc-qty').addEventListener('input', function() {
    document.getElementById('calc-qty-input').value = this.value;
    document.getElementById('qty-display').textContent = this.value;
    calculateTotal();
});

function generatePaymentQR() {
    const total = calculateTotal();
    const upiId = rates.upi_id || 'pkbehera.in@slc';
    const qty = parseInt(document.getElementById('calc-qty-input').value) || 50;
    
    const wrap = document.getElementById('payment-qr-wrap');
    const qrDiv = document.getElementById('payment-qr');
    
    qrDiv.innerHTML = '';
    wrap.style.display = 'block';
    document.getElementById('qr-amount-label').textContent = '₹' + Math.round(total);
    
    const upiUrl = `upi://pay?pa=${upiId}&pn=QAMS&am=${Math.round(total)}&cu=INR&tn=QAMS%20${billing}%20${qty}%20assets`;
    
    new QRCode(qrDiv, {
        text: upiUrl, width: 200, height: 200,
        colorDark: "#0b0f1a", colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Proof upload handling
function handleProofFile(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        showToast('File too large. Max 5MB.', 'error');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('proof-img').src = e.target.result;
        document.getElementById('proof-preview').style.display = 'block';
        document.getElementById('proof-drop-zone').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeProof() {
    document.getElementById('proof-file').value = '';
    document.getElementById('proof-preview').style.display = 'none';
    document.getElementById('proof-drop-zone').style.display = 'block';
}

// Drag & drop support
const dropZone = document.getElementById('proof-drop-zone');
if (dropZone) {
    ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, (ev) => { ev.preventDefault(); dropZone.style.borderColor = '#00cfe8'; dropZone.style.background = '#f0fdfa'; }));
    ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, (ev) => { ev.preventDefault(); dropZone.style.borderColor = '#d1d5db'; dropZone.style.background = '#fafafa'; }));
    dropZone.addEventListener('drop', (ev) => {
        ev.preventDefault();
        const file = ev.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('proof-file');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            handleProofFile(input);
        }
    });
}

async function submitWithProof() {
    const total = calculateTotal();
    const qty = parseInt(document.getElementById('calc-qty-input').value) || 50;
    const branding = document.getElementById('calc-branding').checked ? 1 : 0;
    const txnId = document.getElementById('proof-txn-id').value.trim();
    const fileInput = document.getElementById('proof-file');
    const btn = document.getElementById('submit-proof-btn');
    
    // Validation: Require at least one
    if (!txnId && !fileInput.files[0]) {
        showToast('Please provide Transaction ID or upload a screenshot.', 'error');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split" style="margin-right:8px"></i> Submitting...';
    
    // Use FormData for file upload
    const fd = new FormData();
    fd.append('asset_limit', qty);
    fd.append('remove_branding', branding);
    fd.append('billing_cycle', billing);
    fd.append('amount', Math.round(total));
    if (txnId) fd.append('transaction_id', txnId);
    if (fileInput.files[0]) fd.append('payment_proof', fileInput.files[0]);
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/subscriptions', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.success) {
            showToast(d.message);
            document.getElementById('payment-qr-wrap').style.display = 'none';
            document.getElementById('payment-submitted').style.display = 'block';
            document.getElementById('pay-btn').disabled = true;
            document.getElementById('pay-btn').textContent = 'Request Submitted';
        } else {
            showToast(d.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send" style="margin-right:8px"></i> Submit Payment Request';
        }
    } catch(e) {
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send" style="margin-right:8px"></i> Submit Payment Request';
    }
}

loadRates();
</script>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
