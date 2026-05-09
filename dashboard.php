<?php
require_once __DIR__ . '/includes/auth_check.php';
$user_name = htmlspecialchars($_SESSION['name']);
$user_role = $_SESSION['role'];
if ($user_role === 'admin') { header('Location: ' . BASE_URL . '/admin/'); exit; }

// Get user details for settings prefill
$stmt = $pdo->prepare("SELECT email, role, serial_prefix, logo_url, company_heading, company_subheading, two_factor_enabled FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — QR Serial</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  #tab-print .serial-card{position:relative}
  .qr-box canvas,
  .qr-box img{display:block;margin:0 auto}
  .form-group-flex { display: flex; gap: 10px; align-items: flex-end; }
  
  /* Print Styles for Labels */
  @media screen {
    .unselected-for-print { opacity: 0.4; background: #f0f0f0 !important; }
  }
  
  @media print {
    @page { size: A4; margin: 0; }
    body { background: #fff; margin: 0; padding: 0; }
    .no-print, .site-header, .tabs, .stats-grid, .card:not(#tab-print .card) { display: none !important; }
    #tab-print, #tab-print .card, .print-grid { display: block !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
    
    .unselected-for-print { display: none !important; }
    
    .print-grid {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      gap: 0 !important;
      width: 210mm !important; /* A4 width */
    }
    
    .print-label {
      width: 105mm !important;
      height: 25mm !important; /* Increased for better scanning */
      border: 0.2mm solid #000 !important;
      box-sizing: border-box !important;
      padding: 2mm !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      overflow: hidden !important;
      background: #fff !important;
      page-break-inside: avoid !important;
      break-inside: avoid !important;
    }
    
    .label-content {
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 10mm !important;
    }
    
    .label-text {
      text-align: left !important;
    }
    
    .label-sn {
      font-size: 12pt !important;
      font-family: monospace !important;
      font-weight: bold !important;
      color: #000 !important;
    }
    
    .label-qr {
      width: 18mm !important; /* Larger for better scanning */
      height: 18mm !important;
    }
    .label-qr canvas,    .label-qr img {
      width: 18mm !important;
      height: 18mm !important;
    }
  }
  
  /* Modal Styles */
  .modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000;
    backdrop-filter: blur(2px);
  }
  .modal-overlay.active { display: flex; }
  .modal-card {
    background: #fff; width: 90%; max-width: 500px; padding: 2rem; border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-height: 90vh; overflow-y: auto;
  }
</style>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────────── -->
<header class="site-header no-print">
  <div class="container header-inner">
    <span class="site-logo">📦 QR Serial</span>
    <div class="header-meta">
      <span><?= $user_name ?></span> &nbsp;|&nbsp;
      <a href="logout.php">Logout</a>
    </div>
  </div>
</header>

<div id="toast-container"></div>

<!-- ── DASHBOARD ──────────────────────────────────────── -->
<main class="container" style="padding-top:1.5rem">

  <!-- Tabs nav -->
  <nav class="tabs no-print" role="tablist" aria-label="Dashboard tabs">
    <button class="tab-btn active" data-tab="overview"  role="tab" id="tbtn-overview" >Overview</button>
    <button class="tab-btn"        data-tab="products"  role="tab" id="tbtn-products" >Products</button>

    <button class="tab-btn"        data-tab="allserials" role="tab" id="tbtn-allserials">All Serials</button>
    <button class="tab-btn"        data-tab="create"    role="tab" id="tbtn-create"   >Create Serial</button>
    <button class="tab-btn"        data-tab="print"     role="tab" id="tbtn-print"    >Print / QR</button>
    <button class="tab-btn"        data-tab="settings"  role="tab" id="tbtn-settings" >Settings</button>
  </nav>

  <!-- ── OVERVIEW ─────────────────────────────────────── -->
  <section id="tab-overview" class="tab-content active" role="tabpanel">
    <div class="stats-grid" id="stats-grid">
      <div class="stat-card"><div class="stat-num" id="stat-products">…</div><div class="stat-lbl">Products</div></div>
      <div class="stat-card"><div class="stat-num" id="stat-serials">…</div><div class="stat-lbl">Serials Generated</div></div>
      <div class="stat-card"><div class="stat-num" id="stat-prefix">…</div><div class="stat-lbl">Serial Prefix</div></div>
    </div>
    <div class="card">
      <h2 style="font-size:1rem;margin-bottom:1rem">Recent Serials</h2>
      <div class="table-wrap">
        <table id="recent-table">
          <thead><tr><th>Serial Number</th><th>Product</th><th>Date</th><th>Action</th></tr></thead>
          <tbody id="recent-body"><tr><td colspan="4">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ── ALL SERIALS ──────────────────────────────────── -->
  <section id="tab-allserials" class="tab-content" role="tabpanel">
    <div class="card" style="margin-bottom:1rem">
      <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1; min-width:200px">
          <label for="all-search">Search Serials</label>
          <input type="text" id="all-search" placeholder="Search by SN or fields..." oninput="renderAllSerials()">
        </div>
        <div class="form-group" style="margin:0; width:180px">
          <label for="all-filter-prod">Filter Product</label>
          <select id="all-filter-prod" onchange="renderAllSerials()">
            <option value="">All Products</option>
          </select>
        </div>
        <button class="btn btn-ghost" onclick="resetAllFilters()">Reset</button>
      </div>
    </div>
    <div class="card">
      <h2 style="font-size:1rem;margin-bottom:1rem">Master List</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Serial Number</th><th>Product</th><th>Fields</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="all-serials-tbody"><tr><td colspan="5">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- ── PRODUCTS ──────────────────────────────────────── -->
  <section id="tab-products" class="tab-content" role="tabpanel">
    <div class="card" style="margin-bottom:1rem">
      <h2 style="font-size:1rem;margin-bottom:1rem">Add Product</h2>
      <div id="product-msg"></div>
      <form id="product-form" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" id="prod-id" value="">
        <div class="form-group" style="margin:0;flex:1;min-width:200px">
          <label for="prod-name">Product Name</label>
          <input type="text" id="prod-name" placeholder="e.g. DESKTOP" required>
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:120px">
          <label for="prod-short">Short Name (Prefix)</label>
          <input type="text" id="prod-short" placeholder="e.g. DESK" maxlength="10" required>
        </div>
        <button type="submit" class="btn btn-primary" id="prod-btn">Save Product</button>
        <button type="button" class="btn btn-ghost" id="prod-cancel-btn" style="display:none">Cancel</button>
      </form>
    </div>
    <div class="card">
      <h2 style="font-size:1rem;margin-bottom:1rem">Your Products</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>Short Name</th><th>Serials</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="product-tbody"><tr><td colspan="5">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </section>


  <!-- ── CREATE SERIAL ────────────────────────────────── -->
  <section id="tab-create" class="tab-content" role="tabpanel">
    <div class="card" style="max-width:500px;margin:0 auto">
      <h2 style="font-size:1.1rem;margin-bottom:1rem">Create New Serial</h2>
      <div id="create-msg"></div>
      
      <div class="form-group">
        <label for="create-product">Select Product</label>
        <select id="create-product" required>
          <option value="">Loading…</option>
        </select>
      </div>

      <div class="form-group" style="display:flex;gap:10px">
        <div style="flex:1">
          <label for="create-user">User (Compulsory)</label>
          <input type="text" id="create-user" placeholder="e.g. ARUP KUMAR MISHRA" required>
        </div>
        <div style="flex:1">
          <label for="create-desig">Designation</label>
          <input type="text" id="create-desig" placeholder="e.g. Security Incharge">
        </div>
      </div>
      
      <div class="form-group">
        <label for="create-dept">Department</label>
        <input type="text" id="create-dept" placeholder="e.g. Security Dept">
      </div>

      <div id="serial-preview-wrap" style="display:none;margin-bottom:1rem;padding:10px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
        <span style="font-size:.8rem;color:#64748b;display:block">Next Serial Preview:</span>
        <strong id="serial-preview" style="font-family:monospace;font-size:1.1rem;color:var(--p)"></strong>
      </div>

      <div style="margin-bottom:1.5rem">
        <label style="font-size:.9rem;font-weight:700;color:var(--p);display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;border-bottom:2px solid #eee;padding-bottom:5px;">
          Hardware Components
          <button type="button" class="btn btn-ghost btn-sm" onclick="addHardwareItem()">+ Add Item</button>
        </label>
        <div id="hardware-items-container"></div>
      </div>

      <button id="generate-btn" class="btn btn-primary" style="width:100%;font-size:1rem;padding:12px" disabled>Generate &amp; Save</button>

      <div id="generate-result" style="display:none;margin-top:1.5rem;text-align:center;padding-top:1.5rem;border-top:1px solid #eee">
        <div style="color:green;font-weight:600;margin-bottom:.5rem">Success!</div>
        <div id="result-serial" style="font-family:monospace;font-size:1.2rem;font-weight:bold;margin-bottom:1rem"></div>
        <div id="result-qr" class="qr-box"></div>
      </div>
    </div>
  </section>

  <!-- ── PRINT / QR ───────────────────────────────────── -->
  <section id="tab-print" class="tab-content" role="tabpanel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem" class="no-print">
      <div style="display:flex;gap:.5rem">
        <select id="filter-product" style="padding:6px;border-radius:6px;border:1px solid #ccc"><option value="">All Products</option></select>
        <button class="btn btn-ghost btn-sm" onclick="window.print()">Print This Page</button>
      </div>
      <button class="btn btn-ghost btn-sm" id="refresh-print-btn">↻ Refresh</button>
    </div>
    <div class="print-grid" id="print-grid"></div>
  </section>

  <!-- ── SETTINGS ─────────────────────────────────────── -->
  <section id="tab-settings" class="tab-content" role="tabpanel">
    <div class="card" style="max-width:500px">
      <h2 style="font-size:1.1rem;margin-bottom:1rem">Your Settings</h2>
      
      <form id="prefix-form" style="margin-bottom:2rem">
        <div class="form-group">
          <label for="prefix-input">Global Serial Prefix</label>
          <div style="display:flex;gap:10px">
            <input type="text" id="prefix-input" value="<?= htmlspecialchars($u['serial_prefix'] ?? '') ?>" placeholder="e.g. MYCO-">
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
          <small>Prepended to all new serials (e.g. <b>MYCO-</b>DESK-001)</small>
        </div>
        <div id="prefix-msg"></div>
      </form>

      <form id="heading-form" style="margin-bottom:2rem;border-top:1px solid #eee;padding-top:1.5rem">
        <div class="form-group">
          <label for="heading-input">Company Heading (Scan Page)</label>
          <input type="text" id="heading-input" value="<?= htmlspecialchars($u['company_heading'] ?? '') ?>" placeholder="e.g. PASUPATI AGROVET PVT LTD">
          <small>Shown at the top of the public scan page.</small>
        </div>
        <div class="form-group">
          <label for="subheading-input">Company Subheading (Unit/Plant Name)</label>
          <div style="display:flex;gap:10px">
            <input type="text" id="subheading-input" value="<?= htmlspecialchars($u['company_subheading'] ?? '') ?>" placeholder="e.g. RAIPUR UNIT (Plant-4)">
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
          <small>Shown just below the main heading.</small>
        </div>
        <div id="heading-msg"></div>
      </form>

      <form id="logo-url-form" style="margin-bottom:2rem;border-top:1px solid #eee;padding-top:1.5rem">
        <div class="form-group">
          <label for="logo-input">Company Logo (For Scan Page)</label>
          <div style="display:flex;gap:10px;margin-bottom:10px">
            <input type="url" id="logo-input" value="<?= htmlspecialchars($u['logo_url'] ?? '') ?>" placeholder="https://example.com/logo.png">
            <button type="submit" class="btn btn-primary">Save URL</button>
          </div>
          <label style="display:block;font-size:0.8rem;color:#666;margin-bottom:5px;">Or upload a logo directly:</label>
          <input type="file" id="logo-file-input" accept="image/png, image/jpeg, image/gif" style="font-size:0.85rem">
        </div>
        <div id="logo-preview-wrap" style="width:100px;height:100px;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;background:#fafafa">
            <?php if (!empty($u['logo_url'])): ?>
                <img src="<?= htmlspecialchars($u['logo_url']) ?>" style="max-width:100%; max-height:100%">
            <?php else: ?>
                <span style="font-size:.7rem; color:#aaa">No Logo</span>
            <?php endif; ?>
        </div>
        <div id="logo-msg" style="margin-top:10px"></div>
      </form>

      <form id="2fa-settings-form" style="margin-bottom:2rem;border-top:1px solid #eee;padding-top:1.5rem">
        <div class="form-group" style="display:flex; justify-content:space-between; align-items:center;">
          <div>
            <label style="margin-bottom:0">Two-Factor Authentication (2FA)</label>
            <p style="font-size:0.8rem; color:#666; margin:0">Require email code on login.</p>
          </div>
          <label class="switch">
            <input type="checkbox" id="2fa-toggle" <?= ($u['two_factor_enabled'] ?? 0) ? 'checked' : '' ?> <?= $u['role'] === 'admin' ? 'disabled' : '' ?>>
            <span class="slider round"></span>
          </label>
        </div>
        <div id="2fa-msg"></div>
        <?php if ($u['role'] === 'admin'): ?>
          <small style="color:#2563eb; font-weight:600">Note: 2FA is mandatory for Administrators.</small>
        <?php endif; ?>
      </form>

      <form id="email-form" style="margin-bottom:2rem;border-top:1px solid #eee;padding-top:1.5rem">
        <div class="form-group">
          <label for="email-input">Account Email</label>
          <div style="display:flex;gap:10px">
            <input type="email" id="email-input" value="<?= htmlspecialchars($u['email']) ?>" required>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </div>
        <div id="email-msg"></div>
      </form>

      <form id="pw-form" style="border-top:1px solid #eee;padding-top:1.5rem">
        <div class="form-group"><label>Current Password</label><input type="password" id="cur-pw" required></div>
        <div class="form-group"><label>New Password</label><input type="password" id="new-pw" required></div>
        <div class="form-group"><label>Confirm New</label><input type="password" id="con-pw" required></div>
        <button type="submit" class="btn btn-primary">Change Password</button>
        <div id="pw-msg" style="margin-top:10px"></div>
      </form>
    </div>
  </section>

</main>

<!-- Edit Serial Modal -->
<div class="modal-overlay" id="edit-serial-modal">
  <div class="modal-card">
    <h2 style="font-size:1.2rem; margin-bottom:1rem">Edit Serial Data</h2>
    <div style="margin-bottom:1rem; font-family:monospace; font-weight:bold; color:#2563eb;" id="edit-serial-sn"></div>
    <input type="hidden" id="edit-serial-id">
    
    <div class="form-group" style="display:flex;gap:10px">
      <div style="flex:1">
        <label for="edit-user">User (Compulsory)</label>
        <input type="text" id="edit-user" required>
      </div>
      <div style="flex:1">
        <label for="edit-desig">Designation</label>
        <input type="text" id="edit-desig">
      </div>
    </div>
    <div class="form-group">
      <label for="edit-dept">Department</label>
      <input type="text" id="edit-dept">
    </div>

    <div style="margin-bottom:1rem">
      <label style="font-size:.9rem;font-weight:700;color:var(--p);display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;border-bottom:2px solid #eee;padding-bottom:5px;">
        Hardware Components
        <button type="button" class="btn btn-ghost btn-sm" onclick="addEditHardwareItem()">+ Add Item</button>
      </label>
      <div id="edit-hardware-items-container"></div>
    </div>
    
    <div style="display:flex; gap:10px; margin-top:2rem; justify-content:flex-end">
      <button class="btn btn-ghost" onclick="closeEditSerialModal()">Cancel</button>
      <button class="btn btn-primary" id="save-edit-btn" onclick="saveSerialEdit()">Save Changes</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const BASE_URL = window.location.origin + '<?= BASE_URL ?>';
const SCAN_BASE = BASE_URL;

/* ═══════════════════════════════════════════════════════
   UTILS
═══════════════════════════════════════════════════════ */
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>'"]/g, 
    tag => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[tag] || tag)
  );
}
function msg(id, txt, type='success') {
  if (!txt) return; 
  showToast(txt, type);
}

function showToast(txt, type='success') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerText = txt;
  container.appendChild(toast);
  
  // Slide out and remove after 1.5s
  setTimeout(() => {
    toast.classList.add('toast-out');
    setTimeout(() => toast.remove(), 300);
  }, 1000); // 1 second display time as requested
}

async function api(endpoint, method='GET', body=null) {
  const opts = { method, headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  try {
    const r = await fetch(BASE_URL + '/' + endpoint, opts);
    return await r.json();
  } catch(e) { return {success:false, message:'Network error.'}; }
}

/* ═══════════════════════════════════════════════════════
   GLOBAL STATE
═══════════════════════════════════════════════════════ */
let State = {
  products: [],
  customFields: [],
  serials: [],
  userPrefix: '',
  loaded: false
};

async function initialLoad() {
  const [pd, sd, fd, setd] = await Promise.all([
    api('api/products.php'),
    api('api/serials.php'),
    api('api/custom_fields.php'),
    api('api/settings.php')
  ]);
  State.products = pd.products || [];
  State.serials = sd.serials || [];
  State.customFields = fd.custom_fields || [];
  State.userPrefix = setd.user?.serial_prefix || '';
  State.loaded = true;
  
  const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
  if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
}

/* ═══════════════════════════════════════════════════════
   TAB SWITCHING
═══════════════════════════════════════════════════════ */
const TAB_LOADERS = {
  overview: renderOverview,
  allserials: renderAllSerials,
  products: renderProductTable,
  create:   renderCreateProducts,
  print:    renderPrintGrid,
  settings: () => {}
};
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => {
      b.classList.remove('active'); b.setAttribute('aria-selected','false');
    });
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active'); btn.setAttribute('aria-selected','true');
    const tab = btn.dataset.tab;
    document.getElementById('tab-' + tab).classList.add('active');
    if (State.loaded && TAB_LOADERS[tab]) TAB_LOADERS[tab]();
  });
});

/* ═══════════════════════════════════════════════════════
   OVERVIEW
═══════════════════════════════════════════════════════ */
function renderOverview() {
  document.getElementById('stat-products').textContent = State.products.length;
  document.getElementById('stat-serials').textContent  = State.serials.length;
  document.getElementById('stat-prefix').textContent = State.userPrefix || '(not set)';

  const tbody = document.getElementById('recent-body');
  if (!State.serials.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="color:#888">No serials yet.</td></tr>'; return;
  }
  tbody.innerHTML = State.serials.slice(0, 10).map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user || '—';
    return `<tr><td style="font-family:monospace">${escHtml(s.serial_number)}</td>
         <td>${escHtml(userText)}</td>
         <td>${escHtml(s.product_name)}</td>
         <td>${escHtml(s.created_at ? s.created_at.slice(0,10) : '—')}</td>
         <td>
            <button class="btn btn-ghost btn-sm" onclick="openEditSerialModal(${s.id})">Edit</button>
         </td></tr>`;
  }).join('');
}

/* ── All Serials ────────────────────────────────────── */
function renderAllSerials() {
  const tbody = document.getElementById('all-serials-tbody');
  const search = document.getElementById('all-search').value.toLowerCase();
  const prodFilter = document.getElementById('all-filter-prod').value;

  // Populate filter dropdown
  const filterSel = document.getElementById('all-filter-prod');
  const currentVal = filterSel.value;
  filterSel.innerHTML = '<option value="">All Products</option>' +
    State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
  filterSel.value = currentVal;

  let list = State.serials;

  if (prodFilter) list = list.filter(s => String(s.product_id) === prodFilter);
  if (search) {
    list = list.filter(s => {
      const sn = s.serial_number.toLowerCase();
      const prod = s.product_name.toLowerCase();
      const fields = s.custom_fields ? JSON.stringify(s.custom_fields).toLowerCase() : '';
      return sn.includes(search) || prod.includes(search) || fields.includes(search);
    });
  }

  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="5" style="color:#888">${State.serials.length ? 'No matches found.' : 'No serials yet.'}</td></tr>`; return;
  }

  tbody.innerHTML = list.map(s => {
    const cf = s.custom_fields || {};
    const userText = cf.user ? `${cf.user} (${cf.department || ''})` : '—';
    const itemsCount = cf.items ? cf.items.length : 0;
    return `<tr>
      <td style="font-family:monospace;font-weight:600">${escHtml(s.serial_number)}</td>
      <td>${escHtml(userText)}</td>
      <td>${escHtml(s.product_name)}</td>
      <td style="font-size:0.75rem; color:#555">${itemsCount} Item(s)</td>
      <td>${escHtml(s.created_at ? s.created_at.slice(0,10) : '—')}</td>
      <td>
        <button class="btn btn-ghost btn-sm" onclick="openEditSerialModal(${s.id})">Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteSerial(${s.id})">✕</button>
      </td>
    </tr>`;
  }).join('');
}

function resetAllFilters() {
  document.getElementById('all-search').value = '';
  document.getElementById('all-filter-prod').value = '';
  renderAllSerials();
}

function openEditSerialModal(id) {
  const s = State.serials.find(x => x.id === id);
  if (!s) return;
  
  const modal = document.getElementById('edit-serial-modal');
  document.getElementById('edit-serial-id').value = s.id;
  document.getElementById('edit-serial-sn').textContent = s.serial_number;
  
  const cf = s.custom_fields || {};
  document.getElementById('edit-user').value = cf.user || '';
  document.getElementById('edit-desig').value = cf.designation || '';
  document.getElementById('edit-dept').value = cf.department || '';
  
  const container = document.getElementById('edit-hardware-items-container');
  container.innerHTML = '';
  
  if (cf.items && Array.isArray(cf.items)) {
    cf.items.forEach(item => addEditHardwareItem(item));
  }
  
  modal.classList.add('active');
}

function addHardwareItem(containerId = 'hardware-items-container', data = {}) {
  const container = document.getElementById(containerId);
  const div = document.createElement('div');
  div.className = 'hardware-item-row';
  div.style = 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:10px; margin-bottom:10px; position:relative;';
  
  const name = data.name || '';
  const details = data.details || '';
  const sn = data.sn || '';

  div.innerHTML = `
    <button type="button" class="btn btn-danger" style="position:absolute; top:5px; right:5px; padding:2px 6px; font-size:10px;" onclick="this.parentElement.remove()">✕</button>
    <div class="form-group" style="margin-bottom:5px;">
      <label style="font-size:0.75rem;">Asset Name</label>
      <input type="text" class="h-name" placeholder="e.g. HP PRODESK CPU" value="${escHtml(name)}" required>
    </div>
    <div class="form-group" style="margin-bottom:5px;">
      <label style="font-size:0.75rem;">Details</label>
      <input type="text" class="h-details" placeholder="e.g. Core i3, 8GB RAM" value="${escHtml(details)}">
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label style="font-size:0.75rem;">Device S/N</label>
      <input type="text" class="h-sn" placeholder="e.g. JPH845162Q" value="${escHtml(sn)}">
    </div>
  `;
  container.appendChild(div);
}

function addEditHardwareItem(data = {}) {
  addHardwareItem('edit-hardware-items-container', data);
}

async function saveSerialEdit() {
  const id = document.getElementById('edit-serial-id').value;
  const user = document.getElementById('edit-user').value;
  const designation = document.getElementById('edit-desig').value;
  const department = document.getElementById('edit-dept').value;
  
  const itemRows = document.querySelectorAll('#edit-hardware-items-container .hardware-item-row');
  const items = [];
  itemRows.forEach(row => {
      const name = row.querySelector('.h-name').value;
      const details = row.querySelector('.h-details').value;
      const sn = row.querySelector('.h-sn').value;
      if (name) items.push({name, details, sn});
  });

  const custom_fields = { user, designation, department, items };
  
  const btn = document.getElementById('save-edit-btn');
  btn.disabled = true;
  const d = await api('api/serials.php', 'PUT', {id, custom_fields});
  btn.disabled = false;
  
  if (d.success) {
      const s = State.serials.find(x => x.id == id);
      if (s) s.custom_fields = d.custom_fields;
      closeEditSerialModal();
      // Refresh current tab
      const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
      if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
      msg('create-msg', 'Serial updated.');
  } else {
      msg('any', d.message, 'error');
  }
}

function closeEditSerialModal() {
  document.getElementById('edit-serial-modal').classList.remove('active');
}

/* ═══════════════════════════════════════════════════════
   PRODUCTS
═══════════════════════════════════════════════════════ */
function renderProductTable() {
  const tbody = document.getElementById('product-tbody');
  if (!State.products.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="color:#888">No products yet.</td></tr>'; return;
  }
  tbody.innerHTML = State.products.map(p => `
    <tr>
      <td>${escHtml(p.name)}</td>
      <td><code>${escHtml(p.short_name)}</code></td>
      <td>${p.serial_count}</td>
      <td>${escHtml(p.created_at ? p.created_at.slice(0,10) : '—')}</td>
      <td>
        <button class="btn btn-ghost btn-sm" onclick="editProduct(${p.id})">Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})">Delete</button>
      </td>
    </tr>`).join('');
}

function editProduct(id) {
  const p = State.products.find(x => x.id === id);
  if (!p) return;
  document.getElementById('prod-id').value = p.id;
  document.getElementById('prod-name').value = p.name;
  document.getElementById('prod-short').value = p.short_name;
  document.getElementById('prod-btn').textContent = 'Update Product';
  document.getElementById('prod-cancel-btn').style.display = 'block';
  window.scrollTo(0,0);
}

document.getElementById('prod-cancel-btn').addEventListener('click', () => {
  document.getElementById('product-form').reset();
  document.getElementById('prod-id').value = '';
  document.getElementById('prod-btn').textContent = 'Save Product';
  document.getElementById('prod-cancel-btn').style.display = 'none';
});

document.getElementById('product-form').addEventListener('submit', async e => {
  e.preventDefault();
  const pid = document.getElementById('prod-id').value;
  const btn  = document.getElementById('prod-btn');
  const name  = document.getElementById('prod-name').value.trim();
  const short = document.getElementById('prod-short').value.trim().toUpperCase();
  
  msg('product-msg','');
  btn.disabled = true;
  
  const method = pid ? 'PUT' : 'POST';
  const payload = {name, short_name: short};
  if(pid) payload.id = parseInt(pid);

  const d = await api('api/products.php', method, payload);
  btn.disabled = false;
  if (d.success) {
    msg('product-msg', pid ? 'Product updated.' : 'Product added.', 'success');
    if(pid) {
        const idx = State.products.findIndex(x => x.id === parseInt(pid));
        if(idx !== -1) {
            State.products[idx].name = name;
            State.products[idx].short_name = short;
        }
    } else {
        State.products.unshift(d.product);
    }
    renderProductTable();
    document.getElementById('prod-cancel-btn').click();
  } else msg('product-msg', d.message, 'error');
});

async function deleteProduct(id) {
  if (!confirm('Delete this product and all its serials?')) return;
  const r = await fetch(BASE_URL + '/api/products.php', {
    method:'DELETE',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id=' + id
  });
  const d = await r.json();
  if (d.success) {
    State.products = State.products.filter(p => p.id !== id);
    State.serials = State.serials.filter(s => s.product_id !== id);
    renderProductTable();
    msg('product-msg', 'Product deleted.', 'success');
  } else msg('any', d.message, 'error');
}


/* ═══════════════════════════════════════════════════════
   CREATE SERIAL
═══════════════════════════════════════════════════════ */
function renderCreateProducts() {
  const sel  = document.getElementById('create-product');
  const currentVal = sel.value;
  sel.innerHTML = '<option value="">— choose product —</option>' +
    State.products.map(p => `<option value="${p.id}" data-short="${escHtml(p.short_name)}" data-count="${p.serial_count}">${escHtml(p.name)} (${escHtml(p.short_name)})</option>`).join('');
  if (currentVal) sel.value = currentVal;
  updatePreview();
}

document.getElementById('create-product').addEventListener('change', updatePreview);
function updatePreview() {
  const sel  = document.getElementById('create-product');
  const opt  = sel.selectedOptions[0];
  const wrap = document.getElementById('serial-preview-wrap');
  const genBtn = document.getElementById('generate-btn');
  
  if (!sel.value) {
    wrap.style.display = 'none'; genBtn.disabled = true; return;
  }
  const short = opt.dataset.short;
  const count = parseInt(opt.dataset.count || 0);
  const next  = String(count + 1).padStart(3, '0');
  document.getElementById('serial-preview').textContent = State.userPrefix + short + '-' + next;
  wrap.style.display = 'block';
  genBtn.disabled = false;
}

document.getElementById('generate-btn').addEventListener('click', async () => {
  const pid = document.getElementById('create-product').value;
  const user = document.getElementById('create-user').value.trim();
  const designation = document.getElementById('create-desig').value.trim();
  const department = document.getElementById('create-dept').value.trim();

  if (!pid || !user) {
    msg('create-msg', 'Product and User are compulsory.', 'error');
    return;
  }
  msg('create-msg','');
  
  let valid = true;
  const items = [];
  document.querySelectorAll('#hardware-items-container .hardware-item-row').forEach(row => {
    const name = row.querySelector('.h-name').value.trim();
    const details = row.querySelector('.h-details').value.trim();
    const sn = row.querySelector('.h-sn').value.trim();
    if (!name) valid = false;
    else items.push({name, details, sn});
  });

  if (!valid) {
    msg('create-msg', 'Please fill all Asset Names in Hardware Components.', 'error');
    return;
  }

  const custom_fields = { user, designation, department, items };

  document.getElementById('generate-btn').disabled = true;
  const d = await api('api/serials.php','POST',{product_id: parseInt(pid), custom_fields});
  document.getElementById('generate-btn').disabled = false;
  
  if (d.success) {
    const serial = d.serial;
    State.serials.unshift(serial);
    const prodIndex = State.products.findIndex(p => p.id === parseInt(pid));
    if (prodIndex !== -1) State.products[prodIndex].serial_count++;
    
    document.getElementById('result-serial').textContent = serial.serial_number;
    const qrDiv = document.getElementById('result-qr');
    qrDiv.innerHTML = '';
    new QRCode(qrDiv, {
      text: SCAN_BASE + '/scan.php?s=' + serial.id,
      width: 128, height: 128, correctLevel: QRCode.CorrectLevel.M
    });
    document.getElementById('generate-result').style.display = 'block';
    
    // reset fields
    document.getElementById('create-user').value = '';
    document.getElementById('create-desig').value = '';
    document.getElementById('create-dept').value = '';
    document.getElementById('hardware-items-container').innerHTML = '';
    renderCreateProducts(); 
  } else {
    msg('create-msg', d.message, 'error');
  }
});

/* ═══════════════════════════════════════════════════════
   PRINT / QR
═══════════════════════════════════════════════════════ */
function renderPrintGrid() {
  const fsel = document.getElementById('filter-product');
  const currentFilter = fsel.value;
  fsel.innerHTML = '<option value="">All Products</option>' +
    State.products.map(p => `<option value="${p.id}">${escHtml(p.name)}</option>`).join('');
  if (currentFilter) fsel.value = currentFilter;
  fsel.onchange = renderPrintGrid;

  const pid   = fsel.value;
  const list  = pid ? State.serials.filter(s => String(s.product_id) === pid) : State.serials;
  const grid  = document.getElementById('print-grid');
  if (!list.length) { grid.innerHTML = '<p style="color:#888">No serials yet.</p>'; return; }
  
  grid.innerHTML = `
    <div class="no-print" style="width:100%; margin-bottom:1rem; display:flex; gap:1rem; align-items:center;">
        <button class="btn btn-primary btn-sm" id="select-all-btn">Select All</button>
        <button class="btn btn-ghost btn-sm" id="deselect-all-btn">Deselect All</button>
        <span style="font-size:0.8rem; color:#666;">Only selected labels will be printed.</span>
    </div>
  `;
  
  list.forEach(s => {
    const isSelected = s.selected !== false;
    const card = document.createElement('div');
    card.className = 'serial-card print-label' + (isSelected ? '' : ' unselected-for-print');
    card.dataset.id = s.id;
    
    card.innerHTML = `
      <div class="no-print" style="position:absolute; top:2px; left:2px; z-index:10;">
        <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleSerialSelection(${s.id}, this.checked)">
      </div>
      
      <div class="label-content">
          <div class="label-text">
            <div class="label-sn">${escHtml(s.serial_number)}</div>
          </div>
          <div class="label-qr" id="qr-${s.id}"></div>
      </div>
    `;
    grid.appendChild(card);
    new QRCode(document.getElementById('qr-' + s.id), {
      text: SCAN_BASE + '/scan.php?s=' + s.id,
      width: 128, height: 128, correctLevel: QRCode.CorrectLevel.H
    });
  });

  document.getElementById('select-all-btn').onclick = () => {
      State.serials.forEach(s => s.selected = true);
      renderPrintGrid();
  };
  document.getElementById('deselect-all-btn').onclick = () => {
      State.serials.forEach(s => s.selected = false);
      renderPrintGrid();
  };
}

function toggleSerialSelection(id, checked) {
    const s = State.serials.find(x => x.id === id);
    if (s) {
        s.selected = checked;
        const el = document.querySelector(`.serial-card[data-id="${id}"]`);
        if (el) {
            if (checked) el.classList.remove('unselected-for-print');
            else el.classList.add('unselected-for-print');
        }
    }
}

async function deleteSerial(id) {
  if (!confirm('Delete this serial number?')) return;
  const r = await fetch(BASE_URL + '/api/serials.php', {
    method:'DELETE',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id=' + id
  });
  const d = await r.json();
  if (d.success) {
    State.serials = State.serials.filter(s => s.id !== id);
    // Refresh whatever tab is active
    const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
    if (TAB_LOADERS[activeTab]) TAB_LOADERS[activeTab]();
    msg('create-msg', 'Serial deleted.', 'success');
  } else msg('any', d.message, 'error');
}

document.getElementById('refresh-print-btn').addEventListener('click', async () => {
  const btn = document.getElementById('refresh-print-btn');
  btn.textContent = '↻ Refreshing...';
  await initialLoad();
  btn.textContent = '↻ Refresh';
});

/* ═══════════════════════════════════════════════════════
   SETTINGS
═══════════════════════════════════════════════════════ */
document.getElementById('prefix-form').addEventListener('submit', async e => {
  e.preventDefault();
  const prefix = document.getElementById('prefix-input').value;
  const d = await api('api/settings.php','POST',{action:'update_prefix', serial_prefix: prefix});
  msg('prefix-msg', d.message, d.success ? 'success' : 'error');
  if (d.success) State.userPrefix = prefix;
});

document.getElementById('heading-form').addEventListener('submit', async e => {
  e.preventDefault();
  const heading = document.getElementById('heading-input').value;
  const subheading = document.getElementById('subheading-input').value;
  const d = await api('api/settings.php','POST',{
      action:'update_heading', 
      company_heading: heading,
      company_subheading: subheading
  });
  msg('heading-msg', d.message, d.success ? 'success' : 'error');
});

document.getElementById('logo-url-form').addEventListener('submit', async e => {
  e.preventDefault();
  const logo = document.getElementById('logo-input').value;
  const d = await api('api/settings.php','POST',{action:'update_logo', logo_url: logo});
  msg('logo-msg', d.message, d.success ? 'success' : 'error');
  if (d.success) {
    updateLogoPreview(logo);
  }
});

document.getElementById('logo-file-input').addEventListener('change', async e => {
  if (!e.target.files.length) return;
  const file = e.target.files[0];
  const formData = new FormData();
  formData.append('logo_file', file);
  
  msg('logo-msg', 'Uploading...', 'success');
  try {
    const res = await fetch(BASE_URL + '/api/upload_logo.php', { method: 'POST', body: formData });
    const d = await res.json();
    if (d.success) {
      msg('logo-msg', d.message, 'success');
      updateLogoPreview(d.logo_url);
      document.getElementById('logo-input').value = d.logo_url;
    } else {
      msg('logo-msg', d.message, 'error');
    }
  } catch (err) {
    msg('logo-msg', 'Upload failed.', 'error');
  }
});

function updateLogoPreview(url) {
  const wrap = document.getElementById('logo-preview-wrap');
  if (url) {
    wrap.innerHTML = `<img src="${escHtml(url)}" style="max-width:100%; max-height:100%">`;
  } else {
    wrap.innerHTML = `<span style="font-size:.7rem; color:#aaa">No Logo</span>`;
  }
}
document.getElementById('2fa-toggle').addEventListener('change', async e => {
  const enabled = e.target.checked;
  const d = await api('api/settings.php', 'POST', {action: 'update_2fa', enabled: enabled});
  if (!d.success) {
      e.target.checked = !enabled; // Revert on failure
      msg('2fa-msg', d.message, 'error');
  } else {
      msg('2fa-msg', d.message, 'success');
  }
});

document.getElementById('email-form').addEventListener('submit', async e => {
  e.preventDefault();
  const email = document.getElementById('email-input').value;
  const d = await api('api/settings.php','POST',{action:'update_email', email});
  msg('email-msg', d.message, d.success ? 'success' : 'error');
});
document.getElementById('pw-form').addEventListener('submit', async e => {
  e.preventDefault();
  const d = await api('api/settings.php','POST',{
    action:'update_password',
    current_password:  document.getElementById('cur-pw').value,
    new_password:      document.getElementById('new-pw').value,
    confirm_password:  document.getElementById('con-pw').value
  });
  msg('pw-msg', d.message, d.success ? 'success' : 'error');
  if (d.success) e.target.reset();
});

/* ═══════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════ */
initialLoad();
</script>
</body>
</html>
