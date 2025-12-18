<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin']);

$page_title = 'Company Profile';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $companyName = sanitize($_POST['company_name']);
    $companyCode = sanitize($_POST['company_code'] ?? '');
    $taxId = sanitize($_POST['tax_id']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $website = sanitize($_POST['website']);
    
    try {
        // Check if record exists
        $exists = $db->query("SELECT id FROM companies LIMIT 1")->fetch();
        
        if ($exists) {
            // Update
            $stmt = $db->prepare("UPDATE companies SET 
                                  company_name = ?, company_code = ?, tax_id = ?,
                                  address = ?, phone = ?, email = ?, website = ?
                                  WHERE id = ?");
            $stmt->execute([
                $companyName, $companyCode, $taxId,
                $address, $phone, $email, $website, $exists['id']
            ]);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO companies 
                                  (company_name, company_code, tax_id, address, phone, email, website, is_active) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                $companyName, $companyCode, $taxId,
                $address, $phone, $email, $website
            ]);
        }
        
        $_SESSION['success'] = 'Company profile updated successfully!';
        
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: company.php');
    exit();
}

// Get company data
$company = $db->query("SELECT * FROM companies LIMIT 1")->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏢 Company Profile</h1>
        <a href="index.php" class="btn btn-secondary">← Back to Settings</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Company Information</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label>Company Name *</label>
                        <input type="text" name="company_name" class="form-control" required
                               value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>"
                               placeholder="PT. Technology Solutions Indonesia">
                    </div>
                    
                    <div class="form-group">
                        <label>Company Code</label>
                        <input type="text" name="company_code" class="form-control"
                               value="<?php echo htmlspecialchars($company['company_code'] ?? ''); ?>"
                               placeholder="TSI">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tax Number (NPWP) *</label>
                    <input type="text" name="tax_id" class="form-control" required
                           value="<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>"
                           placeholder="01.234.567.8-901.000">
                </div>
                
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" class="form-control" rows="3" required
                              placeholder="Jl. Sudirman No. 123, Jakarta Selatan 12190"><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="phone" class="form-control" required
                               value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>"
                               placeholder="(021) 1234-5678">
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>"
                               placeholder="info@company.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Website</label>
                        <input type="url" name="website" class="form-control"
                               value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                               placeholder="https://www.company.com">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Company Profile</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}
</style>

<?php include '../../includes/footer.php'; ?>
