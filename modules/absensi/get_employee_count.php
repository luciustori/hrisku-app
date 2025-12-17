<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

checkRole(['super_admin', 'admin']);

$database = new Database();
$db = $database->getConnection();

$department_id = (int)$_GET['department_id'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM employees WHERE department_id = :dept_id AND is_active = 1");
$stmt->execute(['dept_id' => $department_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['count' => $result['count']]);
