<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login'); exit;
}

$users   = $pdo->query("SELECT id, name, email, role, serial_prefix, created_at, serial_limit, show_reference FROM users ORDER BY created_at DESC")->fetchAll();
$tusers  = count($users);
$tprod   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$tserial = $pdo->query("SELECT COUNT(*) FROM serials")->fetchColumn();
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
foreach ($stmt->fetchAll() as $r) $settings[$r['setting_key']] = $r['setting_value'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — QAMS</title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<div class="dashboard-layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="Logo">
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li><button onclick="showSection('overview')" id="nav-overview" class="active"><i class="bi bi-grid-fill"></i> Overview</button></li>
        <li><button onclick="showSection('users')" id="nav-users"><i class="bi bi-people-fill"></i> Users</button></li>
        <li><button onclick="showSection('pricing')" id="nav-pricing"><i class="bi bi-currency-dollar"></i> Pricing</button></li>
        <li><button onclick="showSection('settings')" id="nav-settings"><i class="bi bi-gear-fill"></i> Settings</button></li>
      </ul>
    </nav>

    <div class="sidebar-footer" style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05);">
        <button onclick="confirmLogout()" class="sidebar-logout-btn" style="display: flex; align-items: center; gap: 12px; width: 100%; padding: 12px 1.25rem; color: #ef4444; background: none; border: none; font-weight: 700; cursor: pointer; transition: all 0.2s; border-radius: 12px;">
            <i class="bi bi-box-arrow-left" style="font-size: 1.2rem;"></i>
            <span>Sign Out</span>
        </button>
    </div>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <h1 style="font-size:1.1rem; font-weight:700">Administrative Overview</h1>
      </div>
      <div class="topbar-actions">
        <div class="user-profile">
          <div class="user-avatar"><?= substr($_SESSION['name'], 0, 1) ?></div>
          <div class="user-info">
            <span class="name"><?= htmlspecialchars($_SESSION['name']) ?></span>
            <span class="role">System Administrator</span>
          </div>
        </div>
      </div>
    </header>

    <div class="container" style="padding-top: 1.5rem;">

    <!-- OVERVIEW SECTION -->
    <div id="section-overview" class="admin-section active">
      <div class="stats-grid" style="margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem;">
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); text-align: center;">
          <div class="stat-lbl" style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Total Users</div>
          <div class="stat-num" style="font-size: 2.2rem; font-weight: 900; color: var(--text-primary); margin-top: 0.5rem;"><?= $tusers ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); text-align: center;">
          <div class="stat-lbl" style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Total Products</div>
          <div class="stat-num" style="font-size: 2.2rem; font-weight: 900; color: var(--text-primary); margin-top: 0.5rem;"><?= $tprod ?></div>
        </div>
        <div class="stat-card" style="background: #fff; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); text-align: center;">
          <div class="stat-lbl" style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Total Serials</div>
          <div class="stat-num" style="font-size: 2.2rem; font-weight: 900; color: var(--text-primary); margin-top: 0.5rem;"><?= $tserial ?></div>
        </div>
      </div>

      <!-- SUBSCRIPTION REQUESTS -->
      <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem">
          <h2 style="font-size:1rem; margin:0">Subscription Requests</h2>
          <button class="btn btn-ghost btn-sm" onclick="loadSubscriptions()">Refresh</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>User</th><th>Email</th><th>Assets</th><th>Cycle</th><th>Amount</th><th>Txn ID</th><th>Proof</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody id="sub-tbody">
              <tr><td colspan="9" style="text-align:center; padding:2rem; color:#666">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- USERS SECTION -->
    <div id="section-users" class="admin-section" style="display:none">
      <div class="card">
        <h2 style="font-size:1rem;margin-bottom:1rem">User Management</h2>
        <div id="admin-msg"></div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Name</th><th>Email</th><th>Role</th><th>Prefix</th><th>Limit</th><th>Branding</th><th>Joined</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
              <tr id="urow-<?= $u['id'] ?>">
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] === 'admin' ? '<b>Admin</b>' : 'User' ?></td>
                <td><code><?= htmlspecialchars($u['serial_prefix'] ?: '—') ?></code></td>
                <td>
                  <input type="number" value="<?= $u['serial_limit'] ?>" 
                         style="width: 60px; padding: 4px;" 
                         onchange="updateUserLimit(<?= $u['id'] ?>, this.value)">
                </td>
                <td>
                  <input type="checkbox" <?= $u['show_reference'] ? 'checked' : '' ?> 
                         onchange="updateUserBranding(<?= $u['id'] ?>, this.checked)">
                </td>
                <td><?= htmlspecialchars(substr($u['created_at'],0,10)) ?></td>
                <td>
                  <?php if ($u['role'] !== 'admin'): ?>
                  <button class="btn btn-danger btn-sm"
                    onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'],ENT_QUOTES) ?>')"
                    aria-label="Delete <?= htmlspecialchars($u['name']) ?>">Delete</button>
                  <?php else: echo '<span style="color:#888;font-size:.8rem">—</span>'; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PRICING SECTION -->
    <div id="section-pricing" class="admin-section" style="display:none">
      <div class="card">
        <h2 style="font-size:1rem;margin-bottom:1rem">Pricing & Revenue Config</h2>
        <form id="pricing-form">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem">
            <div class="form-group" style="margin:0">
              <label>Rate Per Asset (Monthly) ₹</label>
              <input type="number" id="rate_monthly" step="0.01" min="0" value="<?= htmlspecialchars($settings['rate_per_asset_monthly'] ?? '10') ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label>Rate Per Asset (Yearly) ₹</label>
              <input type="number" id="rate_yearly" step="0.01" min="0" value="<?= htmlspecialchars($settings['rate_per_asset_yearly'] ?? '100') ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label>Branding Removal (Monthly) ₹</label>
              <input type="number" id="brand_monthly" step="0.01" min="0" value="<?= htmlspecialchars($settings['branding_removal_monthly'] ?? '499') ?>">
            </div>
            <div class="form-group" style="margin:0">
              <label>Branding Removal (Yearly) ₹</label>
              <input type="number" id="brand_yearly" step="0.01" min="0" value="<?= htmlspecialchars($settings['branding_removal_yearly'] ?? '4999') ?>">
            </div>
          </div>
          <div class="form-group"><label>UPI ID for Payments</label><input type="text" id="upi_id" placeholder="yourname@upi" value="<?= htmlspecialchars($settings['upi_id'] ?? 'pkbehera.in@slc') ?>"></div>
          
          <hr style="margin:2rem 0; border:0; border-top:1px solid #eee">
          <h3 style="font-size:0.9rem; margin-bottom:1rem">Landing Page Statistics</h3>
          <div style="display:grid; grid-template-columns:1fr 2fr; gap:1.5rem; margin-bottom:1.5rem">
            <div class="form-group"><label>Stat 1 Value</label><input type="text" id="stat_1_value" value="<?= htmlspecialchars($settings['stat_1_value'] ?? '500+') ?>"></div>
            <div class="form-group"><label>Stat 1 Label</label><input type="text" id="stat_1_label" value="<?= htmlspecialchars($settings['stat_1_label'] ?? 'Assets Managed') ?>"></div>
            
            <div class="form-group"><label>Stat 2 Value</label><input type="text" id="stat_2_value" value="<?= htmlspecialchars($settings['stat_2_value'] ?? '1000+') ?>"></div>
            <div class="form-group"><label>Stat 2 Label</label><input type="text" id="stat_2_label" value="<?= htmlspecialchars($settings['stat_2_label'] ?? 'Happy Users') ?>"></div>
            
            <div class="form-group"><label>Stat 3 Value</label><input type="text" id="stat_3_value" value="<?= htmlspecialchars($settings['stat_3_value'] ?? '99.9%') ?>"></div>
            <div class="form-group"><label>Stat 3 Label</label><input type="text" id="stat_3_label" value="<?= htmlspecialchars($settings['stat_3_label'] ?? 'Uptime') ?>"></div>
          </div>
          <button type="submit" class="btn btn-primary">Save Pricing & Stats</button>
        </form>
      </div>
    </div>

    <!-- SETTINGS SECTION -->
    <div id="section-settings" class="admin-section" style="display:none">
      <div class="card">
        <h2 style="font-size:1rem;margin-bottom:1rem">System Health & Configuration</h2>
        <div class="table-wrap">
          <table class="table-minimal">
            <thead>
              <tr><th>Component</th><th>Key</th><th>Status</th></tr>
            </thead>
            <tbody>
              <tr>
                <td><b>Database</b></td>
                <td><code>MYSQL_CONNECTION</code></td>
                <td><span style="color:green; font-weight:700">✔ Connected</span></td>
              </tr>
              <tr>
                <td><b>SMTP Mail</b></td>
                <td><code>SMTP_USER</code></td>
                <td><?= defined('SMTP_USER') && SMTP_USER ? '<span style="color:green">✔ Configured (' . htmlspecialchars(SMTP_USER) . ')</span>' : '<span style="color:red">✘ Missing</span>' ?></td>
              </tr>
              <tr>
                <td><b>reCAPTCHA</b></td>
                <td><code>RECAPTCHA_SITE_KEY</code></td>
                <td><?= defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY ? '<span style="color:green">✔ Configured</span>' : '<span style="color:red">✘ Missing</span>' ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div> <!-- Close container -->
  </main> <!-- Close main-content -->
</div> <!-- Close dashboard-layout -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';

function showSection(id) {
  document.querySelectorAll('.admin-section').forEach(s => s.style.display = 'none');
  document.getElementById('section-' + id).style.display = 'block';
  
  document.querySelectorAll('.sidebar-nav button').forEach(b => b.classList.remove('active'));
  document.getElementById('nav-' + id).classList.add('active');
  
  if (id === 'overview') loadSubscriptions();
}

function confirmLogout() {
    if (confirm('Are you sure you want to sign out?')) {
        window.location.href = BASE + '/logout';
    }
}

async function deleteUser(id, name) {
  if (!confirm('Delete user "' + name + '" and all their data?')) return;
  const r = await fetch(BASE + '/api/admin_delete_user', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id=' + id
  });
  const d = await r.json();
  if (d.success) {
    document.getElementById('urow-' + id)?.remove();
    showToast('User deleted.');
  } else showToast(d.message, 'error');
}

async function updateUserLimit(id, limit) {
  const r = await fetch(BASE + '/api/admin_update_user', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=update_limit&id=${id}&limit=${limit}`
  });
  const d = await r.json();
  if (d.success) showToast('Limit updated.');
  else showToast(d.message, 'error');
}

async function updateUserBranding(id, checked) {
  const r = await fetch(BASE + '/api/admin_update_user', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=update_branding&id=${id}&status=${checked ? 1 : 0}`
  });
  const d = await r.json();
  if (d.success) showToast('Branding updated.');
  else showToast(d.message, 'error');
}

// Pricing settings
document.getElementById('pricing-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const body = {
    rate_per_asset_monthly: document.getElementById('rate_monthly').value,
    rate_per_asset_yearly: document.getElementById('rate_yearly').value,
    branding_removal_monthly: document.getElementById('brand_monthly').value,
    branding_removal_yearly: document.getElementById('brand_yearly').value,
    upi_id: document.getElementById('upi_id').value,
    stat_1_value: document.getElementById('stat_1_value').value,
    stat_1_label: document.getElementById('stat_1_label').value,
    stat_2_value: document.getElementById('stat_2_value').value,
    stat_2_label: document.getElementById('stat_2_label').value,
    stat_3_value: document.getElementById('stat_3_value').value,
    stat_3_label: document.getElementById('stat_3_label').value
  };
  const r = await fetch(BASE + '/api/pricing', {
    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)
  });
  const d = await r.json();
  if (d.success) showToast(d.message);
  else showToast(d.message, 'error');
});

// Subscription management
async function loadSubscriptions() {
  const tbody = document.getElementById('sub-tbody');
  tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:2rem; color:#666">Loading...</td></tr>';

  // Load pending first, then others
  for (const status of ['pending', 'active', 'expired', 'rejected']) {
    const r = await fetch(BASE + `/api/subscriptions?admin=1&status=${status}`);
    const d = await r.json();
    if (status === 'pending') tbody.innerHTML = '';
    if (d.subscriptions && d.subscriptions.length) {
      d.subscriptions.forEach(s => {
        const statusBadge = {
          pending: '<span style="color:#f59e0b; font-weight:700">⏳ Pending</span>',
          active: '<span style="color:#10b981; font-weight:700">✔ Active</span>',
          expired: '<span style="color:#ef4444; font-weight:700">✘ Expired</span>',
          rejected: '<span style="color:#ef4444; font-weight:700">✘ Rejected</span>'
        }[s.status] || s.status;

        const actions = s.status === 'pending' ? `
          <button class="btn btn-primary btn-sm" onclick="handleSub(${s.id}, 'approve')">Approve</button>
          <button class="btn btn-danger btn-sm" onclick="handleSub(${s.id}, 'reject')" style="margin-left:4px">Reject</button>
        ` : '—';

        const txnId = s.transaction_id ? `<code style="font-size:0.8rem">${s.transaction_id}</code>` : '<span style="color:#aaa">—</span>';
        const proof = s.payment_proof ? `<a href="${BASE}/${s.payment_proof}" target="_blank" style="color:#2563eb; font-weight:600"><i class="bi bi-image"></i> View</a>` : '<span style="color:#aaa">—</span>';

        tbody.innerHTML += `<tr>
          <td>${s.user_name || '—'}</td>
          <td style="font-size:0.8rem">${s.user_email || '—'}</td>
          <td><b>${s.asset_limit}</b> <small style="color:#888">${s.billing_cycle}</small></td>
          <td>${s.billing_cycle}</td>
          <td><b>₹${parseFloat(s.amount).toFixed(0)}</b></td>
          <td>${txnId}</td>
          <td>${proof}</td>
          <td>${statusBadge}</td>
          <td>${actions}</td>
        </tr>`;
      });
    }
  }

  if (!tbody.innerHTML.trim()) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:2rem; color:#666">No subscription requests yet.</td></tr>';
  }
}

async function handleSub(id, action) {
  if (!confirm(`${action === 'approve' ? 'Approve' : 'Reject'} this subscription?`)) return;
  const r = await fetch(BASE + '/api/subscriptions', {
    method: 'PUT', headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ id, action })
  });
  const d = await r.json();
  if (d.success) { showToast(d.message); loadSubscriptions(); }
  else showToast(d.message, 'error');
}

loadSubscriptions();
</script>
</body>
</html>
