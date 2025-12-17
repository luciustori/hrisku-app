<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html><html><head><title>Fix Companies Table</title></head><body>";
echo "<h1>🔧 Fix Companies Table Migration</h1>";
echo "<pre>";

try {
    // Disable FK check
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✅ Disabled foreign key checks\n\n";
    
    // Backup existing data
    $db->exec("DROP TABLE IF EXISTS companies_backup_20241217");
    $db->exec("CREATE TABLE companies_backup_20241217 AS SELECT * FROM companies");
    echo "✅ Backed up existing data\n\n";
    
    // Drop and recreate
    $db->exec("DROP TABLE IF EXISTS companies");
    echo "✅ Dropped old table\n\n";
    
    $createSQL = "CREATE TABLE companies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) NOT NULL,
        company_code VARCHAR(50) UNIQUE NOT NULL,
        tax_id VARCHAR(50),
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(100),
        website VARCHAR(100),
        logo VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($createSQL);
    echo "✅ Created new table structure\n\n";
    
    // Restore data
    $restoreSQL = "INSERT INTO companies (id, company_name, company_code, tax_id, address, phone, email, website, logo, is_active, created_at)
                   SELECT 
                       id, 
                       company_name,
                       CONCAT('COMP', LPAD(id, 3, '0')) as company_code,
                       '' as tax_id,
                       '' as address,
                       '' as phone,
                       '' as email,
                       '' as website,
                       '' as logo,
                       1 as is_active,
                       NOW()
                   FROM companies_backup_20241217";
    
    try {
        $db->exec($restoreSQL);
        echo "✅ Restored data from backup\n\n";
    } catch (PDOException $e) {
        echo "⚠️  No backup data to restore\n\n";
        
        // Insert default
        $db->exec("INSERT INTO companies (company_name, company_code, tax_id, address, phone, email, website, is_active)
                   VALUES ('PT Contoh Indonesia', 'PTI', '00.000.000.0-000.000', 'Jakarta Pusat', '021-12345678', 'info@pti.com', 'www.pti.com', 1)");
        echo "✅ Inserted default company\n\n";
    }
    
    // Re-enable FK check
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Re-enabled foreign key checks\n\n";
    
    // Show result
    echo "📊 Current Companies Data:\n";
    echo str_repeat("-", 80) . "\n";
    
    $companies = $db->query("SELECT * FROM companies")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($companies as $company) {
        echo "ID: {$company['id']}\n";
        echo "Name: {$company['company_name']}\n";
        echo "Code: {$company['company_code']}\n";
        echo "Active: " . ($company['is_active'] ? 'Yes' : 'No') . "\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    echo "\n✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "\n➡️  Go to: <a href='modules/company/companies.php'>Companies Page</a>\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n🔧 Try running this SQL manually in phpMyAdmin:\n\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n";
    echo "DROP TABLE IF EXISTS companies;\n";
    echo "-- Then run the CREATE TABLE statement\n";
}

echo "</pre>";
echo "</body></html>";
?>
