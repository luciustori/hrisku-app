<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Edit Departemen';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: departments.php');
    exit();
}

$error = '';
$success = '';

// Get department data
$stmt = $db->prepare("SELECT * FROM departments WHERE id = :id");
$stmt->execute(['id' => $id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    header('Location: departments.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_code = sanitize($_POST['department_code'] ?? '');
    $department_name = sanitize($_POST['department_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "UPDATE departments SET
                  department_code = :code,
                  department_name = :name,
                  description = :desc,
                  is_active = :active,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'code' => $department_code,
            'name' => $department_name,
            'desc' => $description,
            'active' => $is_active,
            'id' => $id
        ]);
        
        $success = 'Data departemen berhasil diupdate';
        
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM departments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $department = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Edit Departemen</h1>
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
            <h2>Form Edit Departemen</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Departemen *</label>
                        <input type="text" name="department_code" class="form-control" required 
                               value="<?php echo htmlspecialchars($department['department_code'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Departemen *</label>
                        <input type="text" name="department_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($department['department_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($department['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($department['is_active']) ? 'checked' : ''; ?>>
                        Departemen Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Departemen</button>
                <a href="departments.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
