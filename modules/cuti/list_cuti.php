<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// TEMPORARY
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Semua Cuti';

$database = new Database();
$db = $database->getConnection();

// Get all leave requests
$query = "SELECT lr.*, e.full_name, e.employee_code, d.department_name
          FROM leave_requests lr
          JOIN employees e ON lr.employee_id = e.id
          LEFT JOIN departments d ON e.department_id = d.id
          ORDER BY lr.created_at DESC";
$leaveRequests = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Semua Pengajuan Cuti</h1>
        <a href="index.php" class="btn btn-secondary">← Dashboard</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Daftar Pengajuan Cuti (<?php echo count($leaveRequests); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($leaveRequests) > 0): ?>
                        <?php foreach ($leaveRequests as $req): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($req['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($req['employee_code']); ?></small>
                            </td>
                            <td>
                                <?php
                                $leaveTypes = [
                                    'annual' => '🏖️ Tahunan',
                                    'sick' => '🤒 Sakit',
                                    'emergency' => '🚨 Darurat',
                                    'unpaid' => '💸 Tanpa Gaji',
                                    'maternity' => '🤱 Melahirkan',
                                    'paternity' => '👶 Ayah'
                                ];
                                echo $leaveTypes[$req['leave_type']] ?? 'Cuti';
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
                                echo htmlspecialchars(mb_substr($reason, 0, 40));
                                if (mb_strlen($reason) > 40) echo '...';
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'badge-kontrak',
                                    'approved' => 'badge-tetap',
                                    'rejected' => 'badge-magang'
                                ];
                                $statusLabel = [
                                    'pending' => '⏳ Pending',
                                    'approved' => '✓ Disetujui',
                                    'rejected' => '✗ Ditolak'
                                ];
                                ?>
                                <span class="badge <?php echo $statusClass[$req['status']]; ?>">
                                    <?php echo $statusLabel[$req['status']]; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['status'] == 'pending'): ?>
                                    <form method="POST" action="approve.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-action" style="background: #10b981;">✓</button>
                                    </form>
                                    <form method="POST" action="approve.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-action btn-delete">✗</button>
                                    </form>
                                <?php else: ?>
                                    <small><?php echo date('d M', strtotime($req['approved_at'] ?? $req['updated_at'])); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">Belum ada pengajuan cuti</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
