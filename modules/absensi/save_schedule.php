<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// Set header JSON sebelum cek role
header('Content-Type: application/json');

// Cek role
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Baca input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

$date = $input['date'] ?? '';
$employees = $input['employees'] ?? [];
$shift_id = (int)($input['shift_id'] ?? 0);
$notes = $input['notes'] ?? '';
$created_by = getUserId();

// Validasi data
if (empty($date) || empty($employees) || $shift_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

try {
    // Get first branch (atau bisa dibuat dynamic)
    $branchQuery = $db->query("SELECT id FROM branches WHERE is_active = 1 LIMIT 1");
    $branch = $branchQuery->fetch(PDO::FETCH_ASSOC);
    
    if (!$branch) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada cabang aktif']);
        exit();
    }
    
    $branch_id = $branch['id'];
    
    $db->beginTransaction();
    
    $query = "INSERT INTO employee_schedules (employee_id, shift_id, schedule_date, branch_id, notes, created_by)
              VALUES (:emp_id, :shift_id, :date, :branch_id, :notes, :created_by)
              ON DUPLICATE KEY UPDATE 
              shift_id = VALUES(shift_id), 
              notes = VALUES(notes),
              updated_at = NOW()";
    
    $stmt = $db->prepare($query);
    
    $successCount = 0;
    foreach ($employees as $emp_id) {
        $result = $stmt->execute([
            'emp_id' => (int)$emp_id,
            'shift_id' => $shift_id,
            'date' => $date,
            'branch_id' => $branch_id,
            'notes' => $notes,
            'created_by' => $created_by
        ]);
        
        if ($result) {
            $successCount++;
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "Berhasil menyimpan {$successCount} jadwal",
        'count' => $successCount
    ]);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

exit();
