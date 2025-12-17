<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)$input['id'];

try {
    $stmt = $db->prepare("DELETE FROM employee_schedules WHERE id = :id");
    $stmt->execute(['id' => $id]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
