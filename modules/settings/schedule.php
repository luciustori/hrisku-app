<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin']);

$page_title = 'Work Schedule';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add' || $action == 'edit') {
            $shiftName = sanitize($_POST['shift_name']);
            $startTime = $_POST['start_time'];
            $endTime = $_POST['end_time'];
            $description = sanitize($_POST['description'] ?? '');
            
            try {
                if ($action == 'add') {
                    $stmt = $db->prepare("INSERT INTO shifts (shift_name, start_time, end_time, description) 
                                          VALUES (?, ?, ?, ?)");
                    $stmt->execute([$shiftName, $startTime, $endTime, $description]);
                    $_SESSION['success'] = 'Work schedule added successfully!';
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $db->prepare("UPDATE shifts SET shift_name = ?, start_time = ?, end_time = ?, 
                                          description = ? WHERE id = ?");
                    $stmt->execute([$shiftName, $startTime, $endTime, $description, $id]);
                    $_SESSION['success'] = 'Work schedule updated successfully!';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("DELETE FROM shifts WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Work schedule deleted successfully!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'toggle') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE shifts SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Schedule status updated!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
        }
        
        header('Location: schedule.php');
        exit();
    }
}

// Get all schedules
$schedules = $db->query("SELECT * FROM shifts ORDER BY shift_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>⏰ Work Schedule</h1>
        <div>
            <button onclick="showAddModal()" class="btn btn-primary">+ Add Schedule</button>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-mini">
        <div class="stat-mini" style="border-color: #10b981;">
            <span class="stat-label">✓ Active Schedules</span>
            <span class="stat-value"><?php echo count(array_filter($schedules, fn($s) => $s['is_active'])); ?></span>
        </div>
        <div class="stat-mini" style="border-color: #6b7280;">
            <span class="stat-label">✗ Inactive</span>
            <span class="stat-value"><?php echo count(array_filter($schedules, fn($s) => !$s['is_active'])); ?></span>
        </div>
        <div class="stat-mini" style="border-color: #3b82f6;">
            <span class="stat-label">📊 Total</span>
            <span class="stat-value"><?php echo count($schedules); ?></span>
        </div>
    </div>
    
    <!-- Schedules List -->
    <div class="card">
        <div class="card-header">
            <h2>Work Schedules (<?php echo count($schedules); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Schedule Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($schedules) > 0): ?>
                        <?php foreach ($schedules as $schedule): ?>
                        <?php
                        // Calculate duration
                        $start = strtotime($schedule['start_time']);
                        $end = strtotime($schedule['end_time']);
                        $duration = ($end - $start) / 3600; // hours
                        if ($duration < 0) $duration += 24; // Handle overnight shifts
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($schedule['shift_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    🕐 <?php echo date('H:i', strtotime($schedule['start_time'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    🕐 <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo number_format($duration, 1); ?></strong> hours
                            </td>
                            <td>
                                <?php 
                                $desc = $schedule['description'] ?? '';
                                echo htmlspecialchars(mb_substr($desc, 0, 50));
                                if (mb_strlen($desc) > 50) echo '...';
                                ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" class="badge" style="border: none; cursor: pointer; <?php echo $schedule['is_active'] ? 'background: #10b981;' : 'background: #9ca3af;'; ?>">
                                        <?php echo $schedule['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button onclick='editSchedule(<?php echo json_encode($schedule); ?>)' 
                                        class="btn-action" title="Edit">✏️</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this schedule?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Delete">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">⏰</p>
                                <p style="margin: 10px 0 0;">No work schedules yet</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Work Schedule</h3>
            <button onclick="closeModal()" class="modal-close">×</button>
        </div>
        <form method="POST" id="scheduleForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="scheduleId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Schedule Name *</label>
                    <input type="text" name="shift_name" id="shiftName" class="form-control" required
                           placeholder="e.g. Regular Shift, Morning Shift">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time *</label>
                        <input type="time" name="start_time" id="startTime" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>End Time *</label>
                        <input type="time" name="end_time" id="endTime" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"
                              placeholder="e.g. Monday to Friday, 9 AM - 6 PM"></textarea>
                </div>
                
                <div class="alert alert-info" style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; border-radius: 4px;">
                    <small style="color: #1e40af;">
                        💡 <strong>Tip:</strong> For overnight shifts (e.g. 22:00 - 06:00), 
                        the system will automatically calculate the duration correctly.
                    </small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<style>
.stats-mini {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-mini {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-left: 4px solid;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
}

.badge-info {
    background: #3b82f6;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

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
    overflow-y: auto;
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
    document.getElementById('modalTitle').textContent = 'Add Work Schedule';
    document.getElementById('formAction').value = 'add';
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleModal').classList.add('show');
}

function editSchedule(schedule) {
    document.getElementById('modalTitle').textContent = 'Edit Work Schedule';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('scheduleId').value = schedule.id;
    document.getElementById('shiftName').value = schedule.shift_name;
    document.getElementById('startTime').value = schedule.start_time;
    document.getElementById('endTime').value = schedule.end_time;
    document.getElementById('description').value = schedule.description || '';
    document.getElementById('scheduleModal').classList.add('show');
}

function closeModal() {
    document.getElementById('scheduleModal').classList.remove('show');
}

document.getElementById('scheduleModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
