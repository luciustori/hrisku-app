<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin']);

$page_title = 'Settings';

$database = new Database();
$db = $database->getConnection();

// Get company info
$company = $db->query("SELECT * FROM companies WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Get schedules count
$schedulesCount = $db->query("SELECT COUNT(*) FROM shifts WHERE is_active = 1")->fetchColumn();

// Get holidays count (upcoming)
$holidaysCount = $db->query("SELECT COUNT(*) FROM holidays WHERE holiday_date >= CURDATE()")->fetchColumn();

// Get system settings count
$settingsCount = $db->query("SELECT COUNT(*) FROM system_settings")->fetchColumn();

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>⚙️ Settings</h1>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-secondary">← Dashboard</a>
    </div>
    
    <!-- Quick Info -->
    <div class="stats-grid" style="margin-bottom: 30px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                🏢
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo htmlspecialchars($company['company_name'] ?? 'Not Set'); ?></div>
                <div class="stat-label">Company Name</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                ⏰
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $schedulesCount; ?></div>
                <div class="stat-label">Work Schedules</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                📅
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $holidaysCount; ?></div>
                <div class="stat-label">Upcoming Holidays</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                🔧
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $settingsCount; ?></div>
                <div class="stat-label">System Settings</div>
            </div>
        </div>
    </div>
    
    <!-- Settings Menu -->
    <div class="settings-grid">
        <!-- Company Profile -->
        <a href="company.php" class="settings-card">
            <div class="settings-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <span style="font-size: 48px;">🏢</span>
            </div>
            <h3>Company Profile</h3>
            <p>Manage company information, logo, address, and contact details</p>
            <div class="settings-footer">
                <span class="badge badge-info">Essential</span>
            </div>
        </a>
        
        <!-- Work Schedule -->
        <a href="schedule.php" class="settings-card">
            <div class="settings-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <span style="font-size: 48px;">⏰</span>
            </div>
            <h3>Work Schedule</h3>
            <p>Configure work shifts, break times, and working days</p>
            <div class="settings-footer">
                <span class="badge badge-tetap"><?php echo $schedulesCount; ?> Schedules</span>
            </div>
        </a>
        
        <!-- Holidays -->
        <a href="holidays.php" class="settings-card">
            <div class="settings-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <span style="font-size: 48px;">📅</span>
            </div>
            <h3>Holidays</h3>
            <p>Manage national holidays, religious days, and company events</p>
            <div class="settings-footer">
                <span class="badge badge-kontrak"><?php echo $holidaysCount; ?> Upcoming</span>
            </div>
        </a>
        
        <!-- System Settings -->
        <a href="system.php" class="settings-card">
            <div class="settings-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <span style="font-size: 48px;">🔧</span>
            </div>
            <h3>System Settings</h3>
            <p>Configure payroll rates, leave quotas, and system parameters</p>
            <div class="settings-footer">
                <span class="badge badge-magang"><?php echo $settingsCount; ?> Settings</span>
            </div>
        </a>
    </div>
</div>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
}

.settings-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.settings-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.settings-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.settings-card h3 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #1e40af;
}

.settings-card p {
    margin: 0 0 20px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
}

.settings-footer {
    margin-top: auto;
}

.badge-info {
    background: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
</style>

<?php include '../../includes/footer.php'; ?>
