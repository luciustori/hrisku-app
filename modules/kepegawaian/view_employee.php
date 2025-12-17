<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'hrd', 'karyawan']);

$page_title = 'Detail Karyawan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: employees.php');
    exit();
}

// Get employee data with relations
$query = "SELECT e.*, 
          d.department_name, 
          p.position_name, p.level as position_level,
          b.branch_name, b.address as branch_address
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN positions p ON e.position_id = p.id
          LEFT JOIN branches b ON e.branch_id = b.id
          WHERE e.id = :id";

$stmt = $db->prepare($query);
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: employees.php');
    exit();
}

// Calculate age
$age = '';
if (!empty($employee['birth_date'])) {
    $birthDate = new DateTime($employee['birth_date']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y . ' tahun';
}

// Calculate work duration
$workDuration = '';
if (!empty($employee['join_date'])) {
    $joinDate = new DateTime($employee['join_date']);
    $today = new DateTime();
    $diff = $today->diff($joinDate);
    $workDuration = $diff->y . ' tahun ' . $diff->m . ' bulan';
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👤 Detail Karyawan</h1>
        <div>
            <a href="edit_employee.php?id=<?php echo $id; ?>" class="btn btn-primary">✏️ Edit</a>
            <a href="employees.php" class="btn btn-secondary">← Kembali</a>
            <button onclick="window.print()" class="btn" style="background: #10b981;">🖨️ Print</button>
        </div>
    </div>
    
    <!-- Employee Header Card -->
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body" style="padding: 40px;">
            <div style="display: flex; align-items: center; gap: 30px; color: white;">
                <div>
                    <?php if (!empty($employee['photo']) && file_exists('../../uploads/employees/' . $employee['photo'])): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/employees/<?php echo htmlspecialchars($employee['photo']); ?>" 
                             alt="Photo" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 72px; border: 5px solid white;">
                            <?php echo $employee['gender'] == 'male' ? '👨' : '👩'; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="flex: 1;">
                    <h1 style="margin: 0 0 10px 0; font-size: 36px; color: white;">
                        <?php echo htmlspecialchars($employee['full_name'] ?? ''); ?>
                    </h1>
                    <p style="font-size: 18px; margin: 5px 0; opacity: 0.9;">
                        <strong>NIK:</strong> <?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?>
                    </p>
                    <p style="font-size: 18px; margin: 5px 0; opacity: 0.9;">
                        <strong>Jabatan:</strong> <?php echo htmlspecialchars($employee['position_name'] ?? '-'); ?>
                    </p>
                    <p style="font-size: 18px; margin: 5px 0; opacity: 0.9;">
                        <strong>Departemen:</strong> <?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?>
                    </p>
                    <div style="margin-top: 15px;">
                        <?php
                        $statusClass = [
                            'tetap' => ['bg' => '#10b981', 'label' => 'Karyawan Tetap'],
                            'kontrak' => ['bg' => '#f59e0b', 'label' => 'Karyawan Kontrak'],
                            'probation' => ['bg' => '#8b5cf6', 'label' => 'Probation'],
                            'magang' => ['bg' => '#3b82f6', 'label' => 'Magang']
                        ];
                        $status = $employee['employment_status'] ?? 'kontrak';
                        $statusInfo = $statusClass[$status] ?? $statusClass['kontrak'];
                        ?>
                        <span style="background: <?php echo $statusInfo['bg']; ?>; padding: 8px 20px; border-radius: 20px; font-weight: bold; font-size: 16px;">
                            <?php echo $statusInfo['label']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Information Grid -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
        <!-- Data Pribadi -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Data Pribadi</h2>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <label>Nama Lengkap</label>
                    <p><?php echo htmlspecialchars($employee['full_name'] ?? '-'); ?></p>
                </div>
                
                <div class="info-item">
                    <label>Jenis Kelamin</label>
                    <p><?php echo $employee['gender'] == 'male' ? '👨 Laki-laki' : '👩 Perempuan'; ?></p>
                </div>
                
                <div class="info-item">
                    <label>Tempat, Tanggal Lahir</label>
                    <p>
                        <?php 
                        echo htmlspecialchars($employee['birth_place'] ?? '-');
                        if (!empty($employee['birth_date'])) {
                            echo ', ' . date('d F Y', strtotime($employee['birth_date']));
                            echo ' (' . $age . ')';
                        }
                        ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Email</label>
                    <p>
                        <?php if (!empty($employee['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>">
                                📧 <?php echo htmlspecialchars($employee['email']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Nomor Telepon</label>
                    <p>
                        <?php if (!empty($employee['phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>">
                                📞 <?php echo htmlspecialchars($employee['phone']); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Alamat</label>
                    <p><?php echo nl2br(htmlspecialchars($employee['address'] ?? '-')); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Data Pekerjaan -->
        <div class="card">
            <div class="card-header">
                <h2>💼 Data Pekerjaan</h2>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <label>NIK / Kode Karyawan</label>
                    <p><strong><?php echo htmlspecialchars($employee['employee_code'] ?? '-'); ?></strong></p>
                </div>
                
                <div class="info-item">
                    <label>Jabatan</label>
                    <p>
                        <?php echo htmlspecialchars($employee['position_name'] ?? '-'); ?>
                        <?php if (!empty($employee['position_level'])): ?>
                            <span class="badge badge-kontrak"><?php echo ucfirst($employee['position_level']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Departemen</label>
                    <p><?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></p>
                </div>
                
                <div class="info-item">
                    <label>Cabang</label>
                    <p>
                        <?php echo htmlspecialchars($employee['branch_name'] ?? '-'); ?>
                        <?php if (!empty($employee['branch_address'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($employee['branch_address']); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Status Kepegawaian</label>
                    <p>
                        <?php
                        $statusClass = [
                            'tetap' => 'badge-tetap',
                            'kontrak' => 'badge-kontrak',
                            'probation' => 'badge-probation',
                            'magang' => 'badge-magang'
                        ];
                        $status = $employee['employment_status'] ?? 'kontrak';
                        ?>
                        <span class="badge <?php echo $statusClass[$status] ?? 'badge-kontrak'; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </p>
                </div>
                
                <div class="info-item">
                    <label>Tanggal Bergabung</label>
                    <p>
                        <?php 
                        if (!empty($employee['join_date'])) {
                            echo date('d F Y', strtotime($employee['join_date']));
                            echo '<br><small style="color: #666;">Masa kerja: ' . $workDuration . '</small>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </p>
                </div>
                
                <?php if (!empty($employee['end_date'])): ?>
                <div class="info-item">
                    <label>Tanggal Berakhir Kontrak</label>
                    <p style="color: #ef4444; font-weight: bold;">
                        <?php echo date('d F Y', strtotime($employee['end_date'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Timeline Card -->
    <div class="card">
        <div class="card-header">
            <h2>📅 Timeline</h2>
        </div>
        <div class="card-body">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker" style="background: #10b981;">✓</div>
                    <div class="timeline-content">
                        <h4>Bergabung</h4>
                        <p><?php echo !empty($employee['join_date']) ? date('d F Y', strtotime($employee['join_date'])) : '-'; ?></p>
                    </div>
                </div>
                
                <?php if (!empty($employee['end_date'])): ?>
                <div class="timeline-item">
                    <div class="timeline-marker" style="background: #ef4444;">⏰</div>
                    <div class="timeline-content">
                        <h4>Berakhir Kontrak</h4>
                        <p><?php echo date('d F Y', strtotime($employee['end_date'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="timeline-item">
                    <div class="timeline-marker" style="background: #3b82f6;">📝</div>
                    <div class="timeline-content">
                        <h4>Data Terakhir Diupdate</h4>
                        <p><?php echo !empty($employee['updated_at']) ? date('d F Y H:i', strtotime($employee['updated_at'])) : '-'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-item {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.info-item label {
    display: block;
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
    font-weight: 600;
}

.info-item p {
    margin: 0;
    font-size: 16px;
    color: #1f2937;
    font-weight: 500;
}

.info-item a {
    color: #3b82f6;
    text-decoration: none;
}

.info-item a:hover {
    text-decoration: underline;
}

.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    border: 4px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.timeline-content h4 {
    margin: 0 0 5px 0;
    color: #1f2937;
    font-size: 16px;
}

.timeline-content p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

@media print {
    .page-header .btn, .card-header, .timeline::before {
        display: none !important;
    }
    
    .card {
        page-break-inside: avoid;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
