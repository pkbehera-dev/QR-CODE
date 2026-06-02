<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$is_logged_in = isset($_SESSION['user_id']);
$dashboard_link = $is_logged_in ? (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? BASE_URL . '/admin' : BASE_URL . '/dashboard') : BASE_URL . '/login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QAMS — QR Asset Management System</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
$is_dark_page = in_array($current_page, ['index.php', 'price.php', 'terms.php', 'privacy.php']);
?>
<body class="<?= $is_dark_page ? 'bg-dark' : '' ?>">

<header class="public-header <?= $is_dark_page ? 'header-dark' : '' ?>">
    <div class="container">
        <a href="<?= BASE_URL ?>/" class="logo">
            <img src="<?= BASE_URL ?>/assets/img/logo%20png.png" alt="QAMS Logo">
        </a>
        
        <!-- HAMBURGER ICON -->
        <button class="menu-toggle" id="menu-toggle">
            <i class="bi bi-list"></i>
        </button>

        <nav class="nav-links" id="nav-links">
            <a href="<?= BASE_URL ?>/">Home</a>
            <a href="<?= BASE_URL ?>/#features">Features</a>
            <a href="<?= BASE_URL ?>/price">Support</a>
            <a href="<?= BASE_URL ?>/#about">About</a>
            <a href="<?= BASE_URL ?>/#contact">Contact</a>
            <?php if ($is_logged_in): ?>
                <a href="<?= $dashboard_link ?>" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login">Login</a>
                <a href="<?= BASE_URL ?>/signup" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<script>
document.getElementById('menu-toggle').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('active');
    const icon = this.querySelector('i');
    if (icon.classList.contains('bi-list')) {
        icon.classList.replace('bi-list', 'bi-x-lg');
    } else {
        icon.classList.replace('bi-x-lg', 'bi-list');
    }
});

// Close menu when clicking a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('nav-links').classList.remove('active');
        document.querySelector('.menu-toggle i').classList.replace('bi-x-lg', 'bi-list');
    });
});
</script>
