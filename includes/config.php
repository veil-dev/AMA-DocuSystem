<?php
// Database config for DMS
// Set your database credentials (MySQL example for XAMPP)
$dsn = 'mysql:host=localhost;dbname=DMS;charset=utf8mb4';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    } else {
        die('Database connection failed: ' . $e->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
