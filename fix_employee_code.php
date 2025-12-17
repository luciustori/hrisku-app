<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Employee Code</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: white; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>

<h1>🔧 Fix Employee Code Column</h1>
<pre>
<?php

try {
    echo "Step 1: Check if employee_code column exists...\n";
    
    $check = $db->query("SHOW COLUMNS FROM employees LIKE 'employee_code'")->fetch();
    
    if (!$check) {
        echo "❌ Column not found. Adding column...\n";
        $db->exec("ALTER TABLE employees ADD COLUMN employee_code VARCHAR(50) UNIQUE AFTER id");
        echo "<span class='success'>✅ Column added\n\n</span>";
    } else {
        echo "<span class='success'>✅ Column already exists\n\n</span>";
    }
    
    echo "Step 2: Update existing employees...\n";
    $db->exec("UPDATE employees SET employee_code = CONCAT('EMP', LPAD(id, 4, '0')) WHERE employee_code IS NULL OR employee_code = ''");
    echo "<span class='success'>✅ Updated\n\n</span>";
    
    echo "Step 3: Verify data...\n";
    $employees = $db->query("SELECT id, employee_code, full_name FROM employees LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($employees as $emp) {
        echo "ID: {$emp['id']} | Code: {$emp['employee_code']} | Name: {$emp['full_name']}\n";
    }
    
    echo "\n<span class='success'>✅✅✅ FIX COMPLETED! ✅✅✅</span>\n";
    
} catch (PDOException $e) {
    echo "<span class='error'>❌ ERROR: " . $e->getMessage() . "</span>\n";
}

?>
</pre>

<a href="modules/cuti/list_cuti.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">
    ➡️ Go to List Cuti
</a>

</body>
</html>
