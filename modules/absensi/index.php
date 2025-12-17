<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'user']);

$page_title = 'Master Absensi';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT es.employee_id) as scheduled_today,
        COUNT(DISTINCT ar.employee_id) as present_today,
        (SELECT COUNT(*) FROM holidays WHERE holiday_date = CURDATE()) as is_holiday
    FROM employee_schedules es
    LEFT JOIN attendance_records ar ON es.employee_id = ar.employee_id AND ar.attendance_date = CURDATE()
    WHERE es.schedule_date = CURDATE()
";
$stmt = $db->query($statsQuery);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<link rel="stylesheet" href="../assets/css/absensi.css">

<div class="container">
    <div class="page-header">
        <h1>📋 Master Absensi</h1>
        <p>Kelola jadwal shift, absensi, dan hari libur karyawan</p>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?= $stats['scheduled_today'] ?? 0 ?></h3>
                <p>Jadwal Hari Ini</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h3><?= $stats['present_today'] ?? 0 ?></h3>
                <p>Sudah Absen</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏖️</div>
            <div class="stat-info">
                <h3><?= $stats['is_holiday'] ? 'Ya' : 'Tidak' ?></h3>
                <p>Hari Libur</p>
            </div>
        </div>
    </div>

    <div class="menu-grid">
        <a href="calendar.php" class="menu-card">
            <div class="icon">📅</div>
            <h3>Kalender Jadwal</h3>
            <p>Atur jadwal shift karyawan bulanan</p>
        </a>
        
        <a href="shifts.php" class="menu-card">
            <div class="icon">⏰</div>
            <h3>Kelola Shift</h3>
            <p>Pengaturan shift & jam kerja</p>
        </a>
        
        <a href="holidays.php" class="menu-card">
            <div class="icon">🏖️</div>
            <h3>Hari Libur</h3>
            <p>Libur nasional & cuti bersama</p>
        </a>
        
        <a href="attendance.php" class="menu-card">
            <div class="icon">📊</div>
            <h3>Laporan Absensi</h3>
            <p>Rekap kehadiran karyawan</p>
        </a>
        
        <a href="clock.php" class="menu-card">
            <div class="icon">🕐</div>
            <h3>Clock In/Out</h3>
            <p>Absensi harian karyawan</p>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
