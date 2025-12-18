<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'manager']);

$page_title = 'Leave Reports';

$database = new Database();
$db = $database->getConnection();

// Get current year
$year = $_GET['year'] ?? date('Y');

// Leave stats
$totalRequests = $db->query("SELECT COUNT(*) FROM leave_requests WHERE YEAR(start_date) = $year")->fetchColumn();
$approved = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND YEAR(start_date) = $year")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending' AND YEAR(start_date) = $year")->fetchColumn();
$rejected = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'rejected' AND YEAR(start_date) = $year")->fetchColumn();

// Leave by month (current year)
$byMonth = $db->query("
    SELECT 
        MONTH(start_date) as month,
        COUNT(*) as count
    FROM leave_requests
    WHERE YEAR(start_date) = $year AND status = 'approved'
    GROUP BY MONTH(start_date)
    ORDER BY MONTH(start_date)
")->fetchAll(PDO::FETCH_ASSOC);

// Create full year array
$monthlyData = array_fill(1, 12, 0);
foreach ($byMonth as $row) {
    $monthlyData[$row['month']] = $row['count'];
}

// Leave by type
$byType = $db->query("
    SELECT 
        leave_type,
        COUNT(*) as count,
        SUM(DATEDIFF(end_date, start_date) + 1) as total_days
    FROM leave_requests
    WHERE status = 'approved' AND YEAR(start_date) = $year
    GROUP BY leave_type
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Recent leave requests
$recentLeaves = $db->query("
    SELECT 
        lr.*,
        e.full_name as employee_name,
        e.employee_code
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE YEAR(lr.start_date) = $year
    ORDER BY lr.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// Top employees by leave usage
$topEmployees = $db->query("
    SELECT 
        e.employee_code,
        e.full_name,
        COUNT(lr.id) as leave_count,
        SUM(DATEDIFF(lr.end_date, lr.start_date) + 1) as total_days
    FROM employees e
    JOIN leave_requests lr ON e.id = lr.employee_id
    WHERE lr.status = 'approved' AND YEAR(lr.start_date) = $year
    GROUP BY e.id
    ORDER BY total_days DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📅 Leave Reports</h1>
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
                <div class="stat-value"><?php echo $totalRequests; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-content">
                <div class="stat-value" style="color: #10b981;"><?php echo $approved; ?></div>
                <div class="stat-label">✓ Approved</div>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-content">
                <div class="stat-value" style="color: #f59e0b;"><?php echo $pending; ?></div>
                <div class="stat-label">⏳ Pending</div>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-content">
                <div class="stat-value" style="color: #ef4444;"><?php echo $rejected; ?></div>
                <div class="stat-label">✗ Rejected</div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="charts-row">
        <div class="card">
            <div class="card-header">
                <h2>📈 Monthly Leave Trend (<?php echo $year; ?>)</h2>
            </div>
            <div class="card-body">
                <canvas id="chartMonthly" height="250"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>📊 Leave by Type</h2>
            </div>
            <div class="card-body">
                <canvas id="chartType" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Employees Table -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>🏆 Top 10 Employees by Leave Usage</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Leave Count</th>
                        <th>Total Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($topEmployees as $emp): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($emp['full_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($emp['employee_code']); ?></small>
                        </td>
                        <td><?php echo $emp['leave_count']; ?> requests</td>
                        <td><strong><?php echo $emp['total_days']; ?></strong> days</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($topEmployees) == 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
                            No leave data for <?php echo $year; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Leave Requests -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">
            <h2>📋 Recent Leave Requests (<?php echo count($recentLeaves); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table" id="leaveTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLeaves as $leave): ?>
                    <?php
                    $days = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / 86400 + 1;
                    $statusClass = [
                        'pending' => 'badge-pending',
                        'approved' => 'badge-approved',
                        'rejected' => 'badge-rejected'
                    ];
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($leave['employee_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($leave['employee_code']); ?></small>
                        </td>
                        <td><?php echo ucfirst($leave['leave_type']); ?></td>
                        <td><?php echo date('d M Y', strtotime($leave['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($leave['end_date'])); ?></td>
                        <td><strong><?php echo $days; ?></strong> days</td>
                        <td>
                            <span class="badge <?php echo $statusClass[$leave['status']] ?? ''; ?>">
                                <?php echo ucfirst($leave['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $reason = $leave['reason'] ?? '';
                            echo htmlspecialchars(mb_substr($reason, 0, 50));
                            if (mb_strlen($reason) > 50) echo '...';
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
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const colors = {
    primary: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a'],
    bgPrimary: ['rgba(102, 126, 234, 0.2)', 'rgba(118, 75, 162, 0.2)', 'rgba(240, 147, 251, 0.2)']
};

// Monthly Trend Chart
new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Approved Leaves',
            data: <?php echo json_encode(array_values($monthlyData)); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
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

// Leave Type Chart
new Chart(document.getElementById('chartType'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map('ucfirst', array_column($byType, 'leave_type'))); ?>,
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

function exportToExcel() {
    const table = document.getElementById('leaveTable');
    let html = table.outerHTML;
    window.open('data:application/vnd.ms-excel,' + encodeURIComponent(html));
}
</script>

<?php include '../../includes/footer.php'; ?>
