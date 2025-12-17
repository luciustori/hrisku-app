<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Bulk Jadwal Shift';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $department_id = (int)$_POST['department_id'];
    $shift_id = (int)$_POST['shift_id'];
    $work_days = $_POST['work_days']; // senin-jumat, senin-sabtu, senin-minggu
    $branch_id = (int)$_POST['branch_id'];
    $created_by = getUserId();
    
    try {
        $db->beginTransaction();
        
        // Get employees by department
        $empQuery = "SELECT id FROM employees WHERE department_id = :dept_id AND is_active = 1";
        $stmt = $db->prepare($empQuery);
        $stmt->execute(['dept_id' => $department_id]);
        $employees = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($employees)) {
            throw new Exception('Tidak ada karyawan aktif di departemen ini');
        }
        
        // Generate dates between start and end
        $dates = [];
        $current = strtotime($start_date);
        $end = strtotime($end_date);
        
        while ($current <= $end) {
            $dayOfWeek = date('w', $current); // 0 = Sunday, 6 = Saturday
            
            $include = false;
            if ($work_days == 'senin-jumat' && $dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $include = true;
            } elseif ($work_days == 'senin-sabtu' && $dayOfWeek >= 1 && $dayOfWeek <= 6) {
                $include = true;
            } elseif ($work_days == 'senin-minggu') {
                $include = true;
            }
            
            // Check if holiday
            $dateStr = date('Y-m-d', $current);
            $holidayCheck = $db->prepare("SELECT id FROM holidays WHERE holiday_date = :date");
            $holidayCheck->execute(['date' => $dateStr]);
            $isHoliday = $holidayCheck->fetch();
            
            if ($include && !$isHoliday) {
                $dates[] = $dateStr;
            }
            
            $current = strtotime('+1 day', $current);
        }
        
        if (empty($dates)) {
            throw new Exception('Tidak ada tanggal valid untuk dijadwalkan');
        }
        
        // Insert schedules
        $insertQuery = "INSERT INTO employee_schedules (employee_id, shift_id, schedule_date, branch_id, created_by)
                        VALUES (:emp_id, :shift_id, :date, :branch_id, :created_by)
                        ON DUPLICATE KEY UPDATE 
                        shift_id = VALUES(shift_id),
                        updated_at = NOW()";
        
        $stmt = $db->prepare($insertQuery);
        
        $count = 0;
        foreach ($employees as $emp_id) {
            foreach ($dates as $date) {
                $stmt->execute([
                    'emp_id' => $emp_id,
                    'shift_id' => $shift_id,
                    'date' => $date,
                    'branch_id' => $branch_id,
                    'created_by' => $created_by
                ]);
                $count++;
            }
        }
        
        $db->commit();
        
        $success = "Berhasil membuat {$count} jadwal untuk " . count($employees) . " karyawan pada " . count($dates) . " hari kerja";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get departments
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

// Get shifts
$shifts = $db->query("SELECT * FROM shifts WHERE is_active = 1 ORDER BY shift_code")->fetchAll(PDO::FETCH_ASSOC);

// Get branches
$branches = $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📅 Bulk Schedule Assignment</h1>
        <div>
            <a href="calendar.php" class="btn btn-secondary">← Kembali ke Kalender</a>
            <a href="index.php" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Buat Jadwal Massal</h2>
        </div>
        <div class="card-body">
            <div class="info-box">
                <strong>ℹ️ Informasi:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Jadwal akan dibuat untuk semua karyawan aktif di departemen yang dipilih</li>
                    <li>Hari libur nasional akan otomatis di-skip</li>
                    <li>Jika sudah ada jadwal di tanggal yang sama, akan di-update</li>
                </ul>
            </div>
            
            <form method="POST" action="" id="bulkScheduleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai *</label>
                        <input type="date" name="start_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Selesai *</label>
                        <input type="date" name="end_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Departemen *</label>
                        <select name="department_id" class="form-control" required onchange="loadEmployeeCount()">
                            <option value="">Pilih Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="employeeCount"></small>
                    </div>
                    <div class="form-group">
                        <label>Shift *</label>
                        <select name="shift_id" class="form-control" required>
                            <option value="">Pilih Shift</option>
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?php echo $shift['id']; ?>">
                                    <?php echo htmlspecialchars($shift['shift_name']); ?> 
                                    (<?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Hari Kerja *</label>
                        <select name="work_days" class="form-control" required>
                            <option value="">Pilih Hari Kerja</option>
                            <option value="senin-jumat">Senin - Jumat (5 hari)</option>
                            <option value="senin-sabtu">Senin - Sabtu (6 hari)</option>
                            <option value="senin-minggu">Senin - Minggu (7 hari)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cabang *</label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">Pilih Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>">
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="previewSchedule()">👁️ Preview</button>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Yakin membuat jadwal massal ini?')">
                        💾 Buat Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closePreview()">&times;</span>
            <h2>Preview Jadwal</h2>
            <div id="previewContent"></div>
            <button type="button" class="btn btn-secondary" onclick="closePreview()">Tutup</button>
        </div>
    </div>
</div>

<style>
.info-box {
    background: #e0e7ff;
    border-left: 4px solid var(--primary);
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<script>
function loadEmployeeCount() {
    const deptId = document.querySelector('select[name="department_id"]').value;
    const countEl = document.getElementById('employeeCount');
    
    if (!deptId) {
        countEl.textContent = '';
        return;
    }
    
    fetch('get_employee_count.php?department_id=' + deptId)
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                countEl.textContent = `${data.count} karyawan aktif di departemen ini`;
                countEl.style.color = '#10b981';
            } else {
                countEl.textContent = 'Tidak ada karyawan aktif';
                countEl.style.color = '#ef4444';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function previewSchedule() {
    const form = document.getElementById('bulkScheduleForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        alert('Lengkapi semua field terlebih dahulu');
        return;
    }
    
    // Convert to JSON
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    
    fetch('preview_bulk_schedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            document.getElementById('previewContent').innerHTML = `
                <div class="preview-summary">
                    <h3>Ringkasan Jadwal</h3>
                    <ul>
                        <li><strong>Departemen:</strong> ${result.department_name}</li>
                        <li><strong>Jumlah Karyawan:</strong> ${result.employee_count} orang</li>
                        <li><strong>Shift:</strong> ${result.shift_name}</li>
                        <li><strong>Periode:</strong> ${result.start_date} s/d ${result.end_date}</li>
                        <li><strong>Hari Kerja:</strong> ${result.work_days_text}</li>
                        <li><strong>Total Hari Kerja:</strong> ${result.work_day_count} hari</li>
                        <li><strong>Total Jadwal:</strong> ${result.total_schedules} jadwal</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <strong>📅 Tanggal yang akan dijadwalkan:</strong><br>
                        ${result.dates.join(', ')}
                    </div>
                </div>
            `;
            document.getElementById('previewModal').style.display = 'block';
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}
</script>

<?php include '../../includes/footer.php'; ?>
