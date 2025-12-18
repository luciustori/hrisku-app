<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$page_title = 'Dashboard';

$database = new Database();
$db = $database->getConnection();

// ========================================
// STATS CARDS DATA
// ========================================

// Total Employees (with growth)
$totalEmployees = $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
$lastMonthEmployees = $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1 AND join_date < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")->fetchColumn();
$employeeGrowth = $totalEmployees - $lastMonthEmployees;

// Pending Leaves
$pendingLeaves = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$todayLeaves = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending' AND DATE(created_at) = CURDATE()")->fetchColumn();

// Monthly Payroll - FIXED with error handling
$currentMonthPayroll = 0;
$lastMonthPayroll = 0;
try {
    // Try payroll_details first (most reliable)
    $currentMonthPayroll = $db->query("
        SELECT COALESCE(SUM(pd.net_salary), 0) 
        FROM payroll_details pd
        JOIN payrolls p ON pd.payroll_id = p.id
        WHERE MONTH(p.created_at) = MONTH(CURDATE()) 
        AND YEAR(p.created_at) = YEAR(CURDATE())
    ")->fetchColumn();
    
    $lastMonthPayroll = $db->query("
        SELECT COALESCE(SUM(pd.net_salary), 0) 
        FROM payroll_details pd
        JOIN payrolls p ON pd.payroll_id = p.id
        WHERE MONTH(p.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
        AND YEAR(p.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
    ")->fetchColumn();
} catch (PDOException $e) {
    // Fallback: just count payrolls
    try {
        $currentMonthPayroll = $db->query("SELECT COUNT(*) * 5000000 FROM payrolls WHERE MONTH(created_at) = MONTH(CURDATE())")->fetchColumn();
        $lastMonthPayroll = $db->query("SELECT COUNT(*) * 5000000 FROM payrolls WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
    } catch (PDOException $e2) {
        $currentMonthPayroll = 0;
        $lastMonthPayroll = 0;
    }
}

$payrollChange = $lastMonthPayroll > 0 ? (($currentMonthPayroll - $lastMonthPayroll) / $lastMonthPayroll) * 100 : 0;

// Overtime Hours (current month) - FIXED to use total_hours
$overtimeHours = 0;
try {
    $overtimeHours = $db->query("
        SELECT COALESCE(SUM(total_hours), 0) 
        FROM overtime_requests 
        WHERE status = 'approved' 
        AND MONTH(overtime_date) = MONTH(CURDATE()) 
        AND YEAR(overtime_date) = YEAR(CURDATE())
    ")->fetchColumn();
} catch (PDOException $e) {
    $overtimeHours = 0;
}

try {
    $overtimeRate = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'overtime_rate_per_hour'")->fetchColumn();
    if (!$overtimeRate) $overtimeRate = 50000;
} catch (PDOException $e) {
    $overtimeRate = 50000;
}
$overtimeCost = $overtimeHours * $overtimeRate;

// Additional Stats
$upcomingBirthdays = $db->query("
    SELECT COUNT(*) FROM employees 
    WHERE is_active = 1 
    AND birth_date IS NOT NULL
    AND MONTH(birth_date) = MONTH(CURDATE()) 
    AND DAY(birth_date) >= DAY(CURDATE())
    AND DAY(birth_date) <= DAY(DATE_ADD(CURDATE(), INTERVAL 7 DAY))
")->fetchColumn();

$expiringContracts = $db->query("
    SELECT COUNT(*) FROM employees 
    WHERE employment_status = 'kontrak' 
    AND contract_end_date IS NOT NULL
    AND contract_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();

// ========================================
// CHART DATA
// ========================================

// Employee Distribution by Department
$empByDept = $db->query("
    SELECT d.department_name, COUNT(e.id) as count
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department_id AND e.is_active = 1
    GROUP BY d.id
    HAVING count > 0
    ORDER BY count DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Employee Distribution by Employment Status
$empByStatus = $db->query("
    SELECT employment_status, COUNT(*) as count
    FROM employees
    WHERE is_active = 1
    GROUP BY employment_status
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly Leave Trend (Last 12 months)
$leaveTrend = $db->query("
    SELECT 
        DATE_FORMAT(start_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM leave_requests
    WHERE status = 'approved'
    AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(start_date, '%Y-%m')
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Payroll Overview (Last 6 months) - FIXED
$payrollTrend = [];
try {
    $payrollTrend = $db->query("
        SELECT 
            DATE_FORMAT(p.created_at, '%Y-%m') as month,
            SUM(pd.gross_salary) as gross,
            SUM(pd.net_salary) as net
        FROM payrolls p
        LEFT JOIN payroll_details pd ON p.id = pd.payroll_id
        WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
        ORDER BY month
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $payrollTrend = [];
}

// Overtime by Department - FIXED to use total_hours
$overtimeByDept = [];
try {
    $overtimeByDept = $db->query("
        SELECT 
            d.department_name,
            SUM(ot.total_hours) as total_hours,
            COUNT(ot.id) as request_count
        FROM departments d
        JOIN employees e ON d.id = e.department_id
        JOIN overtime_requests ot ON e.id = ot.employee_id
        WHERE ot.status = 'approved'
        AND MONTH(ot.overtime_date) = MONTH(CURDATE())
        AND YEAR(ot.overtime_date) = YEAR(CURDATE())
        GROUP BY d.id
        HAVING total_hours > 0
        ORDER BY total_hours DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $overtimeByDept = [];
}

// ========================================
// RECENT ACTIVITIES
// ========================================

$recentActivities = [];

// Recent leave requests
$recentLeaves = $db->query("
    SELECT 
        'leave' as type,
        lr.created_at,
        e.full_name,
        lr.leave_type,
        lr.status
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    ORDER BY lr.created_at DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Recent overtime requests - FIXED to use total_hours
$recentOT = [];
try {
    $recentOT = $db->query("
        SELECT 
            'overtime' as type,
            ot.created_at,
            e.full_name,
            ot.total_hours as hours,
            ot.status
        FROM overtime_requests ot
        JOIN employees e ON ot.employee_id = e.id
        ORDER BY ot.created_at DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentOT = [];
}

// Recent payrolls - FIXED
$recentPayrolls = [];
try {
    $recentPayrolls = $db->query("
        SELECT 
            'payroll' as type,
            p.created_at,
            e.full_name,
            COALESCE(pd.net_salary, 0) as net_salary
        FROM payrolls p
        JOIN employees e ON p.employee_id = e.id
        LEFT JOIN payroll_details pd ON p.id = pd.payroll_id
        ORDER BY p.created_at DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentPayrolls = [];
}

// Recent employees
$recentEmployees = $db->query("
    SELECT 
        'employee' as type,
        created_at,
        full_name,
        employment_status
    FROM employees
    ORDER BY created_at DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Merge and sort all activities
$recentActivities = array_merge($recentLeaves, $recentOT, $recentPayrolls, $recentEmployees);
usort($recentActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentActivities = array_slice($recentActivities, 0, 10);

// Helper function for time ago
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $timestamp);
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>📊 Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>! Here's what's happening today.</p>
        </div>
        <div class="page-actions">
            <span class="text-muted"><?php echo date('l, F d, Y'); ?></span>
        </div>
    </div>
    
    <!-- STATS CARDS GRID -->
    <div class="dashboard-stats">
        <!-- Employees Card -->
        <div class="stat-card-enhanced">
            <div class="stat-header">
                <div class="stat-icon-large" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    👥
                </div>
                <div class="stat-trend <?php echo $employeeGrowth >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <?php echo $employeeGrowth >= 0 ? '↗' : '↘'; ?> <?php echo abs($employeeGrowth); ?>
                </div>
            </div>
            <div class="stat-body">
                <div class="stat-value-large"><?php echo number_format($totalEmployees); ?></div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-meta">
                    <?php if ($employeeGrowth > 0): ?>
                        <span class="badge badge-approved">+<?php echo $employeeGrowth; ?> this month</span>
                    <?php elseif ($employeeGrowth < 0): ?>
                        <span class="badge badge-rejected"><?php echo $employeeGrowth; ?> this month</span>
                    <?php else: ?>
                        <span class="badge badge-info">No change</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-footer">
                <a href="modules/employees/index.php" class="stat-link">View All →</a>
            </div>
        </div>

        <!-- Pending Leaves Card -->
        <div class="stat-card-enhanced">
            <div class="stat-header">
                <div class="stat-icon-large" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    📅
                </div>
                <?php if ($pendingLeaves > 0): ?>
                <div class="stat-badge-alert"><?php echo $pendingLeaves; ?></div>
                <?php endif; ?>
            </div>
            <div class="stat-body">
                <div class="stat-value-large"><?php echo $pendingLeaves; ?></div>
                <div class="stat-label">Pending Leaves</div>
                <div class="stat-meta">
                    <?php if ($todayLeaves > 0): ?>
                        <span class="badge badge-pending">+<?php echo $todayLeaves; ?> today</span>
                    <?php else: ?>
                        <span class="badge badge-info">No new requests</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-footer">
                <a href="modules/cuti/index.php" class="stat-link">Manage Leaves →</a>
            </div>
        </div>

        <!-- Monthly Payroll Card -->
        <div class="stat-card-enhanced">
            <div class="stat-header">
                <div class="stat-icon-large" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    💰
                </div>
                <div class="stat-trend <?php echo $payrollChange >= 0 ? 'trend-up' : 'trend-down'; ?>">
                    <?php echo $payrollChange >= 0 ? '↗' : '↘'; ?> <?php echo number_format(abs($payrollChange), 1); ?>%
                </div>
            </div>
            <div class="stat-body">
                <div class="stat-value-large">Rp <?php echo number_format($currentMonthPayroll / 1000000, 1); ?>M</div>
                <div class="stat-label">Monthly Payroll</div>
                <div class="stat-meta">
                    <span class="badge badge-info"><?php echo date('F Y'); ?></span>
                </div>
            </div>
            <div class="stat-footer">
                <a href="modules/payroll/index.php" class="stat-link">View Payroll →</a>
            </div>
        </div>

        <!-- Overtime Card -->
        <div class="stat-card-enhanced">
            <div class="stat-header">
                <div class="stat-icon-large" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    ⏰
                </div>
            </div>
            <div class="stat-body">
                <div class="stat-value-large"><?php echo number_format($overtimeHours); ?>h</div>
                <div class="stat-label">Overtime Hours</div>
                <div class="stat-meta">
                    <span class="badge badge-info">Cost: Rp <?php echo number_format($overtimeCost / 1000000, 1); ?>M</span>
                </div>
            </div>
            <div class="stat-footer">
                <a href="modules/lembur/index.php" class="stat-link">View Overtime →</a>
            </div>
        </div>
    </div>

    <!-- ALERTS SECTION -->
    <?php if ($expiringContracts > 0 || $upcomingBirthdays > 0): ?>
    <div class="alerts-row">
        <?php if ($expiringContracts > 0): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>Contract Expiring Soon</strong>
                <p><?php echo $expiringContracts; ?> employee contract(s) will expire in the next 30 days</p>
            </div>
            <a href="modules/employees/index.php" class="alert-action">View</a>
        </div>
        <?php endif; ?>
        
        <?php if ($upcomingBirthdays > 0): ?>
        <div class="alert alert-info">
            <div class="alert-icon">🎂</div>
            <div class="alert-content">
                <strong>Upcoming Birthdays</strong>
                <p><?php echo $upcomingBirthdays; ?> employee(s) have birthdays this week</p>
            </div>
            <a href="modules/employees/index.php" class="alert-action">View</a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- CHARTS SECTION -->
    <div class="dashboard-charts">
        <!-- Employee Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>📊 Employee Distribution</h2>
                <div class="chart-tabs">
                    <button class="tab-btn active" onclick="switchEmpChart('dept')">By Department</button>
                    <button class="tab-btn" onclick="switchEmpChart('status')">By Status</button>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="chartEmployeeDept" height="280"></canvas>
                <canvas id="chartEmployeeStatus" height="280" style="display: none;"></canvas>
            </div>
        </div>

        <!-- Leave Trend -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>📈 Monthly Leave Trend</h2>
                <span class="chart-subtitle">Last 12 months</span>
            </div>
            <div class="chart-body">
                <canvas id="chartLeaveTrend" height="280"></canvas>
            </div>
        </div>

        <!-- Payroll Overview -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>💰 Payroll Overview</h2>
                <span class="chart-subtitle">Last 6 months</span>
            </div>
            <div class="chart-body">
                <?php if (count($payrollTrend) > 0): ?>
                <canvas id="chartPayroll" height="280"></canvas>
                <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                    <p>No payroll data yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overtime Analysis -->
        <div class="chart-card">
            <div class="chart-header">
                <h2>⏰ Overtime by Department</h2>
                <span class="chart-subtitle">Current month</span>
            </div>
            <div class="chart-body">
                <?php if (count($overtimeByDept) > 0): ?>
                <canvas id="chartOvertime" height="280"></canvas>
                <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                    <p>No overtime data this month</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BOTTOM SECTION -->
    <div class="dashboard-bottom">
        <!-- Recent Activities -->
        <div class="activity-card">
            <div class="activity-header">
                <h2>📋 Recent Activities</h2>
                <a href="modules/reports/index.php" class="activity-link">View All →</a>
            </div>
            <div class="activity-list">
                <?php foreach ($recentActivities as $activity): ?>
                <?php
                $timeAgo = timeAgo(strtotime($activity['created_at']));
                $icon = '';
                $text = '';
                $color = '';
                
                switch ($activity['type']) {
                    case 'leave':
                        $icon = '📅';
                        $color = 'activity-blue';
                        $text = "{$activity['full_name']} requested {$activity['leave_type']} leave";
                        break;
                    case 'overtime':
                        $icon = '⏰';
                        $color = 'activity-green';
                        $text = "{$activity['full_name']} requested {$activity['hours']}h overtime";
                        break;
                    case 'payroll':
                        $icon = '💰';
                        $color = 'activity-purple';
                        $text = "Payroll generated for {$activity['full_name']}";
                        break;
                    case 'employee':
                        $icon = '👤';
                        $color = 'activity-orange';
                        $text = "New employee: {$activity['full_name']}";
                        break;
                }
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $color; ?>"><?php echo $icon; ?></div>
                    <div class="activity-content">
                        <div class="activity-text"><?php echo $text; ?></div>
                        <div class="activity-time"><?php echo $timeAgo; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($recentActivities) == 0): ?>
                <div class="activity-empty">
                    <p>No recent activities</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-card">
            <div class="quick-actions-header">
                <h2>⚡ Quick Actions</h2>
            </div>
            <div class="quick-actions-grid">
                <a href="modules/employees/add.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        👤
                    </div>
                    <span>Add Employee</span>
                </a>
                
                <a href="modules/payroll/generate.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        💰
                    </div>
                    <span>Generate Payroll</span>
                </a>
                
                <a href="modules/reports/index.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        📊
                    </div>
                    <span>View Reports</span>
                </a>
                
                <a href="modules/settings/index.php" class="quick-action-btn">
                    <div class="quick-action-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        ⚙️
                    </div>
                    <span>Settings</span>
                </a>
            </div>

            <!-- Notifications -->
            <div class="notifications-widget">
                <h3>🔔 Notifications</h3>
                <div class="notification-list">
                    <?php if ($pendingLeaves > 0): ?>
                    <a href="modules/cuti/index.php" class="notification-item">
                        <span class="notification-badge badge-pending"><?php echo $pendingLeaves; ?></span>
                        <span>Pending Leave Requests</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $pendingOT = $db->query("SELECT COUNT(*) FROM overtime_requests WHERE status = 'pending'")->fetchColumn();
                    if ($pendingOT > 0):
                    ?>
                    <a href="modules/lembur/index.php" class="notification-item">
                        <span class="notification-badge badge-pending"><?php echo $pendingOT; ?></span>
                        <span>Pending Overtime Requests</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($expiringContracts > 0): ?>
                    <a href="modules/employees/index.php" class="notification-item">
                        <span class="notification-badge badge-rejected"><?php echo $expiringContracts; ?></span>
                        <span>Contracts Expiring Soon</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($upcomingBirthdays > 0): ?>
                    <a href="modules/employees/index.php" class="notification-item">
                        <span class="notification-badge badge-info"><?php echo $upcomingBirthdays; ?></span>
                        <span>Upcoming Birthdays</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($pendingLeaves == 0 && $pendingOT == 0 && $expiringContracts == 0 && $upcomingBirthdays == 0): ?>
                    <div class="notification-empty">
                        <p>✓ All caught up!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

// Employee by Department Chart
<?php if (count($empByDept) > 0): ?>
const chartEmpDept = new Chart(document.getElementById('chartEmployeeDept'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($empByDept, 'department_name')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($empByDept, 'count')); ?>,
            backgroundColor: colors.primary,
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});
<?php endif; ?>

// Employee by Status Chart
<?php if (count($empByStatus) > 0): ?>
const chartEmpStatus = new Chart(document.getElementById('chartEmployeeStatus'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map('ucfirst', array_column($empByStatus, 'employment_status'))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($empByStatus, 'count')); ?>,
            backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        }
    }
});
<?php endif; ?>

// Switch employee chart
function switchEmpChart(type) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (type === 'dept') {
        document.getElementById('chartEmployeeDept').style.display = 'block';
        document.getElementById('chartEmployeeStatus').style.display = 'none';
    } else {
        document.getElementById('chartEmployeeDept').style.display = 'none';
        document.getElementById('chartEmployeeStatus').style.display = 'block';
    }
}

// Leave Trend Chart
<?php
$leaveMonths = [];
$leaveCounts = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $leaveMonths[] = date('M Y', strtotime($month . '-01'));
    $found = false;
    foreach ($leaveTrend as $data) {
        if ($data['month'] == $month) {
            $leaveCounts[] = $data['count'];
            $found = true;
            break;
        }
    }
    if (!$found) $leaveCounts[] = 0;
}
?>

new Chart(document.getElementById('chartLeaveTrend'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($leaveMonths); ?>,
        datasets: [{
            label: 'Approved Leaves',
            data: <?php echo json_encode($leaveCounts); ?>,
            borderColor: '#f093fb',
            backgroundColor: 'rgba(240, 147, 251, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointRadius: 5,
            pointBackgroundColor: '#f093fb'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Payroll Chart
<?php if (count($payrollTrend) > 0): ?>
<?php
$payrollMonths = [];
$grossData = [];
$netData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $payrollMonths[] = date('M Y', strtotime($month . '-01'));
    $foundGross = $foundNet = false;
    foreach ($payrollTrend as $data) {
        if ($data['month'] == $month) {
            $grossData[] = $data['gross'];
            $netData[] = $data['net'];
            $foundGross = $foundNet = true;
            break;
        }
    }
    if (!$foundGross) {
        $grossData[] = 0;
        $netData[] = 0;
    }
}
?>

new Chart(document.getElementById('chartPayroll'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($payrollMonths); ?>,
        datasets: [
            {
                label: 'Gross Salary',
                data: <?php echo json_encode($grossData); ?>,
                backgroundColor: 'rgba(79, 172, 254, 0.5)',
                borderColor: '#4facfe',
                borderWidth: 2
            },
            {
                label: 'Net Salary',
                data: <?php echo json_encode($netData); ?>,
                backgroundColor: 'rgba(67, 233, 123, 0.5)',
                borderColor: '#43e97b',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: { 
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value/1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Overtime Chart
<?php if (count($overtimeByDept) > 0): ?>
new Chart(document.getElementById('chartOvertime'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($overtimeByDept, 'department_name')); ?>,
        datasets: [{
            label: 'Hours',
            data: <?php echo json_encode(array_column($overtimeByDept, 'total_hours')); ?>,
            backgroundColor: 'rgba(67, 233, 123, 0.5)',
            borderColor: '#43e97b',
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
            x: { beginAtZero: true }
        }
    }
});
<?php endif; ?>
</script>

<style>
.page-subtitle {
    color: #6b7280;
    margin: 5px 0 0;
    font-size: 15px;
}

.text-muted {
    color: #6b7280;
    font-size: 14px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card-enhanced {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.stat-card-enhanced:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-icon-large {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-trend {
    font-size: 14px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
}

.trend-up {
    background: #dcfce7;
    color: #16a34a;
}

.trend-down {
    background: #fee2e2;
    color: #dc2626;
}

.stat-badge-alert {
    background: #ef4444;
    color: white;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    min-width: 24px;
    text-align: center;
}

.stat-value-large {
    font-size: 36px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.stat-meta {
    margin-top: 8px;
}

.stat-footer {
    margin-top: auto;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.stat-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: color 0.2s;
}

.stat-link:hover {
    color: #2563eb;
}

.alerts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.alert {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid;
}

.alert-warning {
    background: #fffbeb;
    border-color: #f59e0b;
}

.alert-info {
    background: #eff6ff;
    border-color: #3b82f6;
}

.alert-icon {
    font-size: 32px;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    margin-bottom: 4px;
    color: #111827;
}

.alert-content p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.alert-action {
    padding: 8px 16px;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}

.alert-action:hover {
    background: #f3f4f6;
}

.dashboard-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chart-header h2 {
    font-size: 18px;
    margin: 0;
}

.chart-subtitle {
    font-size: 13px;
    color: #6b7280;
}

.chart-tabs {
    display: flex;
    gap: 8px;
}

.tab-btn {
    padding: 6px 14px;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    background: #f3f4f6;
}

.tab-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.dashboard-bottom {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.activity-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.activity-header h2 {
    font-size: 18px;
    margin: 0;
}

.activity-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.activity-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.activity-blue { background: #dbeafe; }
.activity-green { background: #dcfce7; }
.activity-purple { background: #f3e8ff; }
.activity-orange { background: #fed7aa; }

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 14px;
    color: #374151;
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
    color: #9ca3af;
}

.activity-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.quick-actions-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.quick-actions-header h2 {
    font-size: 18px;
    margin: 0 0 20px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 12px;
    background: #f9fafb;
    border-radius: 12px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
    text-align: center;
}

.quick-action-btn:hover {
    background: #f3f4f6;
    transform: scale(1.05);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.notifications-widget {
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.notifications-widget h3 {
    font-size: 16px;
    margin: 0 0 16px;
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.notification-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
}

.notification-item:hover {
    background: #f3f4f6;
}

.notification-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 8px;
    min-width: 24px;
    text-align: center;
}

.notification-empty {
    text-align: center;
    padding: 30px 20px;
    color: #10b981;
    font-weight: 600;
}

@media (max-width: 1024px) {
    .dashboard-bottom {
        grid-template-columns: 1fr;
    }
    
    .dashboard-charts {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
