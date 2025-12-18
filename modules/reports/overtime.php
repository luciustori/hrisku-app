<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'manager']);

$page_title = 'Overtime Reports';

$database = new Database();
$db = $database->getConnection();

$year = $_GET['year'] ?? date('Y');

// Overtime stats
$totalRequests = $db->query("SELECT COUNT(*) FROM overtime_requests WHERE YEAR(overtime_date) = $year")->fetchColumn();
$totalHours = $db->query("SELECT COALESCE(SUM(hours), 0) FROM overtime_requests WHERE status = 'approved' AND YEAR(overtime_date) = $year")->fetchColumn();
$approved = $db->query("SELECT COUNT(*) FROM overtime_requests WHERE status = 'approved' AND YEAR(overtime_date) = $year")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM overtime_requests WHERE status = 'pending' AND YEAR(overtime_date) = $year")->fetchColumn();

// Get overtime rate from system settings
try {
    $overtimeRate = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'overtime_rate_per_hour'")->fetchColumn();
} catch (PDOException $e) {
    $overtimeRate = 50000; // Default
}

$totalCost = $totalHours * $overtimeRate;

// Monthly overtime hours
$monthlyOT = $db->query("
    SELECT 
        MONTH(overtime_date) as month,
        SUM(hours) as total_hours,
        COUNT(*) as request_count
    FROM overtime_requests
    WHERE status = 'approved' AND YEAR(overtime_date) = $year
    GROUP BY MONTH(overtime_date)
    ORDER BY MONTH(overtime_date)
")->fetchAll(PDO::FETCH_ASSOC);

$monthlyData = array_fill(1, 12, 0);
foreach ($monthlyOT as $row) {
    $monthlyData[$row['month']] = $row['total_hours'];
}

// Top overtime employees
$topEmployees = $db->query("
    SELECT 
        e.employee_code,
        e.full_name,
        COUNT(ot.id) as request_count,
        SUM(ot.hours) as total_hours,
        SUM(ot.hours * $overtimeRate) as total_cost
    FROM employees e
    JOIN overtime_requests ot ON e.id = ot.employee_id
    WHERE ot.status = 'approved' AND YEAR(ot.overtime_date) = $year
    GROUP BY e.id
    ORDER BY total_hours DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Overtime by department
$byDepartment = $db->query("
    SELECT 
        d.department_name,
        COUNT(ot.id) as request_count,
        SUM(ot.hours) as total_hours
    FROM departments d
    JOIN employees e ON d.id = e.department_id
    JOIN overtime_requests ot ON e.id = ot.employee_id
    WHERE ot.status = 'approved' AND YEAR(ot.overtime_date) = $year
    GROUP BY d.id
    ORDER BY total_hours DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Recent overtime requests
$recentOT = $db->query("
    SELECT 
        ot.*,
        e.full_name as employee_name,
        e.employee_code,
        d.department_name
    FROM overtime_requests ot
    JOIN employees e ON ot.employee_id = e.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE YEAR(ot.overtime_date) = $year
    ORDER BY ot.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>⏰ Overtime Reports</h1>
        <div>
            <select onchange="window.location.href='?year='+this.value" class="form-control" style="display: inline-block; width: auto;">
                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button onclick="exportToExcel()" class="btn btn-success">📥 Export</button>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($totalHours); ?>h</div>
                <div class="stat-label">Total Overtime Hours</div>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-content">
                <div class="stat-value">Rp <?php echo number_format($totalCost / 1000000, 1); ?>M</div>
                <div class="stat-label">Total Cost</div>
                <small class="text-muted">Rate: Rp <?php echo number_format($overtimeRate); ?>/hour</small>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-content">
                <div class="stat-value"><?php echo $approved; ?></div>
                <div class="stat-label">✓ Approved Requests</div>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-content">
                <div class="stat-value"><?php echo $pending; ?></div>
                <div class="stat-label">⏳ Pending Requests</div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="charts-row">
        <div class="card">
            <div class="card-header">
                <h2>📈 Monthly Overtime Hours (<?php echo $year; ?>)</h2>
            </div>
            <div class="card-body">
                <canvas id="chartMonthly" height="250"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>📊 Overtime by Department</h2>
            </div>
            <div class="card-body">
                <canvas id="chartDepartment" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Employees Table -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>🏆 Top 10 Overtime Employees</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Total Requests</th>
                        <th>Total Hours</th>
                        <th>Estimated Cost</th>
                        <th>Avg Hours/Request</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($topEmployees as $emp): ?>
                    <?php $avgHours = $emp['request_count'] > 0 ? $emp['total_hours'] / $emp['request_count'] : 0; ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($emp['full_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($emp['employee_code']); ?></small>
                        </td>
                        <td><?php echo $emp['request_count']; ?> requests</td>
                        <td><strong><?php echo number_format($emp['total_hours'], 1); ?></strong> hours</td>
                        <td><strong>Rp <?php echo number_format($emp['total_cost']); ?></strong></td>
                        <td><?php echo number_format($avgHours, 1); ?> hours</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($topEmployees) == 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                            No overtime data for <?php echo $year; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Overtime Requests -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>📋 Recent Overtime Requests (<?php echo count($recentOT); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table" id="overtimeTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Hours</th>
                        <th>Estimated Cost</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOT as $ot): ?>
                    <?php
                    $cost = $ot['hours'] * $overtimeRate;
                    $statusClass = [
                        'pending' => 'badge-pending',
                        'approved' => 'badge-approved',
                        'rejected' => 'badge-rejected'
                    ];
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($ot['employee_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($ot['employee_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($ot['department_name'] ?? '-'); ?></td>
                        <td><?php echo date('d M Y', strtotime($ot['overtime_date'])); ?></td>
                        <td><strong><?php echo number_format($ot['hours'], 1); ?></strong> hours</td>
                        <td>Rp <?php echo number_format($cost); ?></td>
                        <td>
                            <span class="badge <?php echo $statusClass[$ot['status']] ?? ''; ?>">
                                <?php echo ucfirst($ot['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $desc = $ot['description'] ?? '';
                            echo htmlspecialchars(mb_substr($desc, 0, 50));
                            if (mb_strlen($desc) > 50) echo '...';
                            ?>
                        </td>
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

.text-muted {
    color: #6b7280;
    font-size: 13px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Monthly Trend Chart
new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Overtime Hours',
            data: <?php echo json_encode(array_values($monthlyData)); ?>,
            borderColor: '#43e97b',
            backgroundColor: 'rgba(67, 233, 123, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 5,
            pointBackgroundColor: '#43e97b'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + ' h';
                    }
                }
            }
        }
    }
});

// Department Chart
new Chart(document.getElementById('chartDepartment'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($byDepartment, 'department_name')); ?>,
        datasets: [{
            label: 'Total Hours',
            data: <?php echo json_encode(array_column($byDepartment, 'total_hours')); ?>,
            backgroundColor: 'rgba(79, 172, 254, 0.5)',
            borderColor: '#4facfe',
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
            y: { 
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + ' h';
                    }
                }
            }
        }
    }
});

function exportToExcel() {
    const table = document.getElementById('overtimeTable');
    let html = table.outerHTML;
    window.open('data:application/vnd.ms-excel,' + encodeURIComponent(html));
}
</script>

<?php include '../../includes/footer.php'; ?>
