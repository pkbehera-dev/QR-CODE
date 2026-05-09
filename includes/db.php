<?php
require_once __DIR__ . '/config.php';
// Auto-detect BASE_URL based on physical directory structure
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$base_url = str_replace($doc_root, '', $app_root);
define('BASE_URL', $base_url);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]));
}
