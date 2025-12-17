<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(); // Semua role bisa akses

$page_title = 'Absensi';

$database = new Database();
$db = $database->getConnection();

$employee_id = getEmployeeId();

// Cek apakah user memiliki employee_id
if (!$employee_id) {
    include '../../includes/header.php';
    include '../../includes/navbar.php';
    ?>
    <div class="container">
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                <h3>Akun Belum Terhubung dengan Data Karyawan</h3>
                <p class="text-muted">Silakan hubungi Administrator untuk menghubungkan akun Anda dengan data karyawan.</p>
                <a href="../../modules/dashboard/index.php" class="btn btn-primary" style="margin-top: 20px;">← Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit();
}

$today = date('Y-m-d');

// Cek apakah sudah ada absensi hari ini
$checkQuery = "SELECT * FROM attendance WHERE employee_id = :emp_id AND attendance_date = :date";
$stmt = $db->prepare($checkQuery);
$stmt->execute(['emp_id' => $employee_id, 'date' => $today]);
$todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Get jadwal hari ini
$scheduleQuery = "SELECT es.*, s.shift_name, s.start_time, s.end_time, s.is_mod, s.mod_value,
                  b.branch_name, b.latitude as branch_lat, b.longitude as branch_lng, b.radius_meter
                  FROM employee_schedules es
                  JOIN shifts s ON es.shift_id = s.id
                  JOIN branches b ON es.branch_id = b.id
                  WHERE es.employee_id = :emp_id AND es.schedule_date = :date";
$stmt = $db->prepare($scheduleQuery);
$stmt->execute(['emp_id' => $employee_id, 'date' => $today]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

// Get employee info
$empQuery = "SELECT e.*, d.department_name, p.position_name 
             FROM employees e
             JOIN departments d ON e.department_id = d.id
             JOIN positions p ON e.position_id = p.id
             WHERE e.id = :id";
$stmt = $db->prepare($empQuery);
$stmt->execute(['id' => $employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    include '../../includes/header.php';
    include '../../includes/navbar.php';
    ?>
    <div class="container">
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                <h3>Data Karyawan Tidak Ditemukan</h3>
                <p class="text-muted">Silakan hubungi Administrator.</p>
            </div>
        </div>
    </div>
    <?php
    include '../../includes/footer.php';
    exit();
}

// Cek hari libur
$holidayQuery = "SELECT * FROM holidays WHERE holiday_date = :date";
$stmt = $db->prepare($holidayQuery);
$stmt->execute(['date' => $today]);
$isHoliday = $stmt->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Absensi Hari Ini</h1>
        <div class="current-time" id="currentTime"></div>
    </div>
    
    <!-- Employee Info Card -->
    <div class="attendance-card">
        <div class="employee-info-section">
            <div class="employee-photo">
                <?php if($employee['photo'] && file_exists('../../uploads/employees/' . $employee['photo'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/employees/' . $employee['photo']; ?>" alt="Photo">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span><?php echo strtoupper(substr($employee['full_name'], 0, 2)); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="employee-details">
                <h2><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                <p class="employee-meta">
                    <span class="badge badge-tetap"><?php echo htmlspecialchars($employee['nik']); ?></span>
                    <span><?php echo htmlspecialchars($employee['position_name']); ?></span>
                    <span><?php echo htmlspecialchars($employee['department_name']); ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <?php if ($isHoliday): ?>
        <!-- Holiday Notice -->
        <div class="alert alert-warning">
            <strong>🏖️ Hari Libur:</strong> <?php echo htmlspecialchars($isHoliday['holiday_name']); ?>
            <?php if ($schedule && $schedule['is_mod']): ?>
                <br><strong>Piket MOD Aktif</strong> - Nilai: <?php echo formatRupiah($schedule['mod_value']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($schedule): ?>
        <!-- Schedule Info -->
        <div class="card">
            <div class="card-header">
                <h3>Jadwal Shift Hari Ini</h3>
            </div>
            <div class="card-body">
                <div class="schedule-info-grid">
                    <div class="info-item">
                        <label>Shift</label>
                        <p><strong><?php echo htmlspecialchars($schedule['shift_name']); ?></strong></p>
                    </div>
                    <div class="info-item">
                        <label>Jam Kerja</label>
                        <p><?php echo date('H:i', strtotime($schedule['start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['end_time'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Lokasi</label>
                        <p><?php echo htmlspecialchars($schedule['branch_name']); ?></p>
                    </div>
                    <?php if ($schedule['is_mod']): ?>
                    <div class="info-item">
                        <label>Piket MOD</label>
                        <p><span class="badge" style="background: #ef4444;"><?php echo formatRupiah($schedule['mod_value']); ?></span></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Attendance Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Absensi</h3>
            </div>
            <div class="card-body">
                <?php if (!$todayAttendance): ?>
                    <!-- Check In -->
                    <div class="attendance-section">
                        <div class="attendance-icon checkin">📍</div>
                        <h3>Check In</h3>
                        <p class="text-muted">Mulai shift kerja Anda</p>
                        <button class="btn btn-primary btn-lg" onclick="checkIn()">
                            Check In Sekarang
                        </button>
                    </div>
                <?php elseif ($todayAttendance && !$todayAttendance['check_out_time']): ?>
                    <!-- Already Checked In -->
                    <div class="attendance-status">
                        <div class="status-badge checkin-badge">
                            <span class="status-icon">✓</span>
                            <span class="status-text">Sudah Check In</span>
                        </div>
                        <div class="attendance-time">
                            <strong><?php echo date('H:i', strtotime($todayAttendance['check_in_time'])); ?></strong>
                        </div>
                        <?php if ($todayAttendance['status'] == 'terlambat'): ?>
                            <div class="alert alert-warning">
                                Terlambat <?php echo $todayAttendance['late_duration']; ?> menit
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr style="margin: 30px 0;">
                    
                    <!-- Check Out -->
                    <div class="attendance-section">
                        <div class="attendance-icon checkout">🏠</div>
                        <h3>Check Out</h3>
                        <p class="text-muted">Akhiri shift kerja Anda</p>
                        <button class="btn btn-danger btn-lg" onclick="checkOut()">
                            Check Out Sekarang
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Already Completed -->
                    <div class="attendance-completed">
                        <div class="completion-icon">✓</div>
                        <h3>Absensi Hari Ini Selesai</h3>
                        <div class="attendance-summary">
                            <div class="summary-item">
                                <label>Check In</label>
                                <strong><?php echo date('H:i', strtotime($todayAttendance['check_in_time'])); ?></strong>
                            </div>
                            <div class="summary-item">
                                <label>Check Out</label>
                                <strong><?php echo date('H:i', strtotime($todayAttendance['check_out_time'])); ?></strong>
                            </div>
                            <div class="summary-item">
                                <label>Durasi Kerja</label>
                                <strong><?php echo floor($todayAttendance['work_duration'] / 60); ?> jam <?php echo $todayAttendance['work_duration'] % 60; ?> menit</strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Location Info -->
        <div class="card">
            <div class="card-header">
                <h3>📍 Lokasi Absensi</h3>
            </div>
            <div class="card-body">
                <p><strong><?php echo htmlspecialchars($schedule['branch_name']); ?></strong></p>
                <p class="text-muted">Radius: <?php echo $schedule['radius_meter']; ?> meter</p>
                <div id="map" style="width: 100%; height: 300px; border-radius: 8px; margin-top: 15px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                    <div style="text-align: center; color: #666;">
                        <p><strong>Koordinat Cabang:</strong></p>
                        <p>Lat: <?php echo $schedule['branch_lat']; ?>, Lng: <?php echo $schedule['branch_lng']; ?></p>
                        <p><small>Radius: <?php echo $schedule['radius_meter']; ?>m</small></p>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- No Schedule -->
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <div style="font-size: 48px; margin-bottom: 20px;">📅</div>
                <h3>Tidak Ada Jadwal Shift</h3>
                <p class="text-muted">Anda tidak memiliki jadwal shift untuk hari ini</p>
            </div>
        </div>
    <?php endif; ?>
</div>

</div>

<!-- Hidden data for JavaScript -->
<div id="scheduleData" 
     data-schedule-id="<?php echo $schedule['id'] ?? ''; ?>"
     data-branch-lat="<?php echo $schedule['branch_lat'] ?? ''; ?>"
     data-branch-lng="<?php echo $schedule['branch_lng'] ?? ''; ?>"
     data-radius="<?php echo $schedule['radius_meter'] ?? ''; ?>"
     data-branch-name="<?php echo htmlspecialchars($schedule['branch_name'] ?? ''); ?>"
     style="display: none;">
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map;
let branchMarker;
let userMarker;
let circle;

// Update current time
function updateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const timeEl = document.getElementById('currentTime');
    if (timeEl) {
        timeEl.textContent = now.toLocaleDateString('id-ID', options);
    }
}

updateTime();
setInterval(updateTime, 1000);

// Initialize Leaflet Map
<?php if ($schedule): ?>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleData = document.getElementById('scheduleData');
    const branchLat = parseFloat(scheduleData.dataset.branchLat);
    const branchLng = parseFloat(scheduleData.dataset.branchLng);
    const radius = parseInt(scheduleData.dataset.radius);
    const branchName = scheduleData.dataset.branchName;
    
    // Initialize map centered on branch
    map = L.map('map').setView([branchLat, branchLng], 17);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Add branch marker (blue)
    const branchIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    branchMarker = L.marker([branchLat, branchLng], {icon: branchIcon})
        .addTo(map)
        .bindPopup(`
            <div style="text-align: center;">
                <strong>🏢 ${branchName}</strong><br>
                <small>Radius: ${radius} meter</small>
            </div>
        `);
    
    // Add radius circle
    circle = L.circle([branchLat, branchLng], {
        color: '#667eea',
        fillColor: '#667eea',
        fillOpacity: 0.2,
        radius: radius
    }).addTo(map);
    
    // Get user location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                
                // Add user marker (red)
                const userIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });
                
                userMarker = L.marker([userLat, userLng], {icon: userIcon})
                    .addTo(map);
                
                // Calculate distance (Haversine)
                const R = 6371000; // Earth radius in meters
                const dLat = (userLat - branchLat) * Math.PI / 180;
                const dLng = (userLng - branchLng) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(branchLat * Math.PI / 180) * Math.cos(userLat * Math.PI / 180) *
                          Math.sin(dLng/2) * Math.sin(dLng/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                const distance = R * c;
                
                const distanceText = distance > 1000 
                    ? (distance / 1000).toFixed(2) + ' km'
                    : Math.round(distance) + ' meter';
                
                const isInRadius = distance <= radius;
                const statusText = isInRadius 
                    ? '<span style="color: #10b981;">✓ Dalam Radius</span>'
                    : '<span style="color: #ef4444;">✗ Di Luar Radius</span>';
                
                userMarker.bindPopup(`
                    <div style="text-align: center;">
                        <strong>📍 Lokasi Anda</strong><br>
                        Jarak: ${distanceText}<br>
                        ${statusText}
                    </div>
                `).openPopup();
                
                // Fit map to show both markers
                const bounds = L.latLngBounds([
                    [branchLat, branchLng],
                    [userLat, userLng]
                ]);
                map.fitBounds(bounds, { padding: [50, 50] });
            },
            function(error) {
                console.error('Geolocation error:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }
});
<?php endif; ?>

// Check In function
function checkIn() {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung Geolocation');
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Mengambil lokasi...';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const data = {
                action: 'checkin',
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            
            fetch('process_attendance.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Check In Sekarang';
                }
            })
            .catch(error => {
                alert('Error: ' + error);
                btn.disabled = false;
                btn.textContent = 'Check In Sekarang';
            });
        },
        function(error) {
            alert('Gagal mendapatkan lokasi: ' + error.message);
            btn.disabled = false;
            btn.textContent = 'Check In Sekarang';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

// Check Out function
function checkOut() {
    if (!confirm('Yakin ingin Check Out sekarang?')) return;
    
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung Geolocation');
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Mengambil lokasi...';
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const data = {
                action: 'checkout',
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
            
            fetch('process_attendance.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                    btn.disabled = false;
                    btn.textContent = 'Check Out Sekarang';
                }
            })
            .catch(error => {
                alert('Error: ' + error);
                btn.disabled = false;
                btn.textContent = 'Check Out Sekarang';
            });
        },
        function(error) {
            alert('Gagal mendapatkan lokasi: ' + error.message);
            btn.disabled = false;
            btn.textContent = 'Check Out Sekarang';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
</script>

<?php include '../../includes/footer.php'; ?>
