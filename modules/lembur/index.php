<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];

$database = new Database();
$db = $database->getConnection();
$user = $_SESSION['user'];
$isAdmin = in_array($user['role'], ['super_admin', 'admin', 'hrd']);

try {
    if ($isAdmin) {
        $stats = [
            'pending' => $db->query("SELECT COUNT(*) FROM overtime_requests WHERE status='pending'")->fetchColumn(),
            'approved' => $db->query("SELECT COUNT(*) FROM overtime_requests WHERE status='approved' AND MONTH(overtime_date)=MONTH(CURDATE())")->fetchColumn(),
            'total_hours' => $db->query("SELECT SUM(total_hours) FROM overtime_requests WHERE status='approved' AND MONTH(overtime_date)=MONTH(CURDATE())")->fetchColumn() ?: 0
        ];
    } else {
        $empId = $db->query("SELECT id FROM employees WHERE email='{$user['username']}' LIMIT 1")->fetchColumn();
        $stats = [
            'pending' => $empId ? $db->query("SELECT COUNT(*) FROM overtime_requests WHERE employee_id=$empId AND status='pending'")->fetchColumn() : 0,
            'approved' => $empId ? $db->query("SELECT COUNT(*) FROM overtime_requests WHERE employee_id=$empId AND status='approved' AND MONTH(overtime_date)=MONTH(CURDATE())")->fetchColumn() : 0,
            'total_hours' => $empId ? $db->query("SELECT SUM(total_hours) FROM overtime_requests WHERE employee_id=$empId AND status='approved' AND MONTH(overtime_date)=MONTH(CURDATE())")->fetchColumn() ?: 0 : 0
        ];
    }
} catch (Exception $e) {
    $stats = ['pending' => 0, 'approved' => 0, 'total_hours' => 0];
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>⏰ Dashboard Lembur</h1>
        <div>
            <a href="pengajuan.php" class="btn btn-primary">+ Ajukan Lembur</a>
            <a href="my_lembur.php" class="btn btn-success">📋 Lembur Saya</a>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">⏳</div>
            <div class="stat-info">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Menunggu Approval</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">✓</div>
            <div class="stat-info">
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Disetujui Bulan Ini</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">⏰</div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['total_hours'], 1); ?></h3>
                <p>Total Jam Bulan Ini</p>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
.stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; }
.stat-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; }
.stat-info h3 { margin: 0; font-size: 36px; color: #1f2937; }
.stat-info p { margin: 5px 0 0; color: #6b7280; }
</style>

<?php include '../../includes/footer.php'; ?>
