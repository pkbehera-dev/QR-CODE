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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="Logo">
      <button class="d-lg-none btn btn-ghost" onclick="toggleSidebar()" style="padding: 4px 8px; margin-left: auto; border: none; color: #fff;">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li><button onclick="showSection('overview')" id="nav-overview" class="active"><i class="bi bi-grid-fill"></i> <span>Overview</span></button></li>
        <li><button onclick="showSection('users')" id="nav-users"><i class="bi bi-people-fill"></i> <span>Users</span></button></li>
        <li><button onclick="showSection('settings')" id="nav-settings"><i class="bi bi-gear-fill"></i> <span>Settings</span></button></li>
      </ul>
    </nav>

    <div class="sidebar-footer">
        <button onclick="confirmLogout()" class="sidebar-logout-btn">
            <i class="bi bi-box-arrow-left"></i>
            <span>Sign Out</span>
        </button>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <header class="topbar">
      <div class="topbar-left" style="display: flex; align-items: center; gap: 1rem;">
        <button class="d-lg-none btn btn-ghost" onclick="toggleSidebar()" style="padding: 6px 10px; border: none;">
          <i class="bi bi-list" style="font-size: 1.5rem;"></i>
        </button>
        <h1 style="font-size:1.25rem; font-weight:800; letter-spacing: -0.5px;">Administrative Overview</h1>
      </div>
      <div class="topbar-actions">
        <div class="user-profile">
          <div class="user-avatar"><?= substr($_SESSION['name'], 0, 1) ?></div>
          <div class="user-info d-none d-sm-block">
            <span class="name"><?= htmlspecialchars($_SESSION['name']) ?></span>
            <span class="role">System Administrator</span>
          </div>
        </div>
      </div>
    </header>

    <div class="container" style="padding-top: 1.5rem;">
      <div id="section-container" class="section-container">
        
        <!-- OVERVIEW SECTION -->
        <div id="section-overview" class="admin-section active">
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
              <div class="stat-details">
                <div class="stat-lbl">Total Users</div>
                <div class="stat-num"><?= $tusers ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="bi bi-box-seam-fill"></i></div>
              <div class="stat-details">
                <div class="stat-lbl">Total Products</div>
                <div class="stat-num"><?= $tprod ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="bi bi-qr-code"></i></div>
              <div class="stat-details">
                <div class="stat-lbl">Total Serials</div>
                <div class="stat-num"><?= $tserial ?></div>
              </div>
            </div>
          </div>

          <!-- SUBSCRIPTION REQUESTS -->
          <div class="card premium-card">
            <div class="card-header">
              <h2>Subscription Requests</h2>
              <button class="btn btn-ghost btn-sm" onclick="loadSubscriptions()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>User</th><th>Email</th><th>Assets</th><th>Cycle</th><th>Amount</th><th>Txn ID</th><th>Proof</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody id="sub-tbody">
                  <tr><td colspan="9" style="text-align:center; padding:3rem; color:#888;">Loading subscriptions...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- USERS SECTION -->
        <div id="section-users" class="admin-section">
          <div class="card premium-card">
            <div class="card-header">
              <h2>User Management</h2>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Name</th><th>Email</th><th>Role</th><th>Prefix</th><th>Limit</th><th>Branding</th><th>Joined</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                  <tr id="urow-<?= $u['id'] ?>">
                    <td style="font-weight: 600;"><?= htmlspecialchars($u['name']) ?></td>
                    <td style="color: var(--text-secondary);"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                      <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge badge-admin">Admin</span>
                      <?php else: ?>
                        <span class="badge badge-user">User</span>
                      <?php endif; ?>
                    </td>
                    <td><code class="code-badge"><?= htmlspecialchars($u['serial_prefix'] ?: '—') ?></code></td>
                    <td>
                      <input type="number" value="<?= $u['serial_limit'] ?>" class="inline-input" onchange="updateUserLimit(<?= $u['id'] ?>, this.value)">
                    </td>
                    <td>
                      <label class="switch">
                        <input type="checkbox" <?= $u['show_reference'] ? 'checked' : '' ?> onchange="updateUserBranding(<?= $u['id'] ?>, this.checked)">
                        <span class="slider"></span>
                      </label>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars(substr($u['created_at'],0,10)) ?></td>
                    <td>
                      <?php if ($u['role'] !== 'admin'): ?>
                      <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'],ENT_QUOTES) ?>')"><i class="bi bi-trash"></i></button>
                      <?php else: echo '<span style="color:#cbd5e1;">—</span>'; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>


        <!-- SETTINGS SECTION -->
        <div id="section-settings" class="admin-section">
          <div class="card premium-card">
            <div class="card-header">
              <h2>System Health</h2>
            </div>
            <div class="table-wrap">
              <table class="table-minimal">
                <thead>
                  <tr><th>Component</th><th>Key</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <tr>
                    <td style="font-weight:600;">Database</td>
                    <td><code class="code-badge">MYSQL_CONNECTION</code></td>
                    <td><span class="status-badge status-green"><i class="bi bi-check-circle-fill"></i> Connected</span></td>
                  </tr>
                  <tr>
                    <td style="font-weight:600;">SMTP Mail</td>
                    <td><code class="code-badge">SMTP_USER</code></td>
                    <td><?= defined('SMTP_USER') && SMTP_USER ? '<span class="status-badge status-green"><i class="bi bi-check-circle-fill"></i> ' . htmlspecialchars(SMTP_USER) . '</span>' : '<span class="status-badge status-red"><i class="bi bi-x-circle-fill"></i> Missing</span>' ?></td>
                  </tr>
                  <tr>
                    <td style="font-weight:600;">reCAPTCHA</td>
                    <td><code class="code-badge">RECAPTCHA_SITE_KEY</code></td>
                    <td><?= defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY ? '<span class="status-badge status-green"><i class="bi bi-check-circle-fill"></i> Configured</span>' : '<span class="status-badge status-red"><i class="bi bi-x-circle-fill"></i> Missing</span>' ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div> <!-- Close section-container -->
    </div> <!-- Close container -->
  </main> <!-- Close main-content -->
</div> <!-- Close dashboard-layout -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';

function confirmLogout() {
    if (confirm('Are you sure you want to sign out?')) {
        window.location.href = BASE + '/logout';
    }
}

// Fast section switching with fade effect
function showSection(id) {
  // Hide all sections with fade out
  const sections = document.querySelectorAll('.admin-section');
  sections.forEach(s => {
    s.classList.remove('fade-in');
    s.classList.remove('active');
  });
  
  // Show target section with fade in
  const target = document.getElementById('section-' + id);
  if(target) {
    target.classList.add('active');
    // Small timeout to ensure display applies before opacity transition
    setTimeout(() => {
      target.classList.add('fade-in');
    }, 10);
  }
  
  // Update nav active state
  document.querySelectorAll('.sidebar-nav button').forEach(b => b.classList.remove('active'));
  const activeNav = document.getElementById('nav-' + id);
  if(activeNav) activeNav.classList.add('active');
  
  // Close sidebar on mobile after clicking
  if(window.innerWidth <= 1024) {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
  }
  
  // Load specific data if needed
  if (id === 'overview') loadSubscriptions();
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
  document.getElementById('sidebarOverlay').classList.toggle('active');
}

async function deleteUser(id, name) {
  if (!confirm('Delete user "' + name + '" and all their data?')) return;
  const r = await fetch(BASE + '/api/admin_delete_user', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id=' + id
  });
  const d = await r.json();
  if (d.success) {
    const row = document.getElementById('urow-' + id);
    if(row) {
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 300);
    }
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


// Subscription management
async function loadSubscriptions() {
  const tbody = document.getElementById('sub-tbody');
  tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:3rem; color:#888;">Loading subscriptions...</td></tr>';

  let hasData = false;
  let html = '';

  for (const status of ['pending', 'active', 'expired', 'rejected']) {
    const r = await fetch(BASE + `/api/subscriptions?admin=1&status=${status}`);
    const d = await r.json();
    
    if (d.subscriptions && d.subscriptions.length) {
      hasData = true;
      d.subscriptions.forEach(s => {
        let statusBadge = '';
        if(s.status === 'pending') statusBadge = '<span class="status-badge status-yellow"><i class="bi bi-hourglass-split"></i> Pending</span>';
        else if(s.status === 'active') statusBadge = '<span class="status-badge status-green"><i class="bi bi-check-circle-fill"></i> Active</span>';
        else statusBadge = `<span class="status-badge status-red"><i class="bi bi-x-circle-fill"></i> ${s.status.charAt(0).toUpperCase() + s.status.slice(1)}</span>`;

        const actions = s.status === 'pending' ? `
          <div style="display:flex; gap:6px;">
            <button class="btn btn-primary btn-sm" onclick="handleSub(${s.id}, 'approve')" style="padding: 4px 8px;"><i class="bi bi-check2"></i></button>
            <button class="btn btn-danger btn-sm" onclick="handleSub(${s.id}, 'reject')" style="padding: 4px 8px;"><i class="bi bi-x-lg"></i></button>
          </div>
        ` : '<span style="color:#cbd5e1;">—</span>';

        const txnId = s.transaction_id ? `<code class="code-badge">${s.transaction_id}</code>` : '<span style="color:#aaa">—</span>';
        const proof = s.payment_proof ? `<a href="${BASE}/${s.payment_proof}" target="_blank" class="proof-link"><i class="bi bi-image"></i> View</a>` : '<span style="color:#aaa">—</span>';

        html += `<tr>
          <td style="font-weight:600;">${s.user_name || '—'}</td>
          <td style="font-size:0.85rem; color:var(--text-secondary);">${s.user_email || '—'}</td>
          <td><b>${s.asset_limit}</b></td>
          <td><span class="badge badge-cycle">${s.billing_cycle}</span></td>
          <td style="font-weight:800; color:var(--text-primary);">₹${parseFloat(s.amount).toFixed(0)}</td>
          <td>${txnId}</td>
          <td>${proof}</td>
          <td>${statusBadge}</td>
          <td>${actions}</td>
        </tr>`;
      });
    }
  }

  tbody.innerHTML = hasData ? html : '<tr><td colspan="9" style="text-align:center; padding:3rem; color:#888;">No subscription requests found.</td></tr>';
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

// Initial initialization
document.addEventListener('DOMContentLoaded', () => {
  // Show overview by default with animation
  const overview = document.getElementById('section-overview');
  if(overview) {
      overview.classList.add('active');
      setTimeout(() => overview.classList.add('fade-in'), 50);
  }
  loadSubscriptions();
});
</script>
</body>
</html>
