<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin']);

$page_title = 'Holidays';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $holidayName = sanitize($_POST['holiday_name']);
            $holidayDate = $_POST['holiday_date'];
            $holidayType = $_POST['holiday_type'];
            
            try {
                if ($action == 'add') {
                    $stmt = $db->prepare("INSERT INTO holidays (holiday_name, holiday_date, holiday_type) 
                                          VALUES (?, ?, ?)");
                    $stmt->execute([$holidayName, $holidayDate, $holidayType]);
                    $_SESSION['success'] = 'Holiday added successfully!';
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $db->prepare("UPDATE holidays SET holiday_name = ?, holiday_date = ?, 
                                          holiday_type = ? WHERE id = ?");
                    $stmt->execute([$holidayName, $holidayDate, $holidayType, $id]);
                    $_SESSION['success'] = 'Holiday updated successfully!';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("DELETE FROM holidays WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Holiday deleted successfully!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
        }
        
        header('Location: holidays.php');
        exit();
    }
}

// Get year filter
$year = $_GET['year'] ?? date('Y');

// Get holidays
$holidays = $db->query("SELECT * FROM holidays 
                       WHERE YEAR(holiday_date) = $year 
                       ORDER BY holiday_date")->fetchAll(PDO::FETCH_ASSOC);

// Get available years
$years = $db->query("SELECT DISTINCT YEAR(holiday_date) as year FROM holidays ORDER BY year DESC")
            ->fetchAll(PDO::FETCH_COLUMN);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📅 Holidays</h1>
        <div>
            <button onclick="showAddModal()" class="btn btn-primary">+ Add Holiday</button>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>
    
    <!-- Filter & Stats -->
    <div class="filter-bar">
        <div class="stats-mini" style="flex: 1;">
            <div class="stat-mini" style="border-color: #3b82f6;">
                <span class="stat-label">📅 Total Holidays</span>
                <span class="stat-value"><?php echo count($holidays); ?></span>
            </div>
            <div class="stat-mini" style="border-color: #10b981;">
                <span class="stat-label">🎉 National</span>
                <span class="stat-value"><?php echo count(array_filter($holidays, fn($h) => $h['holiday_type'] == 'nasional')); ?></span>
            </div>
            <div class="stat-mini" style="border-color: #f59e0b;">
                <span class="stat-label">🕌 Religious</span>
                <span class="stat-value"><?php echo count(array_filter($holidays, fn($h) => $h['holiday_type'] == 'cuti_bersama')); ?></span>
            </div>
        </div>
        
        <div class="year-filter">
            <label>Year:</label>
            <select onchange="window.location.href='holidays.php?year='+this.value" class="form-control">
                <?php foreach ($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                    <?php echo $y; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- Holidays List -->
    <div class="card">
        <div class="card-header">
            <h2>Holidays <?php echo $year; ?> (<?php echo count($holidays); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Holiday Name</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Type</th>
                        <th>Countdown</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($holidays) > 0): ?>
                        <?php foreach ($holidays as $holiday): ?>
                        <?php
                        $date = strtotime($holiday['holiday_date']);
                        $today = strtotime(date('Y-m-d'));
                        $daysUntil = floor(($date - $today) / 86400);
                        
                        $dayName = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][date('w', $date)];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong>
                            </td>
                            <td>
                                <?php echo date('d M Y', $date); ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo $dayName; ?></span>
                            </td>
                            <td>
                                <?php
                                $typeClass = ['nasional' => 'badge-tetap', 'cuti_bersama' => 'badge-kontrak'];
                                $typeLabel = ['nasional' => '🎉 National', 'cuti_bersama' => '🕌 Cuti Bersama'];
                                ?>
                                <span class="badge <?php echo $typeClass[$holiday['holiday_type']] ?? 'badge-magang'; ?>">
                                    <?php echo $typeLabel[$holiday['holiday_type']] ?? $holiday['holiday_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($daysUntil < 0): ?>
                                    <span class="text-muted">Passed</span>
                                <?php elseif ($daysUntil == 0): ?>
                                    <span style="color: #10b981; font-weight: 700;">🎉 Today!</span>
                                <?php elseif ($daysUntil <= 7): ?>
                                    <span style="color: #f59e0b; font-weight: 700;">⚠️ <?php echo $daysUntil; ?> days</span>
                                <?php else: ?>
                                    <span class="text-muted"><?php echo $daysUntil; ?> days</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick='editHoliday(<?php echo json_encode($holiday); ?>)' 
                                        class="btn-action" title="Edit">✏️</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this holiday?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $holiday['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Delete">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">📅</p>
                                <p style="margin: 10px 0 0;">No holidays for <?php echo $year; ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="holidayModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Holiday</h3>
            <button onclick="closeModal()" class="modal-close">×</button>
        </div>
        <form method="POST" id="holidayForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="holidayId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Holiday Name *</label>
                    <input type="text" name="holiday_name" id="holidayName" class="form-control" required
                           placeholder="e.g. Independence Day">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="holiday_date" id="holidayDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="holiday_type" id="holidayType" class="form-control" required>
                            <option value="nasional">🎉 National</option>
                            <option value="cuti_bersama">🕌 Cuti Bersama</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Holiday</button>
            </div>
        </form>
    </div>
</div>

<style>
.filter-bar {
    display: flex;
    gap: 20px;
    align-items: center;
    margin-bottom: 20px;
}

.year-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.year-filter label {
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.year-filter select {
    min-width: 120px;
}

.stats-mini {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-mini {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 5px;
    border-left: 4px solid;
}

.stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
}

.text-muted { color: #9ca3af; }

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.show { display: flex !important; }

.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 32px;
    cursor: pointer;
    color: #9ca3af;
    line-height: 1;
}

.modal-close:hover { color: #374151; }

.modal-body { padding: 20px; }

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Holiday';
    document.getElementById('formAction').value = 'add';
    document.getElementById('holidayForm').reset();
    document.getElementById('holidayModal').classList.add('show');
}

function editHoliday(holiday) {
    document.getElementById('modalTitle').textContent = 'Edit Holiday';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('holidayId').value = holiday.id;
    document.getElementById('holidayName').value = holiday.holiday_name;
    document.getElementById('holidayDate').value = holiday.holiday_date;
    document.getElementById('holidayType').value = holiday.holiday_type;
    document.getElementById('holidayModal').classList.add('show');
}

function closeModal() {
    document.getElementById('holidayModal').classList.remove('show');
}

document.getElementById('holidayModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
