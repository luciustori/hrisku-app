<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Cek akses (hanya super_admin dan admin)
checkRole(['super_admin', 'admin']);

$page_title = 'Data Kepegawaian';

$database = new Database();
$db = $database->getConnection();

// Query data karyawan
$query = "SELECT e.*, d.department_name, p.position_name, b.branch_name
          FROM employees e
          JOIN departments d ON e.department_id = d.id
          JOIN positions p ON e.position_id = p.id
          JOIN branches b ON e.branch_id = b.id
          WHERE e.is_active = 1
          ORDER BY e.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Data Kepegawaian</h1>
        <a href="add.php" class="btn btn-primary">+ Tambah Karyawan</a>
    </div>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
            if($_GET['success'] == 'add') echo 'Karyawan berhasil ditambahkan';
            if($_GET['success'] == 'edit') echo 'Data karyawan berhasil diupdate';
            if($_GET['success'] == 'delete') echo 'Karyawan berhasil dihapus';
            ?>
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama Lengkap</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($employees) > 0): ?>
                    <?php foreach($employees as $emp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($emp['nik']); ?></td>
                        <td><strong><?php echo htmlspecialchars($emp['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($emp['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($emp['position_name']); ?></td>
                        <td><?php echo htmlspecialchars($emp['branch_name']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $emp['employment_status']; ?>">
                                <?php echo ucfirst($emp['employment_status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="view.php?id=<?php echo $emp['id']; ?>" class="btn-action btn-view">View</a>
                                <a href="edit.php?id=<?php echo $emp['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="delete.php?id=<?php echo $emp['id']; ?>" 
                                   class="btn-action btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus karyawan ini?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <p style="color: #999;">Belum ada data karyawan</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top: 10px;">Tambah Karyawan Pertama</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
