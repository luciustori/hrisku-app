<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Daftar Departemen';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        // Check if department is being used
        $checkEmp = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE department_id = :id");
        $checkEmp->execute(['id' => $id]);
        $empCount = $checkEmp->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($empCount > 0) {
            $error = 'Tidak dapat menghapus departemen ini karena masih terhubung dengan ' . $empCount . ' karyawan. Silakan nonaktifkan saja.';
        } else {
            $stmt = $db->prepare("DELETE FROM departments WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = 'Departemen berhasil dihapus';
        }
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle Toggle Active
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle') {
    $id = (int)$_POST['id'];
    $status = (int)$_POST['status'];
    
    try {
        $stmt = $db->prepare("UPDATE departments SET is_active = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        $success = $status ? 'Departemen berhasil diaktifkan' : 'Departemen berhasil dinonaktifkan';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all departments with employee count
$query = "SELECT d.*, 
          COUNT(e.id) as employee_count
          FROM departments d
          LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
          GROUP BY d.id
          ORDER BY d.department_name";

$departments = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏛️ Daftar Departemen</h1>
        <div>
            <a href="add_department.php" class="btn btn-primary">+ Tambah Departemen</a>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Daftar Departemen</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Departemen</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Karyawan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($departments) > 0): ?>
                        <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department_code'] ?? ''); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($dept['department_name'] ?? ''); ?></strong></td>
                            <td>
                                <?php 
                                $desc = $dept['description'] ?? '';
                                if ($desc) {
                                    echo htmlspecialchars(mb_substr($desc, 0, 50)); 
                                    if (mb_strlen($desc) > 50) echo '...';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-kontrak">
                                    <?php echo $dept['employee_count']; ?> orang
                                </span>
                            </td>
                            <td>
                                <?php 
                                $isActive = isset($dept['is_active']) ? $dept['is_active'] : 1;
                                if ($isActive): 
                                ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-probation">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_department.php?id=<?php echo $dept['id']; ?>" class="btn-action btn-edit">Edit</a>
                                
                                <?php if ($isActive): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Nonaktifkan departemen ini?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                        <input type="hidden" name="status" value="0">
                                        <button type="submit" class="btn-action" style="background: #f59e0b;">Nonaktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" class="btn-action" style="background: #10b981;">Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($dept['employee_count'] == 0): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin hapus departemen ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $dept['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete">Hapus</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-action" style="background: #9ca3af; cursor: not-allowed;" disabled title="Tidak bisa dihapus, masih ada karyawan">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">Belum ada data departemen</p>
                                <a href="add_department.php" class="btn btn-primary">+ Tambah Departemen Pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
