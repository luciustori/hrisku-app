<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Edit Jabatan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: manage_positions.php');
    exit();
}

$error = '';
$success = '';

// Get position data
$stmt = $db->prepare("SELECT * FROM positions WHERE id = :id");
$stmt->execute(['id' => $id]);
$position = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$position) {
    header('Location: manage_positions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $position_name = sanitize($_POST['position_name']);
    $level = sanitize($_POST['level']);
    $description = sanitize($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "UPDATE positions SET
                  position_name = :name,
                  level = :level,
                  description = :desc,
                  is_active = :active,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'name' => $position_name,
            'level' => $level,
            'desc' => $description,
            'active' => $is_active,
            'id' => $id
        ]);
        
        $success = 'Data jabatan berhasil diupdate';
        
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM positions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $position = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Edit Jabatan</h1>
        <a href="manage_positions.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Form Edit Jabatan</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Jabatan *</label>
                        <input type="text" name="position_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($position['position_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Level *</label>
                        <select name="level" class="form-control" required>
                            <option value="executive" <?php echo $position['level'] == 'executive' ? 'selected' : ''; ?>>Executive</option>
                            <option value="manager" <?php echo $position['level'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="supervisor" <?php echo $position['level'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="staff" <?php echo $position['level'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="intern" <?php echo $position['level'] == 'intern' ? 'selected' : ''; ?>>Intern</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($position['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $position['is_active'] ? 'checked' : ''; ?>>
                        Jabatan Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Jabatan</button>
                <a href="manage_positions.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
