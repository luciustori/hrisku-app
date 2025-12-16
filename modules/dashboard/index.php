<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Cek login
checkRole();

$page_title = 'Dashboard';

// Koneksi database
$database = new Database();
$db = $database->getConnection();

// Query statistik
try {
    // Total Karyawan Aktif
    $query = "SELECT COUNT(*) as total FROM employees WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Karyawan per Departemen
    $query = "SELECT d.department_name, COUNT(e.id) as total 
              FROM departments d 
              LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
              GROUP BY d.id, d.department_name
              ORDER BY total DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $dept_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Karyawan per Status
    $query = "SELECT employment_status, COUNT(*) as total 
              FROM employees 
              WHERE is_active = 1 
              GROUP BY employment_status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Karyawan Baru (30 hari terakhir)
    $query = "SELECT COUNT(*) as total 
              FROM employees 
              WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
              AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $new_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Dashboard</h1>
        <div class="welcome-text">
            Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
        </div>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Karyawan</h3>
            <div class="stat-number"><?php echo $total_employees; ?></div>
            <div class="stat-label">Karyawan Aktif</div>
        </div>
        
        <div class="stat-card">
            <h3>Karyawan Baru</h3>
            <div class="stat-number"><?php echo $new_employees; ?></div>
            <div class="stat-label">30 Hari Terakhir</div>
        </div>
        
        <div class="stat-card">
            <h3>Departemen</h3>
            <div class="stat-number"><?php echo count($dept_stats); ?></div>
            <div class="stat-label">Total Departemen</div>
        </div>
        
        <div class="stat-card">
            <h3>Status</h3>
            <div class="stat-number"><?php echo count($status_stats); ?></div>
            <div class="stat-label">Tipe Status</div>
        </div>
    </div>
    
    <!-- Karyawan per Departemen -->
    <div class="card">
        <div class="card-header">
            <h2>Karyawan per Departemen</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Departemen</th>
                        <th>Jumlah Karyawan</th>
                        <th>Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dept_stats as $stat): ?>
                        <?php 
                        $percentage = $total_employees > 0 ? round(($stat['total'] / $total_employees) * 100, 1) : 0;
                        ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($stat['department_name']); ?></strong></td>
                        <td><?php echo $stat['total']; ?> orang</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="percentage-text"><?php echo $percentage; ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Status Karyawan -->
    <div class="card">
        <div class="card-header">
            <h2>Status Kepegawaian</h2>
        </div>
        <div class="status-grid">
            <?php foreach($status_stats as $stat): ?>
            <div class="status-item">
                <span class="badge badge-<?php echo $stat['employment_status']; ?>">
                    <?php echo ucfirst($stat['employment_status']); ?>
                </span>
                <div class="status-count"><?php echo $stat['total']; ?> orang</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
