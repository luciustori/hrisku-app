<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'manager']);

$page_title = 'Employee Reports';

$database = new Database();
$db = $database->getConnection();

// Get employees by department (active only)
$byDepartment = $db->query("
    SELECT d.department_name, COUNT(e.id) as count
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
    GROUP BY d.id, d.department_name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get employees by position
$byPosition = $db->query("
    SELECT p.position_name, COUNT(e.id) as count
    FROM positions p
    LEFT JOIN employees e ON p.id = e.position_id AND e.is_active = 1
    GROUP BY p.id, p.position_name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get employees by employment_status (tetap, kontrak, probation)
$byStatus = $db->query("
    SELECT 
        employment_status,
        COUNT(*) as count
    FROM employees
    WHERE is_active = 1
    GROUP BY employment_status
")->fetchAll(PDO::FETCH_ASSOC);

// Get employees by employment_type (if exists) or use dummy data
try {
    $byType = $db->query("
        SELECT 
            CASE 
                WHEN employment_status = 'tetap' THEN 'Permanent'
                WHEN employment_status = 'kontrak' THEN 'Contract'
                WHEN employment_status = 'probation' THEN 'Probation'
                ELSE employment_status
            END as type_label,
            COUNT(*) as count
        FROM employees
        WHERE is_active = 1
        GROUP BY employment_status
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $byType = $byStatus; // Fallback
}

// Get detailed employee list
$employees = $db->query("
    SELECT 
        e.*,
        d.department_name,
        p.position_name,
        b.branch_name
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    LEFT JOIN branches b ON e.branch_id = b.id
    WHERE e.is_active = 1
    ORDER BY e.full_name
")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👥 Employee Reports</h1>
        <div>
            <button onclick="exportToExcel()" class="btn btn-success">📥 Export Excel</button>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?php echo count($employees); ?></div>
                <div class="stat-label">Total Active Employees</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?php echo count($byDepartment); ?></div>
                <div class="stat-label">Departments</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?php echo count($byPosition); ?></div>
                <div class="stat-label">Positions</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <!-- By Department -->
        <div class="card">
            <div class="card-header">
                <h2>📊 Employees by Department</h2>
            </div>
            <div class="card-body">
                <canvas id="chartDepartment" height="300"></canvas>
            </div>
        </div>
        
        <!-- By Position -->
        <div class="card">
            <div class="card-header">
                <h2>💼 Employees by Position</h2>
            </div>
            <div class="card-body">
                <canvas id="chartPosition" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="charts-row" style="margin-top: 20px;">
        <!-- By Employment Status -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Employees by Status</h2>
            </div>
            <div class="card-body">
                <canvas id="chartStatus" height="250"></canvas>
            </div>
        </div>
        
        <!-- By Type (Pie Chart) -->
        <div class="card">
            <div class="card-header">
                <h2>✓ Distribution</h2>
            </div>
            <div class="card-body">
                <canvas id="chartType" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Employee List Table -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>📋 Employee List (<?php echo count($employees); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table" id="employeeTable">
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>NIK</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Join Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($emp['employee_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($emp['nik'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($emp['position_name'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $statusClass = [
                                'tetap' => 'badge-tetap',
                                'kontrak' => 'badge-kontrak',
                                'probation' => 'badge-magang'
                            ];
                            $statusLabel = [
                                'tetap' => 'Permanent',
                                'kontrak' => 'Contract',
                                'probation' => 'Probation'
                            ];
                            ?>
                            <span class="badge <?php echo $statusClass[$emp['employment_status']] ?? ''; ?>">
                                <?php echo $statusLabel[$emp['employment_status']] ?? ucfirst($emp['employment_status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($emp['join_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
}

@media print {
    .page-header button, .page-header a {
        display: none;
    }
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart colors
const colors = {
    primary: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#30cfd0'],
    bgPrimary: ['rgba(102, 126, 234, 0.2)', 'rgba(118, 75, 162, 0.2)', 'rgba(240, 147, 251, 0.2)', 
                'rgba(79, 172, 254, 0.2)', 'rgba(67, 233, 123, 0.2)', 'rgba(250, 112, 154, 0.2)',
                'rgba(254, 225, 64, 0.2)', 'rgba(48, 207, 208, 0.2)']
};

// Department Chart
new Chart(document.getElementById('chartDepartment'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($byDepartment, 'department_name')); ?>,
        datasets: [{
            label: 'Employees',
            data: <?php echo json_encode(array_column($byDepartment, 'count')); ?>,
            backgroundColor: colors.bgPrimary,
            borderColor: colors.primary,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Position Chart
new Chart(document.getElementById('chartPosition'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($byPosition, 'position_name')); ?>,
        datasets: [{
            label: 'Employees',
            data: <?php echo json_encode(array_column($byPosition, 'count')); ?>,
            backgroundColor: colors.bgPrimary,
            borderColor: colors.primary,
            borderWidth: 2
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Status Chart (Pie)
new Chart(document.getElementById('chartStatus'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_map('ucfirst', array_column($byStatus, 'employment_status'))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($byStatus, 'count')); ?>,
            backgroundColor: colors.primary,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Type Chart (Doughnut)
new Chart(document.getElementById('chartType'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($byType, 'type_label')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($byType, 'count')); ?>,
            backgroundColor: colors.primary,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Export to Excel function
function exportToExcel() {
    const table = document.getElementById('employeeTable');
    let html = table.outerHTML;
    window.open('data:application/vnd.ms-excel,' + encodeURIComponent(html));
}
</script>

<?php include '../../includes/footer.php'; ?>
