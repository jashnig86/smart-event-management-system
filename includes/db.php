<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'event_management');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Base URL helper
define('BASE_URL', 'http://localhost/event-management');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('QR_PATH', __DIR__ . '/../assets/qrcodes/');

// Create directories if not exist
if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
if (!is_dir(QR_PATH)) mkdir(QR_PATH, 0755, true);
