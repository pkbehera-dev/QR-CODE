<?php
// Admin-only: delete a user and all their data
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    json_out(false, [], 'Unauthorized.');
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) json_out(false, [], 'User ID required.');

// Cannot delete self
if ($id === (int)$_SESSION['user_id']) json_out(false, [], 'Cannot delete yourself.');

$chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$chk->execute([$id]);
$u = $chk->fetch();
if (!$u) json_out(false, [], 'User not found.');
if ($u['role'] === 'admin') json_out(false, [], 'Cannot delete admin accounts.');

$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
json_out(true, [], 'User deleted.');
