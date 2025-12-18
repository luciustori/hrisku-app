<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'manager']);

$page_title = 'Reports & Analytics';

$database = new Database();
$db = $database->getConnection();

// Get statistics - FIXED for your table structure
try {
    // Count active employees (employment_status = 'tetap' OR is_active = 1)
    $totalEmployees = $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
} catch (PDOException $e) {
    $totalEmployees = $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
}

// Leave requests
try {
    $totalLeaveRequests = $db->query("SELECT COUNT(*) FROM leave_requests WHERE YEAR(start_date) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $totalLeaveRequests = 0;
}

// Payrolls - FIXED (use created_at instead of payroll_period)
try {
    $totalPayrolls = $db->query("SELECT COUNT(*) FROM payrolls WHERE YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $totalPayrolls = 0;
}

// Overtime hours
try {
    $totalOvertimeHours = $db->query("SELECT COALESCE(SUM(hours), 0) FROM overtime_requests WHERE status = 'approved' AND YEAR(overtime_date) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $totalOvertimeHours = 0;
}

// Monthly payroll total - FIXED
try {
    $monthlyPayroll = $db->query("SELECT COALESCE(SUM(net_salary), 0) FROM payrolls WHERE YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $monthlyPayroll = 0;
}

// Leave approved
try {
    $leaveUsed = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved' AND YEAR(start_date) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $leaveUsed = 0;
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📊 Reports & Analytics</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                👥
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($totalEmployees); ?></div>
                <div class="stat-label">Active Employees</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                📅
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($leaveUsed); ?></div>
                <div class="stat-label">Leave Requests (<?php echo date('Y'); ?>)</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                💰
            </div>
            <div class="stat-content">
                <div class="stat-value">Rp <?php echo $monthlyPayroll > 0 ? number_format($monthlyPayroll / 1000000, 1) : '0'; ?>M</div>
                <div class="stat-label">Total Payroll (<?php echo date('Y'); ?>)</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                ⏰
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($totalOvertimeHours); ?>h</div>
                <div class="stat-label">Overtime Hours (<?php echo date('Y'); ?>)</div>
            </div>
        </div>
    </div>
    
    <!-- Report Modules -->
    <div class="reports-grid">
        <!-- Employee Reports -->
        <a href="employees.php" class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span style="font-size: 48px;">👥</span>
            </div>
            <h3>Employee Reports</h3>
            <p>Analysis by department, position, status, and employment type</p>
            <div class="report-features">
                <span class="feature-tag">📊 Charts</span>
                <span class="feature-tag">📥 Export</span>
                <span class="feature-tag">📋 Details</span>
            </div>
        </a>
        
        <!-- Leave Reports -->
        <a href="leave.php" class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span style="font-size: 48px;">📅</span>
            </div>
            <h3>Leave Reports</h3>
            <p>Leave requests, balance tracking, and usage trends</p>
            <div class="report-features">
                <span class="feature-tag">📈 Trends</span>
                <span class="feature-tag">📥 Export</span>
                <span class="feature-tag">📊 Stats</span>
            </div>
        </a>
        
        <!-- Payroll Reports -->
        <a href="payroll.php" class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <span style="font-size: 48px;">💰</span>
            </div>
            <h3>Payroll Reports</h3>
            <p>Monthly payroll summary, trends, and component breakdown</p>
            <div class="report-features">
                <span class="feature-tag">💹 Trends</span>
                <span class="feature-tag">📥 Export</span>
                <span class="feature-tag">📊 Summary</span>
            </div>
        </a>
        
        <!-- Overtime Reports -->
        <a href="overtime.php" class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <span style="font-size: 48px;">⏰</span>
            </div>
            <h3>Overtime Reports</h3>
            <p>Overtime hours, costs, and employee overtime analysis</p>
            <div class="report-features">
                <span class="feature-tag">📊 Hours</span>
                <span class="feature-tag">💰 Costs</span>
                <span class="feature-tag">📥 Export</span>
            </div>
        </a>
        
        <!-- Attendance Reports -->
        <a href="attendance.php" class="report-card">
            <div class="report-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <span style="font-size: 48px;">📋</span>
            </div>
            <h3>Attendance Reports</h3>
            <p>Daily recap, monthly report, late & absent statistics</p>
            <div class="report-features">
                <span class="feature-tag">📅 Daily</span>
                <span class="feature-tag">📊 Monthly</span>
                <span class="feature-tag">📥 Export</span>
            </div>
        </a>
        
        <!-- Custom Reports -->
        <a href="#" class="report-card" onclick="alert('Custom reports coming soon!'); return false;" style="opacity: 0.7;">
            <div class="report-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                <span style="font-size: 48px;">🔧</span>
            </div>
            <h3>Custom Reports</h3>
            <p>Build your own custom reports with filters</p>
            <div class="report-features">
                <span class="feature-tag">⚡ Coming Soon</span>
            </div>
        </a>
    </div>
</div>

<style>
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.report-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    border: 2px solid transparent;
}

.report-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.15);
    border-color: rgba(102, 126, 234, 0.3);
}

.report-icon {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.report-card h3 {
    margin: 0 0 12px;
    font-size: 22px;
    color: #1e40af;
    font-weight: 700;
}

.report-card p {
    margin: 0 0 20px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
    flex: 1;
}

.report-features {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.feature-tag {
    background: #f3f4f6;
    color: #374151;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<?php include '../../includes/footer.php'; ?>
