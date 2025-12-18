<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin']);

$page_title = 'System Settings';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $db->commit();
        $_SESSION['success'] = 'System settings updated successfully!';
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: system.php');
    exit();
}

// Get all settings grouped by category
$settings = $db->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key")
               ->fetchAll(PDO::FETCH_ASSOC);

// Group settings
$grouped = [];
foreach ($settings as $setting) {
    $group = $setting['setting_group'] ?? 'general';
    $grouped[$group][] = $setting;
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🔧 System Settings</h1>
        <a href="index.php" class="btn btn-secondary">← Back to Settings</a>
    </div>
    
    <form method="POST">
        <!-- Payroll Settings -->
        <?php if (isset($grouped['payroll'])): ?>
        <div class="card settings-section">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2>💰 Payroll Settings</h2>
            </div>
            <div class="card-body">
                <?php foreach ($grouped['payroll'] as $setting): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <label class="setting-label">
                            <?php echo ucwords(str_replace('_', ' ', str_replace(['payroll_', 'overtime_'], '', $setting['setting_key']))); ?>
                        </label>
                        <p class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></p>
                    </div>
                    <div class="setting-input">
                        <?php if ($setting['setting_type'] == 'number'): ?>
                            <input type="number" 
                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                   step="<?php echo strpos($setting['setting_key'], 'rate') !== false ? '1000' : '1'; ?>">
                        <?php elseif ($setting['setting_type'] == 'boolean'): ?>
                            <select name="settings[<?php echo $setting['setting_key']; ?>]" class="form-control">
                                <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Yes</option>
                                <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>No</option>
                            </select>
                        <?php else: ?>
                            <input type="text" 
                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Leave Settings -->
        <?php if (isset($grouped['leave'])): ?>
        <div class="card settings-section">
            <div class="card-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <h2>📅 Leave Settings</h2>
            </div>
            <div class="card-body">
                <?php foreach ($grouped['leave'] as $setting): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <label class="setting-label">
                            <?php echo ucwords(str_replace('_', ' ', str_replace(['annual_', 'max_', 'sick_'], '', $setting['setting_key']))); ?>
                        </label>
                        <p class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></p>
                    </div>
                    <div class="setting-input">
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Attendance Settings -->
        <?php if (isset($grouped['attendance'])): ?>
        <div class="card settings-section">
            <div class="card-header" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <h2>⏰ Attendance Settings</h2>
            </div>
            <div class="card-body">
                <?php foreach ($grouped['attendance'] as $setting): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <label class="setting-label">
                            <?php echo ucwords(str_replace('_', ' ', str_replace(['late_', 'early_', 'tolerance_'], '', $setting['setting_key']))); ?>
                        </label>
                        <p class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></p>
                    </div>
                    <div class="setting-input">
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Employee Settings -->
        <?php if (isset($grouped['employee'])): ?>
        <div class="card settings-section">
            <div class="card-header" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <h2>👥 Employee Settings</h2>
            </div>
            <div class="card-body">
                <?php foreach ($grouped['employee'] as $setting): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <label class="setting-label">
                            <?php echo ucwords(str_replace('_', ' ', str_replace(['probation_', 'contract_'], '', $setting['setting_key']))); ?>
                        </label>
                        <p class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></p>
                    </div>
                    <div class="setting-input">
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- General Settings -->
        <?php if (isset($grouped['general'])): ?>
        <div class="card settings-section">
            <div class="card-header" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <h2>⚙️ General Settings</h2>
            </div>
            <div class="card-body">
                <?php foreach ($grouped['general'] as $setting): ?>
                <div class="setting-item">
                    <div class="setting-info">
                        <label class="setting-label">
                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                        </label>
                        <p class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></p>
                    </div>
                    <div class="setting-input">
                        <input type="number" 
                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Save Button -->
        <div class="save-bar">
            <button type="submit" class="btn btn-primary btn-lg">
                💾 Save All Settings
            </button>
            <a href="index.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<style>
.settings-section {
    margin-bottom: 25px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info {
    flex: 1;
    padding-right: 30px;
}

.setting-label {
    display: block;
    font-weight: 600;
    font-size: 15px;
    color: #1e40af;
    margin-bottom: 5px;
}

.setting-description {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}

.setting-input {
    min-width: 200px;
}

.setting-input .form-control {
    text-align: right;
    font-weight: 600;
    font-size: 15px;
}

.save-bar {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 20px;
    border-top: 3px solid #e5e7eb;
    box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    border-radius: 12px;
}

.btn-lg {
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .setting-item {
        flex-direction: column;
    }
    
    .setting-info {
        padding-right: 0;
        margin-bottom: 15px;
    }
    
    .setting-input {
        width: 100%;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
