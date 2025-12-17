<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Laporan Absensi';

$database = new Database();
$db = $database->getConnection();

// Filter
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$department_id = isset($_GET['department']) ? (int)$_GET['department'] : 0;

// Get departments
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

// Build query
$firstDay = date('Y-m-01', strtotime("$year-$month-01"));
$lastDay = date('Y-m-t', strtotime("$year-$month-01"));

$whereClause = $department_id > 0 ? "AND e.department_id = :dept_id" : "";

$query = "SELECT e.id, e.nik, e.full_name, d.department_name, p.position_name,
          COUNT(DISTINCT es.schedule_date) as scheduled_days,
          COUNT(DISTINCT a.attendance_date) as present_days,
          SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as late_count,
          SUM(a.late_duration) as total_late_minutes,
          SUM(a.overtime_duration) as total_overtime_minutes,
          SUM(CASE WHEN s.is_mod = 1 THEN s.mod_value ELSE 0 END) as total_mod_value
          FROM employees e
          JOIN departments d ON e.department_id = d.id
          JOIN positions p ON e.position_id = p.id
          LEFT JOIN employee_schedules es ON e.id = es.employee_id 
              AND es.schedule_date BETWEEN :start AND :end
          LEFT JOIN attendance a ON e.id = a.employee_id 
              AND a.attendance_date BETWEEN :start AND :end
          LEFT JOIN employee_schedules es2 ON a.schedule_id = es2.id
          LEFT JOIN shifts s ON es2.shift_id = s.id
          WHERE e.is_active = 1 $whereClause
          GROUP BY e.id
          ORDER BY e.full_name";

$stmt = $db->prepare($query);
$params = ['start' => $firstDay, 'end' => $lastDay];
if ($department_id > 0) {
    $params['dept_id'] = $department_id;
}
$stmt->execute($params);
$reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1>Laporan Absensi</h1>
        <div>
            <button class="btn btn-success" onclick="exportToExcel()">📊 Export Excel</button>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <!-- Filters -->
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
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Departemen</label>
                        <select name="department" class="form-control">
                            <option value="0">Semua Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $dept['id'] == $department_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Title -->
    <div class="report-title">
        <h2>Laporan Absensi <?php echo $months[$month]; ?> <?php echo $year; ?></h2>
        <?php if ($department_id > 0): ?>
            <p>Departemen: <?php 
                $dept = array_filter($departments, fn($d) => $d['id'] == $department_id);
                echo htmlspecialchars(reset($dept)['department_name']); 
            ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Report Table -->
    <div class="table-container">
        <table class="table" id="reportTable">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Jadwal</th>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Menit Terlambat</th>
                    <th>Lembur (menit)</th>
                    <th>Total MOD</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reportData) > 0): ?>
                    <?php foreach ($reportData as $row): ?>
                        <?php 
                        $attendanceRate = $row['scheduled_days'] > 0 
                            ? round(($row['present_days'] / $row['scheduled_days']) * 100, 1) 
                            : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nik']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                            <td><?php echo $row['scheduled_days']; ?></td>
                            <td><?php echo $row['present_days']; ?></td>
                            <td>
                                <?php if ($row['late_count'] > 0): ?>
                                    <span class="badge" style="background: #f59e0b;"><?php echo $row['late_count']; ?>x</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['total_late_minutes'] ?: 0; ?></td>
                            <td><?php echo $row['total_overtime_minutes'] ?: 0; ?></td>
                            <td><strong><?php echo formatRupiah($row['total_mod_value'] ?: 0); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo $attendanceRate >= 90 ? 'tetap' : ($attendanceRate >= 75 ? 'kontrak' : 'probation'); ?>">
                                    <?php echo $attendanceRate; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align: center;">Tidak ada data</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToExcel() {
    // Simple CSV export
    const table = document.getElementById('reportTable');
    let csv = [];
    
    for (let row of table.rows) {
        let csvRow = [];
        for (let cell of row.cells) {
            csvRow.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        }
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'laporan_absensi_<?php echo $months[$month]; ?>_<?php echo $year; ?>.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../../includes/footer.php'; ?>

