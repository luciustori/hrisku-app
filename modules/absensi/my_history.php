<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole();

$page_title = 'Riwayat Absensi Saya';

$database = new Database();
$db = $database->getConnection();

$employee_id = getEmployeeId();

if (!$employee_id) {
    header('Location: checkin.php');
    exit();
}

// Get month/year filter
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$firstDay = date('Y-m-01', strtotime("$year-$month-01"));
$lastDay = date('Y-m-t', strtotime("$year-$month-01"));

// Get attendance history
$query = "SELECT a.*, es.schedule_date, s.shift_name, s.start_time, s.end_time, b.branch_name
          FROM attendance a
          JOIN employee_schedules es ON a.schedule_id = es.id
          JOIN shifts s ON es.shift_id = s.id
          JOIN branches b ON es.branch_id = b.id
          WHERE a.employee_id = :emp_id 
          AND a.attendance_date BETWEEN :start AND :end
          ORDER BY a.attendance_date DESC";

$stmt = $db->prepare($query);
$stmt->execute(['emp_id' => $employee_id, 'start' => $firstDay, 'end' => $lastDay]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary
$totalPresent = count($history);
$totalLate = count(array_filter($history, fn($h) => $h['status'] == 'terlambat'));
$totalLateMinutes = array_sum(array_column($history, 'late_duration'));
$totalOvertimeMinutes = array_sum(array_column($history, 'overtime_duration'));

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
        <h1>📜 Riwayat Absensi Saya</h1>
        <div>
            <a href="checkin.php" class="btn btn-primary">📍 Absensi Hari Ini</a>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card">
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="month" class="form-control">
                            <?php foreach ($months as $m => $name): ?>
                                <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun</label>
                        <select name="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 1; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="stat-card">
            <h3>Total Hadir</h3>
            <div class="stat-number"><?php echo $totalPresent; ?></div>
            <div class="stat-label">Hari</div>
        </div>
        <div class="stat-card">
            <h3>Terlambat</h3>
            <div class="stat-number" style="color: #f59e0b;"><?php echo $totalLate; ?></div>
            <div class="stat-label">Kali</div>
        </div>
        <div class="stat-card">
            <h3>Total Keterlambatan</h3>
            <div class="stat-number" style="color: #ef4444;"><?php echo $totalLateMinutes; ?></div>
            <div class="stat-label">Menit</div>
        </div>
        <div class="stat-card">
            <h3>Total Lembur</h3>
            <div class="stat-number" style="color: #10b981;"><?php echo $totalOvertimeMinutes; ?></div>
            <div class="stat-label">Menit</div>
        </div>
    </div>
    
    <!-- History Table -->
    <div class="card">
        <div class="card-header">
            <h2>Riwayat <?php echo $months[$month]; ?> <?php echo $year; ?></h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Shift</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) > 0): ?>
                        <?php foreach ($history as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></strong><br>
                                <small class="text-muted">
                                    <?php 
                                    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                    echo $days[date('w', strtotime($row['attendance_date']))];
                                    ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($row['shift_name']); ?></td>
                            <td>
                                <strong><?php echo date('H:i', strtotime($row['check_in_time'])); ?></strong><br>
                                <small class="text-muted">Target: <?php echo date('H:i', strtotime($row['start_time'])); ?></small>
                            </td>
                            <td>
                                <?php if ($row['check_out_time']): ?>
                                    <strong><?php echo date('H:i', strtotime($row['check_out_time'])); ?></strong><br>
                                    <small class="text-muted">Target: <?php echo date('H:i', strtotime($row['end_time'])); ?></small>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Check Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['work_duration']): ?>
                                    <?php echo floor($row['work_duration'] / 60); ?>j <?php echo $row['work_duration'] % 60; ?>m
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'hadir'): ?>
                                    <span class="badge badge-tetap">✓ Hadir</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">⚠ Terlambat <?php echo $row['late_duration']; ?>m</span>
                                <?php endif; ?>
                                
                                <?php if ($row['overtime_duration'] > 0): ?>
                                    <br><span class="badge" style="background: #10b981;">+ Lembur <?php echo $row['overtime_duration']; ?>m</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Tidak ada data absensi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
