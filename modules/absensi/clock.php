<?php
require_once '../config/config.php';
require_once '../config/database.php';

checkRole(['super_admin', 'admin', 'user']);

$page_title = 'Clock In/Out';

$database = new Database();
$db = $database->getConnection();
$today = date('Y-m-d');

// Get today's attendance
$attQuery = "
    SELECT ar.*, es.shift_id, st.start_time, st.end_time, st.shift_name
    FROM attendance_records ar
    LEFT JOIN employee_schedules es ON ar.schedule_id = es.id
    LEFT JOIN shift_types st ON es.shift_id = st.id
    WHERE ar.employee_id = ? AND ar.attendance_date = ?
";
$stmt = $db->prepare($attQuery);
$stmt->execute([$_SESSION['employee_id'], $today]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get today's schedule
$schedQuery = "
    SELECT es.*, st.shift_name, st.start_time, st.end_time
    FROM employee_schedules es
    INNER JOIN shift_types st ON es.shift_id = st.id
    WHERE es.employee_id = ? AND es.schedule_date = ?
";
$stmt = $db->prepare($schedQuery);
$stmt->execute([$_SESSION['employee_id'], $today]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<link rel="stylesheet" href="../assets/css/absensi.css">

<div class="container">
    <div class="clock-container">
        <h1>⏰ Absensi Karyawan</h1>
        
        <div class="time-display" id="currentTime">00:00:00</div>
        <div class="date-display" id="currentDate"></div>
        
        <?php if ($schedule): ?>
            <div class="schedule-info">
                <p><strong>Jadwal Hari Ini:</strong> <?= $schedule['shift_name'] ?></p>
                <p>⏰ <?= date('H:i', strtotime($schedule['start_time'])) ?> - <?= date('H:i', strtotime($schedule['end_time'])) ?></p>
                <?php if ($schedule['is_mod']): ?>
                    <span class="badge-danger">🔴 Piket MOD</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                ⚠️ Anda tidak memiliki jadwal hari ini
            </div>
        <?php endif; ?>
        
        <div class="status-card">
            <?php if ($attendance): ?>
                <div class="attendance-status status-<?= $attendance['status'] ?>">
                    <h3>Status: <?= strtoupper($attendance['status']) ?></h3>
                    <div class="attendance-details">
                        <p><strong>Clock In:</strong> <?= $attendance['clock_in'] ? date('H:i:s', strtotime($attendance['clock_in'])) : '-' ?></p>
                        <p><strong>Clock Out:</strong> <?= $attendance['clock_out'] ? date('H:i:s', strtotime($attendance['clock_out'])) : '-' ?></p>
                        
                        <?php if ($attendance['late_duration_minutes'] > 0): ?>
                            <p class="late-info">⏱️ Terlambat: <?= $attendance['late_duration_minutes'] ?> menit</p>
                        <?php endif; ?>
                        
                        <?php if ($attendance['work_duration_minutes'] > 0): ?>
                            <p>⏰ Durasi Kerja: <?= floor($attendance['work_duration_minutes']/60) ?>j <?= $attendance['work_duration_minutes']%60 ?>m</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="no-attendance">Belum melakukan absensi hari ini</p>
            <?php endif; ?>
            
            <div class="location-info" id="locationInfo">
                📍 Mengambil lokasi Anda...
            </div>
            
            <div id="clockButtons">
                <?php if (!$attendance): ?>
                    <button class="btn btn-success btn-lg" onclick="clockIn()">
                        🟢 Clock In
                    </button>
                <?php elseif (!$attendance['clock_out']): ?>
                    <button class="btn btn-danger btn-lg" onclick="clockOut()">
                        🔴 Clock Out
                    </button>
                <?php else: ?>
                    <div class="complete-badge">
                        ✅ Absensi hari ini sudah lengkap
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/absensi-clock.js"></script>

<?php include '../includes/footer.php'; ?>
