<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

checkRole(['super_admin', 'admin', 'hrd']);

$database = new Database();
$db = $database->getConnection();
$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        // Approve overtime
        try {
            $stmt = $db->prepare("UPDATE overtime_requests 
                                  SET status = 'approved', 
                                      approved_by = :user_id, 
                                      approved_at = NOW() 
                                  WHERE id = :id");
            $stmt->execute([
                'user_id' => $user['id'],
                'id' => $id
            ]);
            
            $_SESSION['success'] = 'Lembur berhasil disetujui!';
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
    } elseif ($action == 'reject') {
        // Reject overtime
        $reason = sanitize($_POST['reason'] ?? 'Ditolak oleh admin');
        
        try {
            $stmt = $db->prepare("UPDATE overtime_requests 
                                  SET status = 'rejected', 
                                      approved_by = :user_id, 
                                      approved_at = NOW(),
                                      rejection_reason = :reason
                                  WHERE id = :id");
            $stmt->execute([
                'user_id' => $user['id'],
                'reason' => $reason,
                'id' => $id
            ]);
            
            $_SESSION['success'] = 'Lembur berhasil ditolak!';
            
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
    }
}

// Redirect back
$redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'modules/lembur/list_lembur.php';
header('Location: ' . $redirect);
exit();
?>
