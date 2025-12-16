<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Detail Karyawan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    redirect(BASE_URL . 'modules/kepegawaian/index.php');
}

// Query detail karyawan
$query = "SELECT e.*, d.department_name, p.position_name, p.level_code, b.branch_name
          FROM employees e
          JOIN departments d ON e.department_id = d.id
          JOIN positions p ON e.position_id = p.id
          JOIN branches b ON e.branch_id = b.id
          WHERE e.id = :id";

$stmt = $db->prepare($query);
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect(BASE_URL . 'modules/kepegawaian/index.php');
}

// Mapping level
$levels = [
    1 => 'Direktur',
    2 => 'Manager',
    3 => 'Supervisor',
    4 => 'Koordinator',
    5 => 'Staff'
];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Detail Karyawan</h1>
        <div>
            <a href="edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-warning">Edit Data</a>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <div class="profile-container">
        <div class="profile-sidebar">
            <div class="profile-photo">
                <?php if($employee['photo']): ?>
                    <img src="<?php echo BASE_URL . 'uploads/employees/' . $employee['photo']; ?>" alt="Photo">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span><?php echo strtoupper(substr($employee['full_name'], 0, 2)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-name">
                <h2><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                <p class="profile-nik">NIK: <?php echo htmlspecialchars($employee['nik']); ?></p>
            </div>
            <div class="profile-status">
                <span class="badge badge-<?php echo $employee['employment_status']; ?>">
                    <?php echo ucfirst($employee['employment_status']); ?>
                </span>
            </div>
        </div>
        
        <div class="profile-content">
            <div class="card">
                <div class="card-header">
                    <h3>Data Pribadi</h3>
                </div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Nama Lengkap</label>
                            <p><?php echo htmlspecialchars($employee['full_name']); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Nomor KTP</label>
                            <p><?php echo htmlspecialchars($employee['ktp_number']); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Tempat, Tanggal Lahir</label>
                            <p>
                                <?php 
                                echo htmlspecialchars($employee['birth_place'] ?: '-');
                                if ($employee['birth_date']) {
                                    echo ', ' . formatDate($employee['birth_date']);
                                }
                                ?>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label>Jenis Kelamin</label>
                            <p><?php echo $employee['gender'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Alamat</label>
                            <p><?php echo htmlspecialchars($employee['address'] ?: '-'); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Telepon</label>
                            <p><?php echo htmlspecialchars($employee['phone'] ?: '-'); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($employee['email'] ?: '-'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Data Kepegawaian</h3>
                </div>
                <div class="card-body">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Departemen</label>
                            <p><strong><?php echo htmlspecialchars($employee['department_name']); ?></strong></p>
                        </div>
                        <div class="detail-item">
                            <label>Jabatan</label>
                            <p>
                                <strong><?php echo htmlspecialchars($employee['position_name']); ?></strong>
                                <span class="text-muted"> (<?php echo $levels[$employee['level_code']]; ?>)</span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label>Cabang</label>
                            <p><?php echo htmlspecialchars($employee['branch_name']); ?></p>
                        </div>
                        <div class="detail-item">
                            <label>Status Kepegawaian</label>
                            <p>
                                <span class="badge badge-<?php echo $employee['employment_status']; ?>">
                                    <?php echo ucfirst($employee['employment_status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label>Tanggal Bergabung</label>
                            <p><?php echo formatDate($employee['join_date']); ?></p>
                        </div>
                        <?php if($employee['contract_end_date']): ?>
                        <div class="detail-item">
                            <label>Tanggal Akhir Kontrak</label>
                            <p><?php echo formatDate($employee['contract_end_date']); ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <label>Masa Kerja</label>
                            <p>
                                <?php
                                $join = new DateTime($employee['join_date']);
                                $now = new DateTime();
                                $diff = $join->diff($now);
                                echo $diff->y . ' tahun ' . $diff->m . ' bulan';
                                ?>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label>Status Akun</label>
                            <p>
                                <?php if($employee['is_active']): ?>
                                    <span class="badge badge-tetap">Aktif</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ccc;">Nonaktif</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
