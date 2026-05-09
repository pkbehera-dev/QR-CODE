<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php'); exit;
}

$users   = $pdo->query("SELECT id, name, email, role, serial_prefix, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$tusers  = count($users);
$tprod   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$tserial = $pdo->query("SELECT COUNT(*) FROM serials")->fetchColumn();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — QR Serial</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <span class="site-logo">📦 QR Serial — Admin</span>
    <div class="header-meta">
      <?= htmlspecialchars($_SESSION['name']) ?> &nbsp;|&nbsp;
      <a href="../logout.php">Logout</a>
    </div>
  </div>
</header>
<main class="container" style="padding-top:1.5rem">

  <div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card"><div class="stat-num"><?= $tusers ?></div><div class="stat-lbl">Total Users</div></div>
    <div class="stat-card"><div class="stat-num"><?= $tprod ?></div><div class="stat-lbl">Total Products</div></div>
    <div class="stat-card"><div class="stat-num"><?= $tserial ?></div><div class="stat-lbl">Total Serials</div></div>
  </div>

  <div class="card">
    <h2 style="font-size:1rem;margin-bottom:1rem">Users</h2>
    <div id="admin-msg"></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Prefix</th><th>Joined</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr id="urow-<?= $u['id'] ?>">
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['role'] === 'admin' ? '<b>Admin</b>' : 'User' ?></td>
            <td><code><?= htmlspecialchars($u['serial_prefix'] ?: '—') ?></code></td>
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

  <div class="card">
    <h2 style="font-size:1rem;margin-bottom:1rem">System Configuration</h2>
    <p style="font-size:0.85rem; color:#666; margin-bottom:1rem;">These settings are managed securely in <code>includes/config.php</code>. Please edit the file directly to make changes.</p>
    
    <div class="table-wrap">
      <table>
        <thead><tr><th>Component</th><th>Setting</th><th>Status</th></tr></thead>
        <tbody>
          <tr>
            <td><b>SMTP Mail</b></td>
            <td><code>SMTP_HOST</code></td>
            <td><?= defined('SMTP_HOST') && SMTP_HOST ? '<span style="color:green">✔ Configured (' . htmlspecialchars(SMTP_HOST) . ')</span>' : '<span style="color:red">✘ Missing</span>' ?></td>
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
          <tr>
            <td><b>reCAPTCHA</b></td>
            <td><code>RECAPTCHA_SECRET_KEY</code></td>
            <td><?= defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY ? '<span style="color:green">✔ Configured (Hidden)</span>' : '<span style="color:red">✘ Missing</span>' ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</main>
<script>
async function deleteUser(id, name) {
  if (!confirm('Delete user "' + name + '" and all their data?')) return;
  const r = await fetch('../api/admin_delete_user.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id=' + id
  });
  const d = await r.json();
  const m = document.getElementById('admin-msg');
  if (d.success) {
    document.getElementById('urow-' + id)?.remove();
    m.innerHTML = '<div class="alert alert-success">User deleted.</div>';
  } else {
    m.innerHTML = `<div class="alert alert-error">${d.message}</div>`;
  }
}
</script>
</body>
</html>
