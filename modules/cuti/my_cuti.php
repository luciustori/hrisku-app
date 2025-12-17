<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// TEMPORARY: Set dummy user
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@hrisku.com',
        'role' => 'super_admin',
        'full_name' => 'Administrator'
    ];
}

checkRole(['super_admin', 'admin', 'hrd', 'karyawan']);

$page_title = 'Cuti Saya';

$database = new Database();
$db = $database->getConnection();

$user = $_SESSION['user'];

// Get employee
$empQuery = "SELECT id FROM employees WHERE email = :email OR employee_code = :code LIMIT 1";
$stmt = $db->prepare($empQuery);
$stmt->execute([
    'email' => $user['email'] ?? $user['username'],
    'code' => $user['username']
]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Get leave requests
$leaveRequests = [];
if ($employee) {
    $query = "SELECT * FROM leave_requests 
              WHERE employee_id = :emp_id 
              ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute(['emp_id' => $employee['id']]);
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Cuti Saya</h1>
        <div>
            <a href="pengajuan.php" class="btn btn-primary">+ Ajukan Cuti</a>
            <a href="index.php" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Riwayat Pengajuan Cuti</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($leaveRequests) > 0): ?>
                        <?php foreach ($leaveRequests as $req): ?>
                        <tr>
                            <td>
                                <?php
                                $leaveTypes = [
                                    'annual' => ['icon' => '🏖️', 'label' => 'Tahunan'],
                                    'sick' => ['icon' => '🤒', 'label' => 'Sakit'],
                                    'emergency' => ['icon' => '🚨', 'label' => 'Darurat'],
                                    'unpaid' => ['icon' => '💸', 'label' => 'Tanpa Gaji'],
                                    'maternity' => ['icon' => '🤱', 'label' => 'Melahirkan'],
                                    'paternity' => ['icon' => '👶', 'label' => 'Ayah']
                                ];
                                $type = $leaveTypes[$req['leave_type']] ?? $leaveTypes['annual'];
                                echo $type['icon'] . ' ' . $type['label'];
                                ?>
                            </td>
                            <td>
                                <?php echo date('d M Y', strtotime($req['start_date'])); ?><br>
                                <small>s/d <?php echo date('d M Y', strtotime($req['end_date'])); ?></small>
                            </td>
                            <td><strong><?php echo $req['total_days']; ?> hari</strong></td>
                            <td>
                                <?php 
                                $reason = $req['reason'] ?? '';
                                echo htmlspecialchars(mb_substr($reason, 0, 50));
                                if (mb_strlen($reason) > 50) echo '...';
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => ['class' => 'badge-kontrak', 'icon' => '⏳', 'label' => 'Pending'],
                                    'approved' => ['class' => 'badge-tetap', 'icon' => '✓', 'label' => 'Disetujui'],
                                    'rejected' => ['class' => 'badge-magang', 'icon' => '✗', 'label' => 'Ditolak']
                                ];
                                $status = $statusClass[$req['status']] ?? $statusClass['pending'];
                                ?>
                                <span class="badge <?php echo $status['class']; ?>">
                                    <?php echo $status['icon'] . ' ' . $status['label']; ?>
                                </span>
                                <?php if ($req['status'] == 'rejected' && !empty($req['rejection_reason'])): ?>
                                    <br><small style="color: #ef4444;"><?php echo htmlspecialchars($req['rejection_reason']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo date('d M Y H:i', strtotime($req['created_at'])); ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">Belum ada pengajuan cuti</p>
                                <a href="pengajuan.php" class="btn btn-primary">+ Ajukan Cuti Pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
