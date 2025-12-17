<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Kelola Jabatan';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $position_name = sanitize($_POST['position_name'] ?? '');
    $level = sanitize($_POST['level'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "INSERT INTO positions (position_name, level, description, is_active)
                  VALUES (:name, :level, :desc, :active)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'name' => $position_name,
            'level' => $level,
            'desc' => $description,
            'active' => $is_active
        ]);
        
        $success = 'Jabatan berhasil ditambahkan';
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        // Check if position is being used
        $checkEmp = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE position_id = :id");
        $checkEmp->execute(['id' => $id]);
        $empCount = $checkEmp->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($empCount > 0) {
            $error = 'Tidak dapat menghapus jabatan ini karena masih digunakan oleh ' . $empCount . ' karyawan.';
        } else {
            $stmt = $db->prepare("DELETE FROM positions WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = 'Jabatan berhasil dihapus';
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
        $stmt = $db->prepare("UPDATE positions SET is_active = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        $success = $status ? 'Jabatan berhasil diaktifkan' : 'Jabatan berhasil dinonaktifkan';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all positions with employee count
$query = "SELECT p.*, 
          COUNT(e.id) as employee_count
          FROM positions p
          LEFT JOIN employees e ON p.id = e.position_id AND e.is_active = 1
          GROUP BY p.id
          ORDER BY 
            CASE p.level
                WHEN 'executive' THEN 1
                WHEN 'manager' THEN 2
                WHEN 'supervisor' THEN 3
                WHEN 'staff' THEN 4
                WHEN 'intern' THEN 5
                ELSE 6
            END,
            p.position_name";

$positions = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👔 Kelola Jabatan</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Add Position Form -->
    <div class="card">
        <div class="card-header">
            <h2>Tambah Jabatan Baru</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Jabatan *</label>
                        <input type="text" name="position_name" class="form-control" required 
                               placeholder="Contoh: Direktur IT, Manager Finance">
                    </div>
                    <div class="form-group">
                        <label>Level *</label>
                        <select name="level" class="form-control" required>
                            <option value="">Pilih Level</option>
                            <option value="executive">Executive (C-Level)</option>
                            <option value="manager">Manager</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="staff">Staff</option>
                            <option value="intern">Intern/Magang</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2" 
                              placeholder="Deskripsi singkat jabatan (opsional)"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Jabatan Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Tambah Jabatan</button>
            </form>
        </div>
    </div>
    
    <!-- Positions List -->
    <div class="card">
        <div class="card-header">
            <h2>Daftar Jabatan</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Jabatan</th>
                        <th>Level</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Karyawan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($positions) > 0): ?>
                        <?php foreach ($positions as $position): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($position['position_name'] ?? ''); ?></strong></td>
                            <td>
                                <?php
                                $levelBadge = [
                                    'executive' => ['class' => 'badge-tetap', 'icon' => '👑', 'label' => 'Executive'],
                                    'manager' => ['class' => 'badge-kontrak', 'icon' => '🎯', 'label' => 'Manager'],
                                    'supervisor' => ['class' => 'badge-probation', 'icon' => '📊', 'label' => 'Supervisor'],
                                    'staff' => ['class' => 'badge-magang', 'icon' => '👤', 'label' => 'Staff'],
                                    'intern' => ['class' => 'badge-warning', 'icon' => '🎓', 'label' => 'Intern']
                                ];
                                
                                $level = $position['level'] ?? 'staff';
                                $badge = $levelBadge[$level] ?? $levelBadge['staff'];
                                ?>
                                <span class="badge <?php echo $badge['class']; ?>">
                                    <?php echo $badge['icon']; ?> <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $desc = $position['description'] ?? '';
                                if ($desc) {
                                    echo htmlspecialchars(mb_substr($desc, 0, 40)); 
                                    if (mb_strlen($desc) > 40) echo '...';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-kontrak">
                                    <?php echo $position['employee_count']; ?> orang
                                </span>
                            </td>
                            <td>
                                <?php 
                                $isActive = isset($position['is_active']) ? $position['is_active'] : 1;
                                if ($isActive): 
                                ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-probation">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_position.php?id=<?php echo $position['id']; ?>" class="btn-action btn-edit">Edit</a>
                                
                                <?php if ($isActive): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Nonaktifkan jabatan ini?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $position['id']; ?>">
                                        <input type="hidden" name="status" value="0">
                                        <button type="submit" class="btn-action" style="background: #f59e0b;">Nonaktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $position['id']; ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" class="btn-action" style="background: #10b981;">Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($position['employee_count'] == 0): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin hapus jabatan ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $position['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete">Hapus</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-action" style="background: #9ca3af; cursor: not-allowed;" disabled title="Tidak bisa dihapus, sedang digunakan">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">Belum ada data jabatan</p>
                                <p style="color: #666;">Gunakan form di atas untuk menambahkan jabatan pertama</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
