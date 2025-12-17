<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit();
}

$employeeId = intval($_GET['employee_id'] ?? 0);

$database = new Database();
$db = $database->getConnection();

$components = $db->query("SELECT * FROM employee_salary_components 
                          WHERE employee_id = $employeeId AND is_active = 1")
                 ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($components);
