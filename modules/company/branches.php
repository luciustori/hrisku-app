<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Daftar Cabang';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        // Check if branch is being used
        $checkEmp = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE branch_id = :id");
        $checkEmp->execute(['id' => $id]);
        $empCount = $checkEmp->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($empCount > 0) {
            $error = 'Tidak dapat menghapus cabang ini karena masih terhubung dengan ' . $empCount . ' karyawan. Silakan nonaktifkan saja.';
        } else {
            $stmt = $db->prepare("DELETE FROM branches WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = 'Cabang berhasil dihapus';
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
        $stmt = $db->prepare("UPDATE branches SET is_active = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        $success = $status ? 'Cabang berhasil diaktifkan' : 'Cabang berhasil dinonaktifkan';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all branches
$branches = $db->query("SELECT * FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏪 Daftar Cabang</h1>
        <div>
            <a href="add_branch.php" class="btn btn-primary">+ Tambah Cabang</a>
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
            <h2>Daftar Cabang/Lokasi</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Cabang</th>
                        <th>Alamat</th>
                        <th>Kontak</th>
                        <th>Koordinat</th>
                        <th>Radius</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($branches) > 0): ?>
                        <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($branch['branch_code'] ?? ''); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($branch['branch_name'] ?? ''); ?></strong></td>
                            <td>
                                <?php 
                                $address = $branch['address'] ?? '';
                                if ($address) {
                                    echo htmlspecialchars(mb_substr($address, 0, 40)); 
                                    if (mb_strlen($address) > 40) echo '...';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <small>
                                    📞 <?php echo htmlspecialchars($branch['phone'] ?? '-'); ?><br>
                                    <?php if (!empty($branch['email'])): ?>
                                        📧 <?php echo htmlspecialchars($branch['email']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <small>
                                    Lat: <?php echo $branch['latitude'] ?? '0'; ?><br>
                                    Lng: <?php echo $branch['longitude'] ?? '0'; ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-kontrak"><?php echo ($branch['radius_meter'] ?? 100); ?>m</span>
                            </td>
                            <td>
                                <?php 
                                $isActive = isset($branch['is_active']) ? $branch['is_active'] : 1;
                                if ($isActive): 
                                ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-probation">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn-action btn-edit">Edit</a>
                                
                                <?php if ($isActive): ?>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Nonaktifkan cabang ini?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                                        <input type="hidden" name="status" value="0">
                                        <button type="submit" class="btn-action" style="background: #f59e0b;">Nonaktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" class="btn-action" style="background: #10b981;">Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin hapus cabang ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">Belum ada data cabang</p>
                                <a href="add_branch.php" class="btn btn-primary">+ Tambah Cabang Pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
