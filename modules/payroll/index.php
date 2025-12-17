<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Payroll Management';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total employees with salary
$stats['employees'] = $db->query("SELECT COUNT(DISTINCT e.id) 
                                   FROM employees e 
                                   WHERE e.is_active = 1")->fetchColumn();

// Total payroll this month
$currentMonth = date('n');
$currentYear = date('Y');
$payroll = $db->query("SELECT * FROM payrolls 
                       WHERE period_month = $currentMonth 
                       AND period_year = $currentYear")->fetch(PDO::FETCH_ASSOC);

$stats['current_payroll'] = $payroll;
$stats['total_net'] = $payroll['total_net'] ?? 0;
$stats['status'] = $payroll['status'] ?? null;

// Recent payrolls - FIXED: use username instead of full_name
$recentPayrolls = $db->query("SELECT p.*, u.username as processed_by_name
                              FROM payrolls p
                              LEFT JOIN users u ON p.processed_by = u.id
                              ORDER BY p.period_year DESC, p.period_month DESC
                              LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);


include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>💰 Payroll Management</h1>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                👥
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['employees']); ?></div>
                <div class="stat-label">Total Karyawan Aktif</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                📅
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo date('F Y'); ?></div>
                <div class="stat-label">Period Saat Ini</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                💵
            </div>
            <div class="stat-content">
                <div class="stat-value">Rp <?php echo number_format($stats['total_net'], 0, ',', '.'); ?></div>
                <div class="stat-label">Total Payroll Bulan Ini</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <?php 
                $statusIcon = [
                    'draft' => '📝',
                    'processed' => '✅',
                    'paid' => '💸',
                    null => '⏳'
                ];
                echo $statusIcon[$stats['status']];
                ?>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?php 
                    $statusLabel = [
                        'draft' => 'Draft',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                        null => 'Belum Ada'
                    ];
                    echo $statusLabel[$stats['status']];
                    ?>
                </div>
                <div class="stat-label">Status Payroll</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="action-grid">
        <a href="generate.php" class="action-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="action-icon">🔄</div>
            <div class="action-title">Generate Payroll</div>
            <div class="action-desc">Buat payroll untuk bulan ini</div>
        </a>
        
        <a href="master_salary.php" class="action-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="action-icon">💼</div>
            <div class="action-title">Master Gaji</div>
            <div class="action-desc">Kelola gaji karyawan</div>
        </a>
        
        <a href="components.php" class="action-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="action-icon">📋</div>
            <div class="action-title">Komponen Gaji</div>
            <div class="action-desc">Tunjangan & potongan</div>
        </a>
        
        <a href="#" class="action-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="action-icon">📊</div>
            <div class="action-title">Laporan</div>
            <div class="action-desc">Export & analisis</div>
        </a>
    </div>
    
    <!-- Recent Payrolls -->
    <div class="card">
        <div class="card-header">
            <h2>📜 Riwayat Payroll</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Karyawan</th>
                        <th>Total Gross</th>
                        <th>Total Potongan</th>
                        <th>Total Net</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentPayrolls) > 0): ?>
                        <?php foreach ($recentPayrolls as $p): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php 
                                    $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                    echo $monthNames[$p['period_month']] . ' ' . $p['period_year']; 
                                    ?>
                                </strong>
                            </td>
                            <td><?php echo number_format($p['total_employees']); ?> orang</td>
                            <td>Rp <?php echo number_format($p['total_gross'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($p['total_deductions'], 0, ',', '.'); ?></td>
                            <td><strong>Rp <?php echo number_format($p['total_net'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'draft' => 'badge-kontrak',
                                    'processed' => 'badge-info',
                                    'paid' => 'badge-tetap'
                                ];
                                $statusLabel = [
                                    'draft' => '📝 Draft',
                                    'processed' => '✅ Processed',
                                    'paid' => '💸 Paid'
                                ];
                                ?>
                                <span class="badge <?php echo $statusClass[$p['status']]; ?>">
                                    <?php echo $statusLabel[$p['status']]; ?>
                                </span>
                            </td>
                            <td>
                                <a href="slip.php?payroll_id=<?php echo $p['id']; ?>" class="btn-action" title="Lihat Detail">
                                    👁️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">💰</p>
                                <p style="margin: 10px 0 0;">Belum ada payroll yang diproses</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.action-card {
    color: white;
    padding: 30px;
    border-radius: 12px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.action-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.action-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
}

.action-desc {
    font-size: 14px;
    opacity: 0.9;
}

.badge-info {
    background: #3b82f6;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
</style>

<?php include '../../includes/footer.php'; ?>
