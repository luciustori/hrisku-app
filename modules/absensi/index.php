<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Master Absensi';

$database = new Database();
$db = $database->getConnection();

// Get shifts
$shifts = $db->query("SELECT * FROM shifts ORDER BY is_mod, shift_code")->fetchAll(PDO::FETCH_ASSOC);

// Get holidays count
$holiday_count = $db->query("SELECT COUNT(*) as total FROM holidays WHERE YEAR(holiday_date) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC)['total'];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Master Absensi</h1>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Shift</h3>
            <div class="stat-number"><?php echo count($shifts); ?></div>
            <div class="stat-label">Shift Terdaftar</div>
        </div>
        
        <div class="stat-card">
            <h3>Libur Tahun Ini</h3>
            <div class="stat-number"><?php echo $holiday_count; ?></div>
            <div class="stat-label">Hari Libur</div>
        </div>
        
        <div class="stat-card">
            <h3>Jadwal Hari Ini</h3>
            <div class="stat-number">
                <?php 
                $today_schedule = $db->query("SELECT COUNT(*) as total FROM employee_schedules WHERE schedule_date = CURDATE()")->fetch(PDO::FETCH_ASSOC)['total'];
                echo $today_schedule;
                ?>
            </div>
            <div class="stat-label">Karyawan Dijadwalkan</div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="quick-links">
        <a href="calendar.php" class="quick-link-card">
            <div class="quick-link-icon">📅</div>
            <h3>Kalender Jadwal</h3>
            <p>Atur jadwal shift karyawan</p>
        </a>
        
        <a href="shifts.php" class="quick-link-card">
            <div class="quick-link-icon">⏰</div>
            <h3>Master Shift</h3>
            <p>Kelola shift kerja</p>
        </a>
        
        <a href="holidays.php" class="quick-link-card">
            <div class="quick-link-icon">🏖️</div>
            <h3>Hari Libur</h3>
            <p>Kelola libur nasional & cuti bersama</p>
        </a>
        
        <a href="report.php" class="quick-link-card">
            <div class="quick-link-icon">📊</div>
            <h3>Laporan Absensi</h3>
            <p>Lihat rekap absensi</p>
        </a>
    </div>
    
    <!-- Shift List -->
    <div class="card">
        <div class="card-header">
            <h2>Daftar Shift Kerja</h2>
            <a href="shifts.php" class="btn btn-primary btn-sm">Kelola Shift</a>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Shift</th>
                        <th>Jam Kerja</th>
                        <th>Hari Kerja</th>
                        <th>Tipe</th>
                        <th>Nilai MOD</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($shifts as $shift): ?>
                    <tr>
                        <td>
                            <span class="shift-badge" style="background: <?php echo $shift['color_code']; ?>">
                                <?php echo htmlspecialchars($shift['shift_code']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($shift['shift_name']); ?></strong></td>
                        <td><?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?></td>
                        <td><?php echo ucwords(str_replace('-', ' ', $shift['work_days'])); ?></td>
                        <td>
                            <?php if($shift['is_mod']): ?>
                                <span class="badge" style="background: #ef4444;">Piket MOD</span>
                            <?php else: ?>
                                <span class="badge badge-tetap">Regular</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $shift['is_mod'] ? formatRupiah($shift['mod_value']) : '-'; ?>
                        </td>
                        <td>
                            <?php if($shift['is_active']): ?>
                                <span class="badge badge-tetap">Aktif</span>
                            <?php else: ?>
                                <span class="badge" style="background: #ccc;">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
