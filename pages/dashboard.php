<?php
require_once __DIR__ . '/../includes/auth_check.php';
$user_name = htmlspecialchars($_SESSION['name']);
$user_role = $_SESSION['role'];
if ($user_role === 'admin') { header('Location: ' . BASE_URL . '/admin/'); exit; }

// Get user details for settings prefill
$stmt = $pdo->prepare("SELECT email, role, serial_prefix, logo_url, company_heading, company_subheading, two_factor_enabled, serial_limit, show_reference FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
$current_serials_count = $pdo->prepare("SELECT COUNT(*) FROM serials WHERE user_id = ?");
$current_serials_count->execute([$_SESSION['user_id']]);
$u_count = $current_serials_count->fetchColumn();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — QAMS</title>
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-body">

<div id="toast-container"></div>
<div class="dashboard-layout">
  <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
  <!-- ── SIDEBAR ── -->
  <aside class="sidebar no-print">
    <div class="sidebar-logo">
      <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo">
    </div>
    
    <nav class="sidebar-nav">
      <ul>
        <li><button class="tab-btn active" data-tab="overview"><i class="bi bi-grid-fill"></i> Overview</button></li>
        <li><button class="tab-btn" data-tab="products"><i class="bi bi-box-seam-fill"></i> Asset Types</button></li>
        <li><button class="tab-btn" data-tab="assetlist"><i class="bi bi-list-columns-reverse"></i> All Assets List</button></li>
        <li><button class="tab-btn" data-tab="create"><i class="bi bi-plus-circle-fill"></i> Create Asset</button></li>
        <li><button class="tab-btn" data-tab="print"><i class="bi bi-printer-fill"></i> Print / QR</button></li>
        <li><button class="tab-btn" data-tab="maintenance"><i class="bi bi-wrench-adjustable"></i> Maintenance</button></li>
        <li><button class="tab-btn" data-tab="settings"><i class="bi bi-gear-fill"></i> Settings</button></li>
      </ul>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar" id="sidebar-user-avatar">
          <?php if (!empty($u['logo_url'])): 
            $logo_src = (strpos($u['logo_url'], 'http') === 0) ? $u['logo_url'] : BASE_URL . '/' . $u['logo_url'];
          ?>
            <img src="<?= $logo_src ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover; border-radius:10px">
          <?php else: ?>
            <?= strtoupper(substr($user_name, 0, 1)) ?>
          <?php endif; ?>
        </div>
        <div class="user-info">
          <span class="name"><?= $user_name ?></span>
          <span class="role"><?= ucfirst($user_role) ?></span>
        </div>
        <a href="logout" class="action-icon" style="margin-left:auto; background:transparent; border:none; color:rgba(255,255,255,0.4)" title="Logout">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- ── MAIN CONTENT AREA ── -->
  <main class="main-content">
    
    <!-- ── TOPBAR ── -->
    <header class="topbar no-print">
      <div style="display:flex; align-items:center; gap:1rem">
        <button class="action-icon d-lg-none" id="sidebar-toggle" onclick="toggleSidebar()">
          <i class="bi bi-list"></i>
        </button>
        <div class="search-bar">
          <i class="bi bi-search"></i>
          <input type="text" id="global-search" placeholder="Quick search assets..." oninput="handleGlobalSearch(this)">
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn btn-primary" onclick="switchTab('create')">
          <i class="bi bi-plus-lg"></i> Quick Generate
        </button>
      </div>
    </header>

    <!-- ── DYNAMIC CONTENT SECTIONS ── -->
    
    <!-- OVERVIEW -->
    <section id="tab-overview" class="tab-content active" role="tabpanel">
      <div class="stats-grid" id="stats-grid">
        <div class="card stat-card">
          <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
          <div class="stat-num" id="stat-products">…</div>
          <div class="stat-lbl">Active Asset Types</div>
        </div>
        <div class="card stat-card">
          <div class="stat-icon"><i class="bi bi-qr-code"></i></div>
          <div class="stat-num" id="stat-serials">…</div>
          <div class="stat-lbl">Total Assets</div>
        </div>
        <?php 
          $limit = (int)$u['serial_limit'];
          $perc = ($limit > 0) ? ($u_count / $limit) * 100 : 0;
          $bar_color = '#10b981'; // Green (low usage)
          $bg_border = '';
          if ($perc >= 90) {
              $bar_color = '#ef4444'; // Red (high usage)
              $bg_border = 'border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 0 15px rgba(239, 68, 68, 0.1);';
          } elseif ($perc >= 70) {
              $bar_color = '#f59e0b'; // Orange/Yellow (medium usage)
              $bg_border = 'border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 0 15px rgba(245, 158, 11, 0.1);';
          }
        ?>
        <div class="card stat-card" style="<?= $bg_border ?> display: flex; align-items: center; gap: 1.25rem;">
          <div class="stat-icon" style="color: <?= $bar_color ?>; background: <?= $bar_color ?>1a; flex-shrink: 0;"><i class="bi bi-shield-check"></i></div>
          <div style="flex: 1; display: flex; flex-direction: column; gap: 2px; min-width: 0;">
            <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
              <span class="stat-num" style="margin: 0; font-size: 1.75rem;"><?= $limit >= 9999 ? 'Unlimited' : $limit ?></span>
              <span class="stat-lbl" style="margin: 0; font-size: 0.75rem; text-transform: none; font-weight: 700; color: var(--text-primary);"><?= $u_count ?> / <?= $limit ?> used</span>
            </div>
            <div class="progress-container" style="margin: 6px 0; background: rgba(0,0,0,0.05); height: 6px; border-radius: 3px; overflow: hidden; width: 100%;">
              <div class="progress-bar" style="width: <?= min(100, $perc) ?>%; background: <?= $bar_color ?>; height: 100%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
              <span class="stat-lbl" style="font-size: 0.75rem; color: var(--text-secondary);">License Limit</span>
              <a href="<?= BASE_URL ?>/price" style="font-size: 0.75rem; color: <?= $bar_color ?>; font-weight: 800; text-decoration: none; white-space: nowrap;">Upgrade / Support →</a>
            </div>
          </div>
        </div>
      </div>

      <div class="card table-card">
        <div style="padding:1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center">
          <h2 style="font-size:1.1rem; font-weight:700">Recently Generated Assets</h2>
          <button class="btn btn-ghost btn-sm" onclick="switchTab('assetlist')">View All</button>
        </div>
        <div class="table-wrap">
          <table id="recent-table">
            <thead><tr><th>Asset Tag / S/N</th><th>User</th><th>Asset Type</th><th>Created Date</th><th>Action</th></tr></thead>
            <tbody id="recent-body"><tr><td colspan="5">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- PRODUCTS -->
    <section id="tab-products" class="tab-content" role="tabpanel">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem">
        <h2 style="font-size:1.2rem; font-weight:800; margin:0">Manage Asset Types</h2>
        <button class="btn btn-primary" onclick="openAddProductModal()"><i class="bi bi-plus-lg"></i> Add Asset Type</button>
      </div>

      <div class="card table-card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Asset Type Name</th><th>Short Code</th><th>Assets Created</th><th>Date Added</th><th>Action</th></tr></thead>
            <tbody id="product-tbody"><tr><td colspan="5">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>

    </section>
 
    <!-- ALL ASSETS LIST -->
    <section id="tab-assetlist" class="tab-content" role="tabpanel">
      <div class="card" style="margin-bottom:1.5rem">
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:flex-end;">
          <div class="form-group" style="margin:0; flex:1; min-width:300px">
            <label for="assetlist-search">Filter by Keyword</label>
            <div style="position:relative">
               <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary)"></i>
               <input type="text" id="assetlist-search" style="padding-left:40px" placeholder="Search asset tags, types, parents..." oninput="renderAssetList()">
            </div>
          </div>
          <div class="form-group" style="margin:0; width:220px">
            <label for="assetlist-filter-prod">Filter by Asset Type</label>
            <select id="assetlist-filter-prod" onchange="renderAssetList()">
              <option value="">All Asset Types</option>
            </select>
          </div>
          <button class="btn btn-ghost" onclick="resetAssetListFilters()"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
      </div>

      <div class="card table-card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Asset Tag / S/N</th><th>Asset Type</th><th>Parent Asset</th><th>Custodian</th><th>Status</th><th>Date Created</th><th>Action</th></tr></thead>
            <tbody id="assetlist-tbody"><tr><td colspan="7">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- CREATE SERIAL -->
    <section id="tab-create" class="tab-content" role="tabpanel">
      <div class="card" style="max-width:600px; margin:0 auto">
        <div style="text-align:center; margin-bottom:2rem">
          <div class="stat-icon" style="margin:0 auto 1rem"><i class="bi bi-plus-lg"></i></div>
          <h2 style="font-size:1.5rem; font-weight:800">Generate New Asset</h2>
          <p style="color:var(--text-secondary)">Fill in the asset details to generate a smart QR label.</p>
        </div>

        <div id="create-msg"></div>
        
        <div class="form-group">
          <label for="create-product-search">Asset Type</label>
          <div class="autocomplete-wrap">
            <input type="text" id="create-product-search" placeholder="Type to search asset type..." autocomplete="off" required>
            <input type="hidden" id="create-product" value="">
            <div id="create-product-suggestions" class="autocomplete-suggestions"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="create-parent-search">Parent Asset (Optional)</label>
          <div class="autocomplete-wrap">
            <input type="text" id="create-parent-search" placeholder="Type to search parent serial..." autocomplete="off">
            <input type="hidden" id="create-parent" value="">
            <div id="create-parent-suggestions" class="autocomplete-suggestions"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="create-status">Asset Status</label>
          <select id="create-status">
            <option value="In Stock" selected>In Stock</option>
            <option value="Active">Active</option>
            <option value="Breakdown">Breakdown</option>
            <option value="Under Repair">Under Repair</option>
            <option value="Retired">Retired</option>
          </select>
        </div>

        <div class="form-group">
          <label for="create-name">Asset Name / Model</label>
          <input type="text" id="create-name" placeholder="e.g. HP PRODESK, HP KM160" required>
        </div>

        <div class="form-group">
          <label for="create-serial-no">Serial Number / Sl No (Optional)</label>
          <input type="text" id="create-serial-no" placeholder="e.g. SN-123456, ASUS-RTX-7762">
        </div>

        <div class="form-group">
          <label for="create-description">Asset Description</label>
          <textarea id="create-description" placeholder="e.g. i3 11th gen, 256 gb ssd etc" rows="2" style="font-family:inherit; resize:vertical;"></textarea>
        </div>

        <div id="create-custodian-section">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem">
            <div class="form-group">
              <label for="create-user">User Name</label>
              <input type="text" id="create-user" placeholder="e.g. John Doe">
            </div>
            <div class="form-group">
              <label for="create-desig">Designation</label>
              <input type="text" id="create-desig" placeholder="e.g. Manager">
            </div>
          </div>
          
          <div class="form-group">
            <label for="create-dept">Department</label>
            <input type="text" id="create-dept" placeholder="e.g. IT Department">
          </div>
        </div>

        <div id="serial-preview-wrap" style="display:none; margin-bottom:1.5rem; padding:1.25rem; background:#f8fafc; border-radius:12px; border:1px solid var(--border-color); text-align:center">
          <span style="font-size:0.8rem; color:var(--text-secondary); display:block; margin-bottom:4px">Next Sequential Asset Tag:</span>
          <strong id="serial-preview" style="font-family:monospace; font-size:1.4rem; color:var(--accent)"></strong>
        </div>

        <button id="generate-btn" class="btn btn-primary" style="width:100%; padding:14px; font-size:1rem" disabled>Generate &amp; Save Asset</button>

      </div>
    </section>

    <!-- PRINT GRID -->
    <section id="tab-print" class="tab-content" role="tabpanel">
      <div class="card no-print" style="margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem">
        <div style="display:flex; gap:1rem; align-items:center">
          <select id="filter-product" style="width:200px"><option value="">All Asset Types</option></select>
          <div style="display:flex; gap:0.5rem; border-left:1px solid var(--border-color); padding-left:1rem">
            <button class="btn btn-ghost btn-sm" onclick="toggleAllQR(true)">Select All</button>
            <button class="btn btn-ghost btn-sm" onclick="toggleAllQR(false)">Deselect All</button>
          </div>
        </div>
        <div style="display:flex; gap:1rem">
          <button class="btn btn-ghost" id="refresh-print-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Selected</button>
        </div>
      </div>
      <div class="print-grid" id="print-grid"></div>
    </section>

    <!-- MAINTENANCE LOGS -->
    <section id="tab-maintenance" class="tab-content" role="tabpanel">
      <div class="card" style="margin-bottom:1.5rem">
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:flex-end;">
          <div class="form-group" style="margin:0; flex:1; min-width:300px">
            <label for="maintenance-search">Search Logs</label>
            <div style="position:relative">
               <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary)"></i>
               <input type="text" id="maintenance-search" style="padding-left:40px" placeholder="Search by asset tag, type, or note..." oninput="renderMaintenanceLogs()">
            </div>
          </div>
          <div class="form-group" style="margin:0; width:220px">
            <label for="maintenance-filter-status">Filter by Status</label>
            <select id="maintenance-filter-status" onchange="renderMaintenanceLogs()">
              <option value="">All Statuses</option>
              <option value="In Stock">In Stock</option>
              <option value="Active">Active</option>
              <option value="Breakdown">Breakdown</option>
              <option value="Under Repair">Under Repair</option>
              <option value="Retired">Retired</option>
            </select>
          </div>
          <button class="btn btn-ghost" onclick="resetMaintenanceFilters()"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
      </div>

      <div class="card table-card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Asset Tag</th>
                <th>Asset Type</th>
                <th>Custodian</th>
                <th>Logged Status</th>
                <th>Maintenance Note</th>
                <th>Date Logged</th>
                <th style="width:100px; text-align:right">Actions</th>
              </tr>
            </thead>
            <tbody id="maintenance-tbody">
              <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--text-secondary)">Loading logs...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- SETTINGS -->
    <section id="tab-settings" class="tab-content" role="tabpanel">
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:2rem">
        
        <!-- General Settings -->
        <div class="card">
          <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1.5rem">General Configuration</h2>
          
          <form id="prefix-form" style="margin-bottom:2rem">
            <div class="form-group">
              <label for="prefix-input">Global Asset Tag Prefix</label>
              <div style="display:flex; gap:10px">
                <input type="text" id="prefix-input" value="<?= htmlspecialchars($u['serial_prefix'] ?? '') ?>" placeholder="e.g. QAMS-">
                <button type="submit" class="btn btn-primary">Update</button>
              </div>
              <small style="color:var(--text-secondary)">Prepended to all asset tags (e.g. QAMS-LAP-001)</small>
            </div>
          </form>

          <form id="logo-upload-form" enctype="multipart/form-data">
            <div class="form-group">
              <label>Organization Logo</label>
              <div style="display:flex; gap:1.5rem; align-items:center; margin-top:0.5rem">
                <div id="logo-preview-wrap" style="width:80px; height:80px; border-radius:14px; border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; background:#f8fafc; overflow:hidden; flex-shrink:0">
                    <?php if (!empty($u['logo_url'])): 
                        $logo_src = (strpos($u['logo_url'], 'http') === 0) ? $u['logo_url'] : BASE_URL . '/' . $u['logo_url'];
                    ?>
                        <img src="<?= $logo_src ?>" style="width:100%; height:100%; object-fit:cover">
                    <?php else: ?>
                        <i class="bi bi-image" style="color:var(--border-color); font-size:1.5rem"></i>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                  <input type="file" id="logo-file-input" accept="image/*" style="font-size:0.85rem; margin-bottom:0.75rem; display:block">
                  <div style="display:flex; gap:10px; margin-bottom:0.75rem">
                    <input type="url" id="logo-url-input" value="<?= (!empty($u['logo_url']) && strpos($u['logo_url'], 'http') === 0) ? htmlspecialchars($u['logo_url']) : '' ?>" placeholder="Or paste image URL..." style="flex:1; font-size:0.85rem">
                  </div>
                  <button type="submit" class="btn btn-primary btn-sm">Update Logo</button>
                </div>
              </div>
              <p style="font-size:0.7rem; color:var(--text-secondary); margin-top:1rem">Recommended: Square PNG/SVG, max 2MB. This logo appears in your sidebar.</p>
            </div>
          </form>
        </div>

        <!-- Security & Preferences -->
        <div class="card">
          <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1.5rem">Security & Branding</h2>
          
          <div class="form-group" style="display:flex; justify-content:space-between; align-items:center; padding-bottom:1.25rem; border-bottom:1px solid var(--border-color)">
            <div>
              <label style="margin:0">Public Reference</label>
              <p style="font-size:0.8rem; color:var(--text-secondary)">Show "Developed by pradyumna" on scan pages.</p>
            </div>
            <label class="switch">
              <input type="checkbox" id="ref-toggle" <?= ($u['show_reference'] ?? 1) ? 'checked' : '' ?> onchange="handleRefToggle(this)">
              <span class="slider"></span>
            </label>
          </div>

          <div class="form-group" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 0; border-bottom:1px solid var(--border-color)">
            <div>
              <label style="margin:0">Two-Factor Authentication (2FA)</label>
              <p style="font-size:0.8rem; color:var(--text-secondary)">Secure your account with email codes.</p>
            </div>
            <label class="switch">
              <input type="checkbox" id="2fa-toggle" <?= ($u['two_factor_enabled'] ?? 0) ? 'checked' : '' ?> <?= $u['role'] === 'admin' ? 'disabled' : '' ?>>
              <span class="slider"></span>
            </label>
          </div>

          <form id="heading-form" style="margin-top:1.5rem" autocomplete="off">
            <div class="form-group">
              <label for="heading-input">Public Display Heading</label>
              <input type="text" id="heading-input" placeholder="e.g. PASUPATI AGROVET PRIVATE LIMITED" autocomplete="off">
            </div>
            <div class="form-group">
              <label for="subheading-input">Public Display Subheading</label>
              <div style="display:flex; gap:10px">
                <input type="text" id="subheading-input" placeholder="e.g. RAIPUR UNIT (Plant-4)" autocomplete="off">
                <button type="submit" class="btn btn-primary">Save</button>
              </div>
            </div>
          </form>
        </div>

        <!-- Account Credentials -->
        <div class="card">
          <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1.5rem">Account Settings</h2>
          <form id="email-form" style="margin-bottom:2rem">
            <div class="form-group">
              <label for="email-input">Login Email Address</label>
              <div style="display:flex; gap:10px">
                <input type="email" id="email-input" value="<?= htmlspecialchars($u['email']) ?>" required>
                <button type="submit" class="btn btn-primary">Update</button>
              </div>
            </div>
          </form>
          <form id="pw-form">
            <div class="form-group"><label>Current Password</label><input type="password" id="cur-pw" required></div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem">
              <div class="form-group"><label>New Password</label><input type="password" id="new-pw" required></div>
              <div class="form-group"><label>Confirm Password</label><input type="password" id="con-pw" required></div>
            </div>
            <button type="submit" class="btn btn-ghost" style="width:100%">Change Password</button>
          </form>
        </div>

      </div>
    </section>

  </main>
</div>

<!-- Edit Serial Modal -->
<div class="modal-overlay" id="edit-serial-modal">
  <div class="modal-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem">
      <h2 style="font-size:1.3rem; font-weight:800">Edit Asset Details</h2>
      <button class="action-icon" style="background:none; border:none" onclick="closeModal('edit-serial-modal')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div style="margin-bottom:1.5rem; padding:1rem; background:#f8fafc; border-radius:12px; font-family:monospace; font-weight:800; color:var(--accent); text-align:center" id="edit-serial-sn"></div>
    <input type="hidden" id="edit-serial-id">
    
    <div class="form-group">
      <label for="edit-name">Asset Name / Model</label>
      <input type="text" id="edit-name" placeholder="e.g. HP PRODESK, HP KM160" required>
    </div>

    <div class="form-group">
      <label for="edit-serial-no">Serial Number / Sl No (Optional)</label>
      <input type="text" id="edit-serial-no" placeholder="e.g. SN-123456, ASUS-RTX-7762">
    </div>

    <div class="form-group">
      <label for="edit-description">Asset Description</label>
      <textarea id="edit-description" placeholder="e.g. i3 11th gen, 256 gb ssd etc" rows="2" style="font-family:inherit; resize:vertical;"></textarea>
    </div>

    <div id="edit-custodian-section">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem">
        <div class="form-group">
          <label for="edit-user">User Name</label>
          <input type="text" id="edit-user">
        </div>
        <div class="form-group">
          <label for="edit-desig">Designation</label>
          <input type="text" id="edit-desig">
        </div>
      </div>
      <div class="form-group">
        <label for="edit-dept">Department</label>
        <input type="text" id="edit-dept">
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem">
      <div class="form-group" style="margin:0">
        <label for="edit-parent">Parent Asset (Optional)</label>
        <select id="edit-parent">
          <option value="">None (Independent Asset)</option>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label for="edit-status">Asset Status</label>
        <select id="edit-status">
          <option value="In Stock">In Stock</option>
          <option value="Active">Active</option>
          <option value="Breakdown">Breakdown</option>
          <option value="Under Repair">Under Repair</option>
          <option value="Retired">Retired</option>
        </select>
      </div>
    </div>

    <div class="form-group" style="margin-top:1.25rem" id="edit-maintenance-note-section">
      <label for="edit-maintenance-note" id="edit-maintenance-note-label">Maintenance / Repair Note (Optional)</label>
      <textarea id="edit-maintenance-note" placeholder="Describe what was repaired or updated (e.g. replaced screen, added toner)..." rows="2" style="font-family:inherit; resize:vertical;"></textarea>
      <small style="color:var(--text-secondary)">Saving creates a log entry in this asset's maintenance history.</small>
    </div>
    
    <div style="display:flex; gap:1rem; margin-top:2.5rem">
      <button class="btn btn-ghost" style="flex:1" onclick="closeModal('edit-serial-modal')">Cancel</button>
      <button class="btn btn-primary" style="flex:1.5" id="save-edit-btn" onclick="saveSerialEdit()">Save Changes</button>
    </div>
  </div>
</div>

<!-- View Serial Modal -->
<div class="modal-overlay" id="view-serial-modal">
  <div class="modal-card" style="max-width: 550px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem">
      <h2 style="font-size:1.3rem; font-weight:800">Asset Record Overview</h2>
      <button class="action-icon" style="background:none; border:none" onclick="closeModal('view-serial-modal')"><i class="bi bi-x-lg"></i></button>
    </div>
    
    <div style="margin-bottom:1.5rem; padding:1.25rem; background:#f8fafc; border-radius:14px; border:1px solid var(--border-color); text-align:center">
      <span style="font-size:0.75rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:4px">Asset Tag</span>
      <span style="font-family:monospace; font-size:1.2rem; font-weight:900; color:var(--accent); word-break:break-all" id="view-serial-sn"></span>
      <span style="display:inline-block; margin-top:6px; font-size:0.8rem; font-weight:700; color:var(--text-primary); background:rgba(0,0,0,0.05); padding:2px 8px; border-radius:6px" id="view-serial-prod"></span>
      <span style="display:inline-block; margin-top:6px; font-size:0.8rem; font-weight:700; margin-left:6px" id="view-serial-status"></span>
    </div>

    <div style="margin-bottom:1.5rem; display:none" id="view-parent-wrap">
      <span style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:6px">Parent Asset Link</span>
      <div style="padding:1rem; background:#ffffff; border:1px solid var(--border-color); border-radius:12px; display:flex; justify-content:space-between; align-items:center">
        <div>
          <span style="font-family:monospace; font-weight:800; color:var(--accent)" id="view-parent-sn"></span>
          <div style="font-size:0.75rem; color:var(--text-secondary)" id="view-parent-details"></div>
        </div>
        <button class="btn btn-ghost btn-sm" id="view-parent-btn">View Parent</button>
      </div>
    </div>

    <div style="margin-bottom:1.5rem">
      <span style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:6px">Assigned Custodian</span>
      <div style="padding:1rem; background:#ffffff; border:1px solid var(--border-color); border-radius:12px">
        <div style="font-weight:800; font-size:1.05rem; color:var(--text-primary)" id="view-user-name"></div>
        <div style="font-size:0.85rem; color:var(--text-secondary); margin-top:2px" id="view-user-meta"></div>
      </div>
    </div>

    <div style="margin-bottom:1.5rem">
      <span style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:6px">Asset Name &amp; Description</span>
      <div style="padding:1rem; background:#ffffff; border:1px solid var(--border-color); border-radius:12px">
        <div style="font-weight:800; font-size:1.05rem; color:var(--text-primary)" id="view-name"></div>
        <div style="font-size:0.85rem; color:var(--text-secondary); margin-top:4px; white-space:pre-wrap" id="view-description"></div>
        <div style="margin-top:8px; display:none; font-family:monospace; font-size:0.8rem; background:rgba(0,0,0,0.05); padding:2px 8px; border-radius:6px; font-weight:700;" id="view-serial-no-wrap">S/N: <span id="view-serial-no"></span></div>
      </div>
    </div>

    <div style="margin-bottom:1.5rem; display:none" id="view-children-wrap">
      <span style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:8px">Linked Peripherals / Component Assets</span>
      <div id="view-children-list" style="display:flex; flex-direction:column; gap:8px; max-height:260px; overflow-y:auto; padding-right:4px"></div>
    </div>

    <div style="margin-bottom:1.5rem; display:none" id="view-maintenance-wrap">
      <span style="font-size:0.8rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; display:block; margin-bottom:8px">Maintenance &amp; Service History</span>
      <div id="view-maintenance-history" style="display:flex; flex-direction:column; gap:8px; max-height:220px; overflow-y:auto; padding-right:4px"></div>
    </div>

    <div style="text-align:center; font-size:0.75rem; color:var(--text-secondary); margin-top:1.5rem; border-top:1px dashed var(--border-color); padding-top:1rem" id="view-created-at"></div>
  </div>
</div>

<!-- Product (Asset Type) Modal -->
<div class="modal-overlay" id="product-modal">
  <div class="modal-card" style="max-width: 450px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem">
      <h2 style="font-size:1.3rem; font-weight:800" id="product-modal-title">Create Asset Type</h2>
      <button class="action-icon" style="background:none; border:none" onclick="closeModal('product-modal')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="product-form">
      <input type="hidden" id="prod-id" value="">
      <div class="form-group">
        <label for="prod-name">Asset Type Name</label>
        <input type="text" id="prod-name" placeholder="e.g. LAPTOP" required>
      </div>
      <div class="form-group">
        <label for="prod-short">Prefix Code</label>
        <input type="text" id="prod-short" placeholder="e.g. LAP" maxlength="10" required>
      </div>
      <div class="form-group">
        <label for="prod-icon">Asset Icon Class</label>
        <div style="display:flex; gap:10px; align-items:center;">
          <div style="width:44px; height:44px; border-radius:10px; border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; background:#f8fafc; flex-shrink:0;">
            <i class="bi bi-box-seam" id="icon-preview-i" style="font-size:1.4rem; color:var(--accent)"></i>
          </div>
          <input type="text" id="prod-icon" placeholder="e.g. bi-laptop" style="flex:1" oninput="updateIconPreview(this.value)" required>
        </div>
        <p style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.5rem; line-height:1.4">
          Enter any icon class from <a href="https://icons.getbootstrap.com/" target="_blank" style="color:var(--accent); text-decoration:underline; font-weight:600">Bootstrap Icons</a> (e.g. <code>bi-laptop</code>, <code>bi-cpu</code>, <code>bi-phone</code>).
        </p>
      </div>
      <div style="display:flex; gap:1rem; margin-top:2rem">
        <button type="button" class="btn btn-ghost" style="flex:1" onclick="closeModal('product-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary" style="flex:1.5" id="prod-btn">Save Asset Type</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Maintenance Log Modal -->
<div class="modal-overlay" id="edit-log-modal">
  <div class="modal-card" style="max-width: 450px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem">
      <h2 style="font-size:1.3rem; font-weight:800">Edit Maintenance Log</h2>
      <button class="action-icon" style="background:none; border:none" onclick="closeModal('edit-log-modal')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form id="edit-log-form">
      <input type="hidden" id="edit-log-id" value="">
      <div class="form-group">
        <label for="edit-log-status">Logged Status</label>
        <select id="edit-log-status">
          <option value="In Stock">In Stock</option>
          <option value="Active">Active</option>
          <option value="Breakdown">Breakdown</option>
          <option value="Under Repair">Under Repair</option>
          <option value="Retired">Retired</option>
        </select>
      </div>
      <div class="form-group">
        <label for="edit-log-note">Maintenance Note</label>
        <textarea id="edit-log-note" required rows="4" style="width:100%; border:1px solid var(--border-color); border-radius:8px; padding:10px; font-family:inherit; resize:vertical;"></textarea>
      </div>
      <div style="display:flex; gap:1rem; margin-top:2rem">
        <button type="button" class="btn btn-ghost" style="flex:1" onclick="closeModal('edit-log-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary" style="flex:1.5" id="edit-log-btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const SCAN_BASE = BASE_URL;

function switchTab(tabId) {
    const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (btn) btn.click();
}

function handleGlobalSearch(input) {
    const search = input.value.toLowerCase();
    if (search.length > 2) {
        document.querySelector('.tab-btn[data-tab="assetlist"]').click();
        document.getElementById('assetlist-search').value = search;
        renderAssetList();
    }
}


function handleRefToggle(cb) {
    if (!cb.checked) {
        if (confirm('Removing the "Developed by pradyumna" reference requires contacting support. Would you like to contact support now?')) {
            window.location.href = '<?= BASE_URL ?>/price';
            cb.checked = true;
        } else {
            cb.checked = true;
        }
    } else {
        api('api/settings', 'PUT', { show_reference: 1 }).then(d => {
            if (!d.success) { cb.checked = false; toast(d.message, 'error'); }
        });
    }
}
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
