<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

if ($department_id > 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, position_name FROM positions 
              WHERE department_id = :dept_id AND is_active = 1 
              ORDER BY level_code, position_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute(['dept_id' => $department_id]);
    
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($positions);
} else {
    echo json_encode([]);
}
