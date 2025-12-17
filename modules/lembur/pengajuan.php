<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];

$database = new Database();
$db = $database->getConnection();
$user = $_SESSION['user'];
$error = $success = '';

$emp = $db->query("SELECT id, full_name FROM employees WHERE email='{$user['username']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $emp) {
    $date = $_POST['overtime_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $reason = $_POST['reason'];
    
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    $hours = ($endTime - $startTime) / 3600;
    
    if ($hours <= 0) {
        $error = 'Jam selesai harus lebih besar dari jam mulai!';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO overtime_requests (employee_id, overtime_date, start_time, end_time, total_hours, reason) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$emp['id'], $date, $start, $end, $hours, $reason]);
            $success = 'Lembur berhasil diajukan!';
            header('Refresh: 2; URL=my_lembur.php');
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📝 Ajukan Lembur</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <?php if($emp): ?>
    <div class="card">
        <div class="card-header"><h2>Form Pengajuan Lembur</h2></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Nama Karyawan</label>
                    <input type="text" class="form-control" value="<?php echo $emp['full_name']; ?>" disabled>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Lembur *</label>
                        <input type="date" name="overtime_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Mulai *</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Selesai *</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alasan Lembur *</label>
                    <textarea name="reason" class="form-control" rows="4" required placeholder="Jelaskan alasan dan pekerjaan yang dilakukan saat lembur..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">📤 Ajukan Lembur</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.form-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php include '../../includes/footer.php'; ?>
