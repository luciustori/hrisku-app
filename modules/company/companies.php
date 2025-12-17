<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Daftar Perusahaan';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Delete with Validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        // Check dependencies
        $dependencies = [];
        
        // Check branches
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM branches WHERE company_id = :id");
        $stmt->execute(['id' => $id]);
        $branchCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($branchCount > 0) {
            $dependencies[] = $branchCount . ' cabang';
        }
        
        // Check departments (if they have company_id)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM departments WHERE company_id = :id");
        $stmt->execute(['id' => $id]);
        $deptCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($deptCount > 0) {
            $dependencies[] = $deptCount . ' departemen';
        }
        
        // Check employees (if they have company_id)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE company_id = :id");
        $stmt->execute(['id' => $id]);
        $empCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($empCount > 0) {
            $dependencies[] = $empCount . ' karyawan';
        }
        
        if (count($dependencies) > 0) {
            // Cannot delete - has dependencies
            $error = 'Tidak dapat menghapus perusahaan ini karena masih terhubung dengan: ' . implode(', ', $dependencies) . 
                    '. <br>Silakan nonaktifkan saja atau hapus data terkait terlebih dahulu.';
        } else {
            // Safe to delete
            $stmt = $db->prepare("SELECT logo FROM companies WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete logo file if exists
            if ($company && !empty($company['logo'])) {
                $logoPath = '../../uploads/companies/' . $company['logo'];
                if (file_exists($logoPath)) {
                    unlink($logoPath);
                }
            }
            
            // Delete company
            $stmt = $db->prepare("DELETE FROM companies WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = 'Perusahaan berhasil dihapus';
        }
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle Toggle Active Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle') {
    $id = (int)$_POST['id'];
    $status = (int)$_POST['status'];
    
    try {
        $stmt = $db->prepare("UPDATE companies SET is_active = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        $success = $status ? 'Perusahaan berhasil diaktifkan' : 'Perusahaan berhasil dinonaktifkan';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all companies
$companies = $db->query("SELECT * FROM companies ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏢 Daftar Perusahaan</h1>
        <div>
            <a href="add_company.php" class="btn btn-primary">+ Tambah Perusahaan</a>
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
            <h2>Daftar Perusahaan</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Kode</th>
                        <th>Nama Perusahaan</th>
                        <th>NPWP</th>
                        <th>Alamat</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($companies) > 0): ?>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <?php if (!empty($company['logo']) && file_exists('../../uploads/companies/' . $company['logo'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" 
                                         alt="Logo" style="max-height: 40px;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                        <span style="font-size: 20px;">🏢</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($company['company_code'] ?? ''); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($company['company_name'] ?? ''); ?></strong></td>
                            <td><?php echo htmlspecialchars($company['tax_id'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $address = $company['address'] ?? '';
                                if ($address) {
                                    echo htmlspecialchars(mb_substr($address, 0, 50)); 
                                    if (mb_strlen($address) > 50) echo '...';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <small>
                                    📞 <?php echo htmlspecialchars($company['phone'] ?? '-'); ?><br>
                                    <?php if (!empty($company['email'])): ?>
                                        📧 <?php echo htmlspecialchars($company['email']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php 
                                $isActive = isset($company['is_active']) ? $company['is_active'] : 1;
                                if ($isActive): 
                                ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-probation">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_company.php?id=<?php echo $company['id']; ?>" class="btn-action btn-edit">Edit</a>
                                
                                <?php if ($isActive): ?>
                                    <!-- Toggle to Inactive -->
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Nonaktifkan perusahaan ini?')">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                        <input type="hidden" name="status" value="0">
                                        <button type="submit" class="btn-action" style="background: #f59e0b;">Nonaktifkan</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Toggle to Active -->
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                        <input type="hidden" name="status" value="1">
                                        <button type="submit" class="btn-action" style="background: #10b981;">Aktifkan</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin hapus perusahaan ini? (akan dicek dependensi)')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $company['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">Belum ada data perusahaan</p>
                                <a href="add_company.php" class="btn btn-primary">+ Tambah Perusahaan Pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
