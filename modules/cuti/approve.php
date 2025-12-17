<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// TEMPORARY
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $user = $_SESSION['user'];
    
    if ($action == 'approve') {
        // Approve
        $stmt = $db->prepare("UPDATE leave_requests SET status = 'approved', approved_by = :user_id, approved_at = NOW() WHERE id = :id");
        $stmt->execute(['user_id' => $user['id'], 'id' => $id]);
        
        // Update leave balance if annual
        $leave = $db->query("SELECT employee_id, leave_type, total_days FROM leave_requests WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
        if ($leave['leave_type'] == 'annual') {
            $db->query("UPDATE leave_balances SET annual_used = annual_used + {$leave['total_days']} WHERE employee_id = {$leave['employee_id']} AND year = YEAR(CURDATE())");
        }
        
        $_SESSION['success'] = 'Cuti berhasil disetujui';
        
    } elseif ($action == 'reject') {
        // Reject
        $reason = $_POST['reason'] ?? 'Ditolak oleh admin';
        $stmt = $db->prepare("UPDATE leave_requests SET status = 'rejected', approved_by = :user_id, approved_at = NOW(), rejection_reason = :reason WHERE id = :id");
        $stmt->execute(['user_id' => $user['id'], 'reason' => $reason, 'id' => $id]);
        
        $_SESSION['success'] = 'Cuti berhasil ditolak';
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'list_cuti.php'));
exit();
?>
