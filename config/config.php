<?php
// ERROR REPORTING - HAPUS DI PRODUCTION!
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// ... sisa code


// Base Configuration
define('BASE_URL', 'http://localhost/hrisku-app/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('APP_NAME', 'HRISKU APP');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function checkRole($allowed_roles = []) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit();
    }
    
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        die('<h1>Access Denied</h1><p>You do not have permission to access this page.</p>');
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getEmployeeId() {
    return $_SESSION['employee_id'] ?? null;
}

function getUsername() {
    return $_SESSION['username'] ?? 'Guest';
}

// Helper Functions
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function showAlert($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}
