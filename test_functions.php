<?php
require_once 'config/config.php';

echo "<!DOCTYPE html><html><head><title>Function Test</title></head><body>";
echo "<h1>✅ Function Test HRISKU App</h1>";
echo "<pre>";

// Test formatRupiah
if (function_exists('formatRupiah')) {
    echo "✅ formatRupiah() exists\n";
    echo "   Test: " . formatRupiah(1000000) . "\n\n";
} else {
    echo "❌ formatRupiah() NOT FOUND\n\n";
}

// Test sanitize
if (function_exists('sanitize')) {
    echo "✅ sanitize() exists\n";
    echo "   Test: " . sanitize("<script>alert('test')</script>") . "\n\n";
} else {
    echo "❌ sanitize() NOT FOUND\n\n";
}

// Test uploadFile
if (function_exists('uploadFile')) {
    echo "✅ uploadFile() exists\n\n";
} else {
    echo "❌ uploadFile() NOT FOUND\n\n";
}

// Test e() helper
if (function_exists('e')) {
    echo "✅ e() exists\n";
    echo "   Test null: '" . e(null, 'default') . "'\n\n";
} else {
    echo "❌ e() NOT FOUND\n\n";
}

// Test formatDate
if (function_exists('formatDate')) {
    echo "✅ formatDate() exists\n";
    echo "   Test: " . formatDate('2025-12-17') . "\n\n";
} else {
    echo "❌ formatDate() NOT FOUND\n\n";
}

// Test auth functions
echo "=== Auth Functions ===\n";
echo "✅ isLoggedIn() exists: " . (function_exists('isLoggedIn') ? 'YES' : 'NO') . "\n";
echo "✅ getUserId() exists: " . (function_exists('getUserId') ? 'YES' : 'NO') . "\n";
echo "✅ getUsername() exists: " . (function_exists('getUsername') ? 'YES' : 'NO') . "\n";
echo "✅ getUserRole() exists: " . (function_exists('getUserRole') ? 'YES' : 'NO') . "\n";
echo "✅ getEmployeeId() exists: " . (function_exists('getEmployeeId') ? 'YES' : 'NO') . "\n";

echo "\n=== Configuration ===\n";
echo "APP_NAME: " . APP_NAME . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "DB_NAME: " . DB_NAME . "\n";

echo "\n=== Session ===\n";
echo "Logged In: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";
if (isLoggedIn()) {
    echo "Username: " . getUsername() . "\n";
    echo "Role: " . getUserRole() . "\n";
}

echo "</pre>";
echo "</body></html>";
?>
