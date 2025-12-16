<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    redirect(BASE_URL . 'modules/kepegawaian/index.php');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Soft delete: set is_active = 0
    $query = "UPDATE employees SET is_active = 0, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $id]);
    
    redirect(BASE_URL . 'modules/kepegawaian/index.php?success=delete');
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
