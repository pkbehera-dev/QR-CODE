<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
require_once __DIR__ . '/includes/db.php';
header('Location: ' . BASE_URL . '/login.php');
exit;
