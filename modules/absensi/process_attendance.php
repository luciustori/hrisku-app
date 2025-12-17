<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? '';
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;
$employee_id = getEmployeeId();
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID tidak ditemukan']);
    exit();
}

// Get schedule
$scheduleQuery = "SELECT es.*, s.start_time, s.end_time, s.is_mod, s.mod_value,
                  b.latitude as branch_lat, b.longitude as branch_lng, b.radius_meter
                  FROM employee_schedules es
                  JOIN shifts s ON es.shift_id = s.id
                  JOIN branches b ON es.branch_id = b.id
                  WHERE es.employee_id = :emp_id AND es.schedule_date = :date";
$stmt = $db->prepare($scheduleQuery);
$stmt->execute(['emp_id' => $employee_id, 'date' => $today]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada jadwal untuk hari ini']);
    exit();
}

// Calculate distance (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meter
    
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

// Validate location
$distance = calculateDistance($latitude, $longitude, $schedule['branch_lat'], $schedule['branch_lng']);

if ($distance > $schedule['radius_meter']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Anda di luar radius lokasi kantor (' . round($distance) . 'm dari kantor). Radius maksimal: ' . $schedule['radius_meter'] . 'm'
    ]);
    exit();
}

try {
    if ($action == 'checkin') {
        // Check if already checked in
        $checkQuery = "SELECT * FROM attendance WHERE employee_id = :emp_id AND attendance_date = :date";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute(['emp_id' => $employee_id, 'date' => $today]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah check in hari ini']);
            exit();
        }
        
        // Calculate late
        $shiftStart = strtotime($today . ' ' . $schedule['start_time']);
        $checkInTime = strtotime($now);
        $lateDuration = 0;
        $status = 'hadir';
        
        if ($checkInTime > $shiftStart) {
            $lateDuration = round(($checkInTime - $shiftStart) / 60);
            $status = 'terlambat';
        }
        
        // Insert check in
        $query = "INSERT INTO attendance 
                  (employee_id, schedule_id, attendance_date, check_in_time, 
                   check_in_latitude, check_in_longitude, status, late_duration)
                  VALUES 
                  (:emp_id, :schedule_id, :date, :time, :lat, :lng, :status, :late)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'emp_id' => $employee_id,
            'schedule_id' => $schedule['id'],
            'date' => $today,
            'time' => $now,
            'lat' => $latitude,
            'lng' => $longitude,
            'status' => $status,
            'late' => $lateDuration
        ]);
        
        $message = $status == 'terlambat' 
            ? "Check In berhasil (Terlambat {$lateDuration} menit)" 
            : "Check In berhasil";
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } elseif ($action == 'checkout') {
        // Get today's attendance
        $checkQuery = "SELECT * FROM attendance WHERE employee_id = :emp_id AND attendance_date = :date";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute(['emp_id' => $employee_id, 'date' => $today]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance) {
            echo json_encode(['success' => false, 'message' => 'Anda belum check in']);
            exit();
        }
        
        if ($attendance['check_out_time']) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah check out']);
            exit();
        }
        
        // Calculate work duration
        $checkInTime = strtotime($attendance['check_in_time']);
        $checkOutTime = strtotime($now);
        $workDuration = round(($checkOutTime - $checkInTime) / 60);
        
        // Calculate overtime
        $shiftEnd = strtotime($today . ' ' . $schedule['end_time']);
        $overtimeDuration = 0;
        
        if ($checkOutTime > $shiftEnd) {
            $overtimeDuration = round(($checkOutTime - $shiftEnd) / 60);
        }
        
        // Update check out
        $query = "UPDATE attendance SET
                  check_out_time = :time,
                  check_out_latitude = :lat,
                  check_out_longitude = :lng,
                  work_duration = :work,
                  overtime_duration = :overtime,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'time' => $now,
            'lat' => $latitude,
            'lng' => $longitude,
            'work' => $workDuration,
            'overtime' => $overtimeDuration,
            'id' => $attendance['id']
        ]);
        
        $message = "Check Out berhasil. Durasi kerja: " . floor($workDuration / 60) . " jam " . ($workDuration % 60) . " menit";
        
        if ($overtimeDuration > 0) {
            $message .= " (Lembur: {$overtimeDuration} menit)";
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
                                                                                                                                                                                                              