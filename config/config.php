<?php
// Error Reporting (Development only - HAPUS di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hrisku_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'HRISKU App');
define('BASE_URL', 'http://localhost/hrisku-app/');

// Include helper functions
require_once __DIR__ . '/functions.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUsername() {
    return $_SESSION['username'] ?? '';
}

function getUserRole() {
    return $_SESSION['role'] ?? '';
}

function getEmployeeId() {
    return $_SESSION['employee_id'] ?? null;
}

function checkRole($allowedRoles = []) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array(getUserRole(), $allowedRoles)) {
        header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
        exit();
    }
}
