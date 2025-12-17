<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Fix Branches</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1f2937; color: #f3f4f6; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; }
        pre { background: #374151; padding: 20px; border-radius: 8px; border: 1px solid #4b5563; }
        .btn { 
            background: #3b82f6; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>

<h1>🔥 FORCE FIX BRANCHES TABLE</h1>
<pre>
<?php

try {
    // STEP 1: Find all FK constraints
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 1: Checking Foreign Key Constraints...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $fkQuery = "SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
        REFERENCED_TABLE_NAME = 'branches'
        AND TABLE_SCHEMA = DATABASE()";
    
    $foreignKeys = $db->query($fkQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($foreignKeys) > 0) {
        echo "<span class='warning'>Found " . count($foreignKeys) . " foreign key(s) referencing branches:</span>\n\n";
        
        foreach ($foreignKeys as $fk) {
            echo "  📌 {$fk['TABLE_NAME']}.{$fk['CONSTRAINT_NAME']}\n";
        }
        echo "\n";
        
        // STEP 2: Drop all FK
        echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
        echo "<span class='warning'>STEP 2: Dropping Foreign Keys...</span>\n";
        echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
        
        foreach ($foreignKeys as $fk) {
            try {
                $dropFK = "ALTER TABLE {$fk['TABLE_NAME']} DROP FOREIGN KEY {$fk['CONSTRAINT_NAME']}";
                $db->exec($dropFK);
                echo "<span class='success'>✅ Dropped: {$fk['TABLE_NAME']}.{$fk['CONSTRAINT_NAME']}</span>\n";
            } catch (PDOException $e) {
                echo "<span class='warning'>⚠️  Skip: {$fk['CONSTRAINT_NAME']} - {$e->getMessage()}</span>\n";
            }
        }
        echo "\n";
    } else {
        echo "<span class='success'>✅ No foreign keys found\n\n</span>";
    }
    
    // STEP 3: Disable FK check
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 3: Disabling Foreign Key Checks...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "<span class='success'>✅ Disabled\n\n</span>";
    
    // STEP 4: Backup
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 4: Backing up existing data...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    try {
        $db->exec("DROP TABLE IF EXISTS branches_backup");
        $db->exec("CREATE TABLE branches_backup AS SELECT * FROM branches");
        
        $count = $db->query("SELECT COUNT(*) FROM branches_backup")->fetchColumn();
        echo "<span class='success'>✅ Backed up {$count} records\n\n</span>";
    } catch (PDOException $e) {
        echo "<span class='warning'>⚠️  No data to backup\n\n</span>";
    }
    
    // STEP 5: Drop table
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 5: Dropping old table...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $db->exec("DROP TABLE IF EXISTS branches");
    echo "<span class='success'>✅ Table dropped\n\n</span>";
    
    // STEP 6: Create new table
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 6: Creating new table structure...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $createSQL = "CREATE TABLE branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch_code VARCHAR(50) UNIQUE NOT NULL,
        branch_name VARCHAR(255) NOT NULL,
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(100),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        radius_meter INT DEFAULT 100,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_branch_code (branch_code),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($createSQL);
    echo "<span class='success'>✅ Table created\n\n</span>";
    
    // STEP 7: Insert data
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 7: Inserting sample data...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $insertSQL = "INSERT INTO branches (branch_code, branch_name, address, phone, email, latitude, longitude, radius_meter, is_active) VALUES
    ('HQ-JKT', 'Kantor Pusat Jakarta', 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan 12190', '021-5000123', 'jakarta@company.com', -6.225014, 106.809159, 100, 1),
    ('BR-BDG', 'Cabang Bandung', 'Jl. Asia Afrika No. 8, Bandung 40111', '022-4200456', 'bandung@company.com', -6.917464, 107.619123, 150, 1),
    ('BR-SBY', 'Cabang Surabaya', 'Jl. Tunjungan No. 101, Surabaya 60275', '031-3100789', 'surabaya@company.com', -7.265757, 112.742088, 100, 1),
    ('BR-SMG', 'Cabang Semarang', 'Jl. Pemuda No. 142, Semarang 50132', '024-3500321', 'semarang@company.com', -6.966667, 110.416664, 100, 1),
    ('BR-MDN', 'Cabang Medan', 'Jl. Imam Bonjol No. 17, Medan 20152', '061-4100654', 'medan@company.com', 3.597031, 98.678513, 100, 1)";
    
    $db->exec($insertSQL);
    echo "<span class='success'>✅ Inserted 5 branches\n\n</span>";
    
    // STEP 8: Re-enable FK
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 8: Re-enabling Foreign Key Checks...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span class='success'>✅ Re-enabled\n\n</span>";
    
    // STEP 9: Verify
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n";
    echo "<span class='warning'>STEP 9: Verification...</span>\n";
    echo "<span class='info'>═══════════════════════════════════════════════════════</span>\n\n";
    
    $branches = $db->query("SELECT * FROM branches ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total Branches: " . count($branches) . "\n";
    echo str_repeat("─", 80) . "\n";
    
    foreach ($branches as $branch) {
        echo "📍 [{$branch['branch_code']}] {$branch['branch_name']}\n";
        echo "   📞 {$branch['phone']} | 📧 {$branch['email']}\n";
        echo "   🗺️  ({$branch['latitude']}, {$branch['longitude']}) - Radius: {$branch['radius_meter']}m\n";
        echo str_repeat("─", 80) . "\n";
    }
    
    echo "\n<span class='success'>╔════════════════════════════════════════════════════╗</span>\n";
    echo "<span class='success'>║  ✅✅✅  MIGRATION COMPLETED SUCCESSFULLY!  ✅✅✅  ║</span>\n";
    echo "<span class='success'>╚════════════════════════════════════════════════════╝</span>\n\n";
    
} catch (PDOException $e) {
    echo "\n<span class='error'>╔════════════════════════════════════════════════════╗</span>\n";
    echo "<span class='error'>║  ❌❌❌  ERROR OCCURRED  ❌❌❌                     ║</span>\n";
    echo "<span class='error'>╚════════════════════════════════════════════════════╝</span>\n\n";
    echo "<span class='error'>" . $e->getMessage() . "</span>\n";
}

?>
</pre>

<a href="modules/company/branches.php" class="btn">🏪 Go to Branches Page</a>
<a href="modules/company/index.php" class="btn" style="background: #10b981;">🏢 Company Dashboard</a>

</body>
</html>
