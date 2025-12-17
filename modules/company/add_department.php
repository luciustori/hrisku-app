<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Tambah Departemen';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_code = sanitize($_POST['department_code'] ?? '');
    $department_name = sanitize($_POST['department_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "INSERT INTO departments (department_code, department_name, description, is_active)
                  VALUES (:code, :name, :desc, :active)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'code' => $department_code,
            'name' => $department_name,
            'desc' => $description,
            'active' => $is_active
        ]);
        
        $success = 'Departemen berhasil ditambahkan';
        header('Refresh: 2; URL=departments.php');
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = 'Kode departemen sudah digunakan. Gunakan kode lain.';
        } else {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Tambah Departemen Baru</h1>
        <a href="departments.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Form Tambah Departemen</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Departemen *</label>
                        <input type="text" name="department_code" class="form-control" required 
                               placeholder="Contoh: IT, HR, FIN" maxlength="50">
                        <small class="text-muted">Kode unik departemen (max 50 karakter)</small>
                    </div>
                    <div class="form-group">
                        <label>Nama Departemen *</label>
                        <input type="text" name="department_name" class="form-control" required 
                               placeholder="Contoh: Teknologi Informasi">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Deskripsi singkat departemen (opsional)"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Departemen Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Simpan Departemen</button>
                <a href="departments.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
