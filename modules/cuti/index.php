<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// TEMPORARY: Set dummy user untuk testing
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

$page_title = 'Dashboard Cuti';

$database = new Database();
$db = $database->getConnection();

$user = $_SESSION['user'] ?? null;

$isAdmin = in_array($user['role'], ['super_admin', 'admin', 'hrd']);

// Get statistics
if ($isAdmin) {
    // Admin - semua data
    try {
        $stats = [
            'total_pending' => $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn(),
            'total_approved' => $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())")->fetchColumn(),
            'total_rejected' => $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'rejected' AND YEAR(start_date) = YEAR(CURDATE())")->fetchColumn(),
            'total_today' => $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND CURDATE() BETWEEN start_date AND end_date")->fetchColumn()
        ];
    } catch (Exception $e) {
        // Table belum ada
        $stats = [
            'total_pending' => 0,
            'total_approved' => 0,
            'total_rejected' => 0,
            'total_today' => 0
        ];
    }
    
    // Pending requests
    try {
        $pendingQuery = "SELECT lr.*, e.full_name, e.employee_code, d.department_name
                         FROM leave_requests lr
                         JOIN employees e ON lr.employee_id = e.id
                         LEFT JOIN departments d ON e.department_id = d.id
                         WHERE lr.status = 'pending'
                         ORDER BY lr.created_at DESC
                         LIMIT 10";
        $pendingRequests = $db->query($pendingQuery)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $pendingRequests = [];
    }
    
} else {
    // Karyawan
    $stats = [
        'total_pending' => 0,
        'total_approved' => 0,
        'total_rejected' => 0
    ];
    $balance = ['annual_quota' => 12, 'annual_used' => 0];
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏖️ Dashboard Cuti</h1>
        <div>
            <a href="pengajuan.php" class="btn btn-primary">+ Ajukan Cuti</a>
            <?php if ($isAdmin): ?>
                <a href="list_cuti.php" class="btn btn-success">📋 Semua Cuti</a>
            <?php else: ?>
                <a href="my_cuti.php" class="btn btn-success">📋 Cuti Saya</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <?php if ($isAdmin): ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b;">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_pending']; ?></h3>
                    <p>Menunggu Approval</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b981;">✓</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_approved']; ?></h3>
                    <p>Disetujui (2025)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #ef4444;">✗</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_rejected']; ?></h3>
                    <p>Ditolak (2025)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #3b82f6;">🏖️</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_today']; ?></h3>
                    <p>Sedang Cuti Hari Ini</p>
                </div>
            </div>
        <?php else: ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b981;">✓</div>
                <div class="stat-info">
                    <h3><?php echo ($balance['annual_quota'] ?? 12) - ($balance['annual_used'] ?? 0); ?></h3>
                    <p>Sisa Cuti Tahunan</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b;">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_pending']; ?></h3>
                    <p>Menunggu Approval</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #3b82f6;">✓</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_approved']; ?></h3>
                    <p>Disetujui (2025)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #ef4444;">✗</div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_rejected']; ?></h3>
                    <p>Ditolak (2025)</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($isAdmin && isset($pendingRequests) && count($pendingRequests) > 0): ?>
    <!-- Pending Approvals -->
    <div class="card">
        <div class="card-header">
            <h2>⏳ Menunggu Approval</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $req): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($req['full_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($req['employee_code']); ?></small>
                        </td>
                        <td>
                            <span class="badge badge-tetap">🏖️ Cuti</span>
                        </td>
                        <td>
                            <?php echo date('d M Y', strtotime($req['start_date'])); ?><br>
                            <small>s/d <?php echo date('d M Y', strtotime($req['end_date'])); ?></small>
                        </td>
                        <td><strong><?php echo $req['total_days']; ?> hari</strong></td>
                        <td>
                            <button class="btn-action" style="background: #10b981;">✓ Setuju</button>
                            <button class="btn-action btn-delete">✗ Tolak</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$isAdmin && isset($balance)): ?>
    <!-- Leave Balance -->
    <div class="card">
        <div class="card-header">
            <h2>📊 Saldo Cuti Tahun <?php echo date('Y'); ?></h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: #f0f9ff; border-radius: 8px;">
                    <h3 style="font-size: 36px; margin: 0; color: #3b82f6;"><?php echo $balance['annual_quota'] ?? 12; ?></h3>
                    <p style="margin: 5px 0 0 0; color: #666;">Jatah Total</p>
                </div>
                <div style="text-align: center; padding: 20px; background: #fef3c7; border-radius: 8px;">
                    <h3 style="font-size: 36px; margin: 0; color: #f59e0b;"><?php echo $balance['annual_used'] ?? 0; ?></h3>
                    <p style="margin: 5px 0 0 0; color: #666;">Terpakai</p>
                </div>
                <div style="text-align: center; padding: 20px; background: #d1fae5; border-radius: 8px;">
                    <h3 style="font-size: 36px; margin: 0; color: #10b981;"><?php echo ($balance['annual_quota'] ?? 12) - ($balance['annual_used'] ?? 0); ?></h3>
                    <p style="margin: 5px 0 0 0; color: #666;">Sisa</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}

.stat-info h3 {
    margin: 0;
    font-size: 32px;
    color: #1f2937;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #6b7280;
    font-size: 14px;
}
</style>

<?php include '../../includes/footer.php'; ?>
