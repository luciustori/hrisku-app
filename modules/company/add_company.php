<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Tambah Perusahaan';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = sanitize($_POST['company_name']);
    $company_code = sanitize($_POST['company_code']);
    $tax_id = sanitize($_POST['tax_id']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $website = sanitize($_POST['website']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle logo upload
    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['logo'], '../../uploads/companies/', ['jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            $logo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $query = "INSERT INTO companies (company_name, company_code, tax_id, address, phone, email, website, logo, is_active)
                      VALUES (:name, :code, :tax_id, :address, :phone, :email, :website, :logo, :active)";
            
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
                'active' => $is_active
            ]);
            
            $success = 'Perusahaan berhasil ditambahkan';
            header('Refresh: 2; URL=companies.php');
            
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
        <h1>Tambah Perusahaan Baru</h1>
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
            <h2>Form Tambah Perusahaan</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Perusahaan *</label>
                        <input type="text" name="company_name" class="form-control" required placeholder="PT Teknologi Indonesia">
                    </div>
                    <div class="form-group">
                        <label>Kode Perusahaan *</label>
                        <input type="text" name="company_code" class="form-control" required placeholder="PTI">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>NPWP</label>
                        <input type="text" name="tax_id" class="form-control" placeholder="00.000.000.0-000.000">
                    </div>
                    <div class="form-group">
                        <label>Telepon *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="021-12345678">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="info@perusahaan.com">
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input type="text" name="website" class="form-control" placeholder="www.perusahaan.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Logo Perusahaan</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-muted">Format: JPG, PNG (Max 2MB)</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Perusahaan Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Perusahaan</button>
                <a href="companies.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
