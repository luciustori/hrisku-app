<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Dashboard Kepegawaian';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [
    'total_employees' => $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn(),
    'total_tetap' => $db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'tetap' AND is_active = 1")->fetchColumn(),
    'total_kontrak' => $db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'kontrak' AND is_active = 1")->fetchColumn(),
    'total_probation' => $db->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'probation' AND is_active = 1")->fetchColumn(),
    'total_male' => $db->query("SELECT COUNT(*) FROM employees WHERE gender = 'male' AND is_active = 1")->fetchColumn(),
    'total_female' => $db->query("SELECT COUNT(*) FROM employees WHERE gender = 'female' AND is_active = 1")->fetchColumn()
];

// Get recent employees (5 terbaru)
$recentQuery = "SELECT e.*, d.department_name, p.position_name, b.branch_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN branches b ON e.branch_id = b.id
                WHERE e.is_active = 1
                ORDER BY e.created_at DESC
                LIMIT 5";
$recentEmployees = $db->query($recentQuery)->fetchAll(PDO::FETCH_ASSOC);

// Get employees by department
$deptQuery = "SELECT d.department_name, COUNT(e.id) as total
              FROM departments d
              LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
              WHERE d.is_active = 1
              GROUP BY d.id
              ORDER BY total DESC
              LIMIT 5";
$employeesByDept = $db->query($deptQuery)->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👥 Dashboard Kepegawaian</h1>
        <div>
            <a href="employees.php" class="btn btn-primary">📋 Lihat Semua Karyawan</a>
            <a href="add_employee.php" class="btn btn-success">+ Tambah Karyawan</a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">👥</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_employees']; ?></h3>
                <p>Total Karyawan Aktif</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">✓</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_tetap']; ?></h3>
                <p>Karyawan Tetap</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">📝</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_kontrak']; ?></h3>
                <p>Karyawan Kontrak</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf6;">⏳</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_probation']; ?></h3>
                <p>Probation</p>
            </div>
        </div>
    </div>
    
    <!-- Gender Distribution -->
    <div class="card" style="margin-bottom: 30px;">
        <div class="card-header">
            <h2>Distribusi Gender</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 20px; align-items: center;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="width: 30px; height: 30px; background: #3b82f6; border-radius: 50%;"></div>
                        <span>Laki-laki: <strong><?php echo $stats['total_male']; ?> orang</strong></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 30px; height: 30px; background: #ec4899; border-radius: 50%;"></div>
                        <span>Perempuan: <strong><?php echo $stats['total_female']; ?> orang</strong></span>
                    </div>
                </div>
                <div style="flex: 1;">
                    <?php
                    $total = $stats['total_male'] + $stats['total_female'];
                    $malePercent = $total > 0 ? round(($stats['total_male'] / $total) * 100) : 0;
                    $femalePercent = $total > 0 ? round(($stats['total_female'] / $total) * 100) : 0;
                    ?>
                    <div style="font-size: 48px; text-align: center;">
                        <span style="color: #3b82f6;">♂ <?php echo $malePercent; ?>%</span>
                        <span style="margin: 0 10px;">|</span>
                        <span style="color: #ec4899;">♀ <?php echo $femalePercent; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Recent Employees -->
        <div class="card">
            <div class="card-header">
                <h2>Karyawan Terbaru</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentEmployees) > 0): ?>
                            <?php foreach ($recentEmployees as $emp): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($emp['full_name'] ?? ''); ?></strong><br>
                                    <small><?php echo htmlspecialchars($emp['employee_code'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($emp['position_name'] ?? '-'); ?><br>
                                    <small><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'tetap' => 'badge-tetap',
                                        'kontrak' => 'badge-kontrak',
                                        'probation' => 'badge-probation',
                                        'magang' => 'badge-magang'
                                    ];
                                    $status = $emp['employment_status'] ?? 'kontrak';
                                    ?>
                                    <span class="badge <?php echo $statusClass[$status] ?? 'badge-kontrak'; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Belum ada data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Employees by Department -->
        <div class="card">
            <div class="card-header">
                <h2>Karyawan per Departemen</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Departemen</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($employeesByDept) > 0): ?>
                            <?php foreach ($employeesByDept as $dept): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                                <td>
                                    <span class="badge badge-kontrak"><?php echo $dept['total']; ?> orang</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Belum ada data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h2>Menu Kepegawaian</h2>
        </div>
        <div class="quick-links">
            <a href="employees.php" class="quick-link-card">
                <div class="quick-link-icon">📋</div>
                <h3>Data Karyawan</h3>
                <p>Lihat & kelola data karyawan</p>
            </a>
            
            <a href="add_employee.php" class="quick-link-card">
                <div class="quick-link-icon">➕</div>
                <h3>Tambah Karyawan</h3>
                <p>Tambah karyawan baru</p>
            </a>
            
            <a href="../company/departments.php" class="quick-link-card">
                <div class="quick-link-icon">🏛️</div>
                <h3>Departemen</h3>
                <p>Kelola departemen</p>
            </a>
            
            <a href="../company/manage_positions.php" class="quick-link-card">
                <div class="quick-link-icon">👔</div>
                <h3>Jabatan</h3>
                <p>Kelola jabatan</p>
            </a>
        </div>
    </div>
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

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.quick-link-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 20px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.quick-link-card:hover {
    transform: translateY(-5px);
}

.quick-link-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.quick-link-card h3 {
    margin: 10px 0;
    font-size: 20px;
    color: white;
}

.quick-link-card p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.quick-link-card:nth-child(2) {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.quick-link-card:nth-child(3) {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.quick-link-card:nth-child(4) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}
</style>

<?php include '../../includes/footer.php'; ?>
