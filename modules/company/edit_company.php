<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Edit Perusahaan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: companies.php');
    exit();
}

$error = '';
$success = '';

// Get company data
$stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
$stmt->execute(['id' => $id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: companies.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = sanitize($_POST['company_name'] ?? '');
    $company_code = sanitize($_POST['company_code'] ?? '');
    $tax_id = sanitize($_POST['tax_id'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle logo upload
    $logo = $company['logo'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['logo'], '../../uploads/companies/', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            // Delete old logo
            if (!empty($company['logo'])) {
                $oldLogoPath = '../../uploads/companies/' . $company['logo'];
                if (file_exists($oldLogoPath)) {
                    unlink($oldLogoPath);
                }
            }
            $logo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $query = "UPDATE companies SET
                      company_name = :name,
                      company_code = :code,
                      tax_id = :tax_id,
                      address = :address,
                      phone = :phone,
                      email = :email,
                      website = :website,
                      logo = :logo,
                      is_active = :active,
                      updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                'name' => $company_name,
                'code' => $company_code,
                'tax_id' => $tax_id,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'website' => $website,
                'logo' => $logo,
                'active' => $is_active,
                'id' => $id
            ]);
            
            $success = 'Data perusahaan berhasil diupdate';
            
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM companies WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Edit Perusahaan</h1>
        <a href="companies.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Form Edit Perusahaan</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Perusahaan *</label>
                        <input type="text" name="company_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Kode Perusahaan *</label>
                        <input type="text" name="company_code" class="form-control" required 
                               value="<?php echo htmlspecialchars($company['company_code'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>NPWP</label>
                        <input type="text" name="tax_id" class="form-control" 
                               value="<?php echo htmlspecialchars($company['tax_id'] ?? ''); ?>"
                               placeholder="00.000.000.0-000.000">
                    </div>
                    <div class="form-group">
                        <label>Telepon *</label>
                        <input type="text" name="phone" class="form-control" required 
                               value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>"
                               placeholder="021-12345678">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>"
                               placeholder="info@perusahaan.com">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" class="form-control" 
                               value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                               placeholder="www.perusahaan.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Logo Perusahaan</label>
                    <?php if (!empty($company['logo'])): ?>
                        <div style="margin-bottom: 10px;">
                            <?php 
                            $logoPath = '../../uploads/companies/' . $company['logo'];
                            if (file_exists($logoPath)): 
                            ?>
                                <img src="<?php echo BASE_URL; ?>uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" 
                                     alt="Logo" style="max-height: 100px; border: 1px solid #ddd; padding: 5px;">
                            <?php else: ?>
                                <p class="text-muted">Logo tidak ditemukan</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-muted">Format: JPG, PNG (Max 5MB). Kosongkan jika tidak ingin mengubah logo.</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($company['is_active']) ? 'checked' : ''; ?>>
                        Perusahaan Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Update Perusahaan</button>
                <a href="companies.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
