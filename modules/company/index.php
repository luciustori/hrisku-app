<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Data Perusahaan';

$database = new Database();
$db = $database->getConnection();

// Get company data
$company = $db->query("SELECT * FROM companies LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get branches
$branches = $db->query("SELECT * FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

// Get departments
$departments = $db->query("SELECT d.*, COUNT(e.id) as employee_count 
                           FROM departments d 
                           LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
                           GROUP BY d.id 
                           ORDER BY d.department_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Data Perusahaan</h1>
    </div>
    
    <!-- Company Info -->
    <div class="card">
        <div class="card-header">
            <h2>Informasi Perusahaan</h2>
            <?php if($company): ?>
                <a href="edit_company.php" class="btn btn-primary btn-sm">Edit</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if($company): ?>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Nama Perusahaan</label>
                        <p><strong><?php echo htmlspecialchars($company['company_name']); ?></strong></p>
                    </div>
                    <div class="detail-item">
                        <label>NPWP</label>
                        <p><?php echo htmlspecialchars($company['npwp'] ?: '-'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Alamat</label>
                        <p><?php echo htmlspecialchars($company['company_address'] ?: '-'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Telepon</label>
                        <p><?php echo htmlspecialchars($company['company_phone'] ?: '-'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($company['company_email'] ?: '-'); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Belum ada data perusahaan</p>
                <a href="edit_company.php" class="btn btn-primary">Tambah Data Perusahaan</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Branches -->
    <div class="card">
        <div class="card-header">
            <h2>Cabang Perusahaan</h2>
            <a href="add_branch.php" class="btn btn-primary btn-sm">+ Tambah Cabang</a>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Cabang</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th>Koordinat</th>
                        <th>Radius</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($branches) > 0): ?>
                        <?php foreach($branches as $branch): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($branch['branch_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($branch['branch_address']); ?></td>
                            <td><?php echo htmlspecialchars($branch['branch_phone'] ?: '-'); ?></td>
                            <td>
                                <small><?php echo $branch['latitude']; ?>, <?php echo $branch['longitude']; ?></small>
                            </td>
                            <td><?php echo $branch['radius_meter']; ?>m</td>
                            <td>
                                <?php if($branch['is_active']): ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ccc;">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_branch.php?id=<?php echo $branch['id']; ?>" class="btn-action btn-edit">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Belum ada data cabang</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Departments -->
    <div class="card">
        <div class="card-header">
            <h2>Departemen</h2>
            <a href="add_department.php" class="btn btn-primary btn-sm">+ Tambah Departemen</a>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Departemen</th>
                        <th>Deskripsi</th>
                        <th>Jumlah Karyawan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($departments) > 0): ?>
                        <?php foreach($departments as $dept): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dept['description'] ?: '-'); ?></td>
                            <td><?php echo $dept['employee_count']; ?> orang</td>
                            <td>
                                <?php if($dept['is_active']): ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ccc;">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_department.php?id=<?php echo $dept['id']; ?>" class="btn-action btn-edit">Edit</a>
                                    <a href="manage_positions.php?dept_id=<?php echo $dept['id']; ?>" class="btn-action btn-view">Jabatan</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Belum ada data departemen</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
