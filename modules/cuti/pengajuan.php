<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

// TEMPORARY: Set dummy user untuk testing
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@hrisku.com',
        'role' => 'super_admin',
        'full_name' => 'Administrator'
    ];
}

checkRole(['super_admin', 'admin', 'hrd', 'karyawan']);

$page_title = 'Ajukan Cuti';

$database = new Database();
$db = $database->getConnection();

$user = $_SESSION['user'];
$error = '';
$success = '';

// Get employee data
$empQuery = "SELECT id, full_name, employee_code FROM employees WHERE email = :email OR employee_code = :code LIMIT 1";
$stmt = $db->prepare($empQuery);
$stmt->execute([
    'email' => $user['email'] ?? $user['username'],
    'code' => $user['username']
]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    $error = 'Data karyawan tidak ditemukan. Hubungi admin.';
}

// Get leave balance
$balance = null;
if ($employee) {
    $balanceQuery = "SELECT * FROM leave_balances WHERE employee_id = :emp_id AND year = YEAR(CURDATE())";
    $stmt = $db->prepare($balanceQuery);
    $stmt->execute(['emp_id' => $employee['id']]);
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$balance) {
        // Create balance
        $db->prepare("INSERT INTO leave_balances (employee_id, year, annual_quota) VALUES (:emp_id, YEAR(CURDATE()), 12)")->execute(['emp_id' => $employee['id']]);
        $stmt->execute(['emp_id' => $employee['id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $employee) {
    $leave_type = sanitize($_POST['leave_type'] ?? '');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    $reason = sanitize($_POST['reason'] ?? '');
    
    // Calculate total days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);
    $total_days = $diff->days + 1;
    
    // Check balance for annual leave
    if ($leave_type == 'annual') {
        $remaining = ($balance['annual_quota'] ?? 12) - ($balance['annual_used'] ?? 0);
        if ($total_days > $remaining) {
            $error = "Sisa cuti tahunan Anda hanya $remaining hari. Anda mengajukan $total_days hari.";
        }
    }
    
    // Handle attachment
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['attachment'], '../../uploads/leave/', ['jpg', 'jpeg', 'png', 'pdf'], 2000000);
        if ($upload['success']) {
            $attachment = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $query = "INSERT INTO leave_requests 
                      (employee_id, leave_type, start_date, end_date, total_days, reason, attachment, status)
                      VALUES (:emp_id, :type, :start, :end, :days, :reason, :attachment, 'pending')";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                'emp_id' => $employee['id'],
                'type' => $leave_type,
                'start' => $start_date,
                'end' => $end_date,
                'days' => $total_days,
                'reason' => $reason,
                'attachment' => $attachment
            ]);
            
            $success = 'Pengajuan cuti berhasil dikirim. Menunggu approval.';
            header('Refresh: 2; URL=my_cuti.php');
            
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📝 Ajukan Cuti</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($employee && $balance): ?>
    <!-- Leave Balance Info -->
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0 0 5px 0; color: white;">Saldo Cuti Tahunan</h3>
                    <p style="margin: 0; opacity: 0.9;">Tahun <?php echo date('Y'); ?></p>
                </div>
                <div style="text-align: right;">
                    <h2 style="margin: 0; font-size: 48px; color: white;">
                        <?php echo ($balance['annual_quota'] ?? 12) - ($balance['annual_used'] ?? 0); ?>
                    </h2>
                    <p style="margin: 0; opacity: 0.9;">dari <?php echo $balance['annual_quota'] ?? 12; ?> hari</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Pengajuan -->
    <div class="card">
        <div class="card-header">
            <h2>Form Pengajuan Cuti</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nama Karyawan</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['full_name']); ?>" disabled>
                    <small class="text-muted">NIK: <?php echo htmlspecialchars($employee['employee_code']); ?></small>
                </div>
                
                <div class="form-group">
                    <label>Jenis Cuti *</label>
                    <select name="leave_type" class="form-control" required>
                        <option value="">Pilih Jenis Cuti</option>
                        <option value="annual">🏖️ Cuti Tahunan</option>
                        <option value="sick">🤒 Cuti Sakit</option>
                        <option value="emergency">🚨 Cuti Darurat</option>
                        <option value="unpaid">💸 Cuti Tanpa Gaji</option>
                        <option value="maternity">🤱 Cuti Melahirkan</option>
                        <option value="paternity">👶 Cuti Ayah</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai *</label>
                        <input type="date" name="start_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               onchange="calculateDays()">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Selesai *</label>
                        <input type="date" name="end_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               onchange="calculateDays()">
                    </div>
                    <div class="form-group">
                        <label>Total Hari</label>
                        <input type="text" id="total_days" class="form-control" readonly 
                               style="background: #f3f4f6; font-weight: bold; font-size: 18px; text-align: center;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alasan Cuti *</label>
                    <textarea name="reason" class="form-control" rows="4" required 
                              placeholder="Jelaskan alasan pengajuan cuti..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Lampiran (Opsional)</label>
                    <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                    <small class="text-muted">
                        Upload surat dokter (untuk cuti sakit) atau dokumen pendukung lainnya. 
                        Format: JPG, PNG, PDF (Max 2MB)
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">📤 Ajukan Cuti</button>
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 150px;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.text-muted {
    color: #6b7280;
    font-size: 13px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function calculateDays() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = end - start;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        if (diffDays > 0) {
            document.getElementById('total_days').value = diffDays + ' hari';
        } else {
            document.getElementById('total_days').value = 'Invalid';
            alert('Tanggal selesai harus setelah tanggal mulai!');
        }
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
