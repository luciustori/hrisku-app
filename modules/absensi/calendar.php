<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Kalender Jadwal';

$database = new Database();
$db = $database->getConnection();

// Get current month/year or from GET
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get shifts for dropdown
$shifts = $db->query("SELECT * FROM shifts WHERE is_active = 1 ORDER BY shift_code")->fetchAll(PDO::FETCH_ASSOC);

// Get employees for dropdown
$employees = $db->query("SELECT id, nik, full_name, department_id FROM employees WHERE is_active = 1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Get departments
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

// Get holidays for this month
$holidays = $db->prepare("SELECT * FROM holidays WHERE MONTH(holiday_date) = :month AND YEAR(holiday_date) = :year");
$holidays->execute(['month' => $month, 'year' => $year]);
$holidays = $holidays->fetchAll(PDO::FETCH_ASSOC);
$holiday_dates = array_column($holidays, 'holiday_name', 'holiday_date');

// Get schedules for this month
$firstDay = date('Y-m-01', strtotime("$year-$month-01"));
$lastDay = date('Y-m-t', strtotime("$year-$month-01"));

$schedules_query = "SELECT es.*, e.full_name, e.nik, s.shift_name, s.shift_code, s.color_code, s.start_time, s.end_time
                    FROM employee_schedules es
                    JOIN employees e ON es.employee_id = e.id
                    JOIN shifts s ON es.shift_id = s.id
                    WHERE es.schedule_date BETWEEN :start AND :end
                    ORDER BY es.schedule_date, e.full_name";
$stmt = $db->prepare($schedules_query);
$stmt->execute(['start' => $firstDay, 'end' => $lastDay]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group schedules by date
$schedules_by_date = [];
foreach ($schedules as $schedule) {
    $date = $schedule['schedule_date'];
    if (!isset($schedules_by_date[$date])) {
        $schedules_by_date[$date] = [];
    }
    $schedules_by_date[$date][] = $schedule;
}

// Calendar helper functions
$firstDayOfMonth = date('w', strtotime($firstDay)); // 0 = Sunday
$daysInMonth = date('t', strtotime($firstDay));

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Kalender Jadwal Shift</h1>
        <div>
            <button class="btn btn-primary" onclick="openScheduleModal()">+ Tambah Jadwal</button>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <!-- Calendar Navigation -->
    <div class="calendar-nav">
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-secondary">
            ← <?php echo $months[$prevMonth]; ?>
        </a>
        
        <h2><?php echo $months[$month]; ?> <?php echo $year; ?></h2>
        
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-secondary">
            <?php echo $months[$nextMonth]; ?> →
        </a>
    </div>
    
    <!-- Legend -->
    <div class="calendar-legend">
        <div class="legend-title">Keterangan:</div>
        <?php foreach ($shifts as $shift): ?>
            <div class="legend-item">
                <span class="legend-color" style="background: <?php echo $shift['color_code']; ?>"></span>
                <span><?php echo $shift['shift_name']; ?> (<?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?>)</span>
            </div>
        <?php endforeach; ?>
        <div class="legend-item">
            <span class="legend-color" style="background: #fca5a5;"></span>
            <span>Hari Libur</span>
        </div>
    </div>
    
    <!-- Calendar Grid -->
    <div class="calendar-container">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Minggu</th>
                    <th>Senin</th>
                    <th>Selasa</th>
                    <th>Rabu</th>
                    <th>Kamis</th>
                    <th>Jumat</th>
                    <th>Sabtu</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dayCount = 1;
                $weekCount = 0;
                
                // Start calendar
                while ($dayCount <= $daysInMonth) {
                    echo '<tr>';
                    
                    for ($i = 0; $i < 7; $i++) {
                        if (($weekCount == 0 && $i < $firstDayOfMonth) || $dayCount > $daysInMonth) {
                            echo '<td class="calendar-day empty"></td>';
                        } else {
                            $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $dayCount);
                            $isToday = $currentDate == date('Y-m-d');
                            $isWeekend = ($i == 0 || $i == 6);
                            $isHoliday = isset($holiday_dates[$currentDate]);
                            
                            $dayClass = 'calendar-day';
                            if ($isToday) $dayClass .= ' today';
                            if ($isWeekend) $dayClass .= ' weekend';
                            if ($isHoliday) $dayClass .= ' holiday';
                            
                            echo "<td class='$dayClass' data-date='$currentDate'>";
                            echo "<div class='day-number'>$dayCount</div>";
                            
                            if ($isHoliday) {
                                echo "<div class='holiday-label'>" . htmlspecialchars($holiday_dates[$currentDate]) . "</div>";
                            }
                            
                            if (isset($schedules_by_date[$currentDate])) {
                                echo "<div class='schedule-list'>";
                                foreach ($schedules_by_date[$currentDate] as $schedule) {
                                    echo "<div class='schedule-item' style='background: " . $schedule['color_code'] . "' 
                                          onclick='viewSchedule(" . json_encode($schedule) . ")'>";
                                    echo "<span class='schedule-shift'>" . $schedule['shift_code'] . "</span>";
                                    echo "<span class='schedule-name'>" . htmlspecialchars($schedule['full_name']) . "</span>";
                                    echo "</div>";
                                }
                                echo "</div>";
                            }
                            
                            echo "<button class='add-schedule-btn' onclick='openScheduleModal(\"$currentDate\")'>+</button>";
                            echo "</td>";
                            
                            $dayCount++;
                        }
                    }
                    
                    echo '</tr>';
                    $weekCount++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Schedule Modal -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeScheduleModal()">&times;</span>
        <h2>Tambah Jadwal Shift</h2>
        <form id="scheduleForm" onsubmit="event.preventDefault(); saveSchedule(event);">

            <div class="form-group">
                <label>Tanggal *</label>
                <input type="date" id="schedule_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Departemen (Filter)</label>
                <select id="filter_department" class="form-control" onchange="filterEmployees()">
                    <option value="">Semua Departemen</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Karyawan *</label>
                <select id="employee_id" class="form-control" required multiple size="8">
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>" data-dept="<?php echo $emp['department_id']; ?>">
                            [<?php echo $emp['nik']; ?>] <?php echo htmlspecialchars($emp['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Tahan Ctrl untuk pilih multiple karyawan</small>
            </div>
            
            <div class="form-group">
                <label>Shift *</label>
                <select id="shift_id" class="form-control" required>
                    <option value="">Pilih Shift</option>
                    <?php foreach ($shifts as $shift): ?>
                        <option value="<?php echo $shift['id']; ?>">
                            <?php echo $shift['shift_name']; ?> 
                            (<?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Catatan</label>
                <textarea id="notes" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" id="saveScheduleBtn">Simpan Jadwal</button>
            <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">Batal</button>
        </form>
    </div>
</div>

<!-- View Schedule Modal -->
<div id="viewScheduleModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewScheduleModal()">&times;</span>
        <h2>Detail Jadwal</h2>
        <div id="scheduleDetail"></div>
        <button type="button" class="btn btn-danger" onclick="deleteSchedule()">Hapus Jadwal</button>
        <button type="button" class="btn btn-secondary" onclick="closeViewScheduleModal()">Tutup</button>
    </div>
</div>

<script>
let currentScheduleId = null;

function openScheduleModal(date = '') {
    if (date) {
        document.getElementById('schedule_date').value = date;
    } else {
        document.getElementById('schedule_date').value = '<?php echo date('Y-m-d'); ?>';
    }
    document.getElementById('scheduleModal').style.display = 'block';
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').style.display = 'none';
    document.getElementById('scheduleForm').reset();
}

function filterEmployees() {
    const deptId = document.getElementById('filter_department').value;
    const options = document.querySelectorAll('#employee_id option');
    
    options.forEach(opt => {
        if (!deptId || opt.dataset.dept == deptId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}

function saveSchedule() {
    const date = document.getElementById('schedule_date').value;
    const employeeSelect = document.getElementById('employee_id');
    const employees = Array.from(employeeSelect.selectedOptions).map(opt => opt.value);
    const shiftId = document.getElementById('shift_id').value;
    const notes = document.getElementById('notes').value;
    
    // Validasi
    if (!date) {
        alert('Pilih tanggal terlebih dahulu');
        return;
    }
    
    if (employees.length === 0) {
        alert('Pilih minimal 1 karyawan');
        return;
    }
    
    if (!shiftId) {
        alert('Pilih shift terlebih dahulu');
        return;
    }
    
    // Disable button untuk prevent double submit
    const submitBtn = event.target;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';
    
    // Data yang akan dikirim
    const data = {
        date: date,
        employees: employees,
        shift_id: parseInt(shiftId),
        notes: notes
    };
    
    console.log('Sending data:', data); // Debug
    
    fetch('save_schedule.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        // Cek apakah response ok
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        
        // Cek apakah response adalah JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Response is not JSON:', text);
                throw new Error('Server did not return JSON. Response: ' + text.substring(0, 100));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response:', data); // Debug
        
        if (data.success) {
            alert(data.message || 'Jadwal berhasil disimpan');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
            submitBtn.disabled = false;
            submitBtn.textContent = 'Simpan Jadwal';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = 'Simpan Jadwal';
    });
}


function viewSchedule(schedule) {
    currentScheduleId = schedule.id;
    
    const detail = `
        <div class="detail-grid">
            <div class="detail-item">
                <label>Tanggal</label>
                <p><strong>${schedule.schedule_date}</strong></p>
            </div>
            <div class="detail-item">
                <label>Karyawan</label>
                <p><strong>[${schedule.nik}] ${schedule.full_name}</strong></p>
            </div>
            <div class="detail-item">
                <label>Shift</label>
                <p><span class="shift-badge" style="background: ${schedule.color_code}">${schedule.shift_name}</span></p>
            </div>
            <div class="detail-item">
                <label>Jam Kerja</label>
                <p>${schedule.start_time.substring(0,5)} - ${schedule.end_time.substring(0,5)}</p>
            </div>
            ${schedule.notes ? `
            <div class="detail-item">
                <label>Catatan</label>
                <p>${schedule.notes}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('scheduleDetail').innerHTML = detail;
    document.getElementById('viewScheduleModal').style.display = 'block';
}

function closeViewScheduleModal() {
    document.getElementById('viewScheduleModal').style.display = 'none';
    currentScheduleId = null;
}

function deleteSchedule() {
    if (!currentScheduleId) return;
    
    if (!confirm('Yakin hapus jadwal ini?')) return;
    
    fetch('delete_schedule.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: currentScheduleId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Jadwal berhasil dihapus');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
