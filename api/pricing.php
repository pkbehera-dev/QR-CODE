<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

/* ── GET: public pricing rates ─────────────────────── */
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    json_out(true, ['settings' => $settings]);
}

/* ── POST: admin update rates ──────────────────────── */
if ($method === 'POST') {
    require_once __DIR__ . '/../includes/auth_check.php';
    if ($_SESSION['role'] !== 'admin') json_out(false, [], 'Admin access required.');

    $input = json_decode(file_get_contents('php://input'), true);
    $allowed = [
        'rate_per_asset_monthly', 'rate_per_asset_yearly', 
        'branding_removal_monthly', 'branding_removal_yearly', 
        'upi_id',
        'stat_1_value', 'stat_1_label',
        'stat_2_value', 'stat_2_label',
        'stat_3_value', 'stat_3_label'
    ];

    $updated = 0;
    foreach ($allowed as $key) {
        if (isset($input[$key])) {
            $val = $input[$key];
            $check = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
            $check->execute([$key]);
            if ($check->fetchColumn() > 0) {
                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$val, $key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $val]);
            }
            $updated++;
        }
    }

    if ($updated > 0) {
        json_out(true, [], "Updated {$updated} setting(s).");
    } else {
        json_out(false, [], 'No valid settings provided.');
    }
}

json_out(false, [], 'Method not allowed.');
