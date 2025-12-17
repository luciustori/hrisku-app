<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$database = new Database();
$db = $database->getConnection();

// Get filters
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$department_id = isset($_GET['department']) ? (int)$_GET['department'] : 0;

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
          ORDER BY d.department_name, e.full_name";

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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Laporan_Absensi_' . $months[$month] . '_' . $year . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Output Excel content
echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #667eea; color: white; font-weight: bold; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .subheader { font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">LAPORAN ABSENSI KARYAWAN</div>
    <div class="subheader">
        Periode: <?php echo $months[$month]; ?> <?php echo $year; ?><br>
        Dicetak: <?php echo date('d/m/Y H:i:s'); ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Lengkap</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th>Jadwal (Hari)</th>
                <th>Hadir (Hari)</th>
                <th>Terlambat (x)</th>
                <th>Total Menit Terlambat</th>
                <th>Lembur (Menit)</th>
                <th>Total MOD</th>
                <th>Persentase Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($reportData as $row): 
                $attendanceRate = $row['scheduled_days'] > 0 
                    ? round(($row['present_days'] / $row['scheduled_days']) * 100, 1) 
                    : 0;
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nik']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                <td style="text-align: center;"><?php echo $row['scheduled_days']; ?></td>
                <td style="text-align: center;"><?php echo $row['present_days']; ?></td>
                <td style="text-align: center;"><?php echo $row['late_count']; ?></td>
                <td style="text-align: center;"><?php echo $row['total_late_minutes'] ?: 0; ?></td>
                <td style="text-align: center;"><?php echo $row['total_overtime_minutes'] ?: 0; ?></td>
                <td style="text-align: right;">Rp <?php echo number_format($row['total_mod_value'] ?: 0, 0, ',', '.'); ?></td>
                <td style="text-align: center;"><?php echo $attendanceRate; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    <div style="margin-top: 50px;">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;"></td>
                <td style="border: none; width: 50%; text-align: center;">
                    Jakarta, <?php echo date('d F Y'); ?><br><br><br><br><br>
                    <strong>HRD Manager</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
