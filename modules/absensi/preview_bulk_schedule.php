<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

checkRole(['super_admin', 'admin']);

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);

$start_date = $input['start_date'];
$end_date = $input['end_date'];
$department_id = (int)$input['department_id'];
$shift_id = (int)$input['shift_id'];
$work_days = $input['work_days'];

try {
    // Get department
    $dept = $db->prepare("SELECT department_name FROM departments WHERE id = :id");
    $dept->execute(['id' => $department_id]);
    $department = $dept->fetch(PDO::FETCH_ASSOC);
    
    // Get shift
    $shiftQ = $db->prepare("SELECT shift_name FROM shifts WHERE id = :id");
    $shiftQ->execute(['id' => $shift_id]);
    $shift = $shiftQ->fetch(PDO::FETCH_ASSOC);
    
    // Get employee count
    $empCount = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE department_id = :dept_id AND is_active = 1");
    $empCount->execute(['dept_id' => $department_id]);
    $employeeCount = $empCount->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Generate dates
    $dates = [];
    $current = strtotime($start_date);
    $end = strtotime($end_date);
    
    while ($current <= $end) {
        $dayOfWeek = date('w', $current);
        
        $include = false;
        if ($work_days == 'senin-jumat' && $dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $include = true;
        } elseif ($work_days == 'senin-sabtu' && $dayOfWeek >= 1 && $dayOfWeek <= 6) {
            $include = true;
        } elseif ($work_days == 'senin-minggu') {
            $include = true;
        }
        
        $dateStr = date('Y-m-d', $current);
        
        // Check holiday
        $holidayCheck = $db->prepare("SELECT id FROM holidays WHERE holiday_date = :date");
        $holidayCheck->execute(['date' => $dateStr]);
        $isHoliday = $holidayCheck->fetch();
        
        if ($include && !$isHoliday) {
            $dates[] = date('d M Y', $current);
        }
        
        $current = strtotime('+1 day', $current);
    }
    
    $workDaysText = [
        'senin-jumat' => 'Senin - Jumat',
        'senin-sabtu' => 'Senin - Sabtu',
        'senin-minggu' => 'Senin - Minggu'
    ];
    
    echo json_encode([
        'success' => true,
        'department_name' => $department['department_name'],
        'employee_count' => $employeeCount,
        'shift_name' => $shift['shift_name'],
        'start_date' => date('d M Y', strtotime($start_date)),
        'end_date' => date('d M Y', strtotime($end_date)),
        'work_days_text' => $workDaysText[$work_days],
        'work_day_count' => count($dates),
        'total_schedules' => $employeeCount * count($dates),
        'dates' => $dates
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
