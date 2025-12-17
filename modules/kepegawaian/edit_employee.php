<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Edit Karyawan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: employees.php');
    exit();
}

$error = '';
$success = '';

// Get employee data
$stmt = $db->prepare("SELECT * FROM employees WHERE id = :id");
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: employees.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_code = sanitize($_POST['employee_code'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $birth_date = sanitize($_POST['birth_date'] ?? '');
    $birth_place = sanitize($_POST['birth_place'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0);
    $position_id = (int)($_POST['position_id'] ?? 0);
    $branch_id = (int)($_POST['branch_id'] ?? 0);
    $employment_status = sanitize($_POST['employment_status'] ?? 'kontrak');
    $join_date = sanitize($_POST['join_date'] ?? '');
    $end_date = sanitize($_POST['end_date'] ?? '');
    
    // Handle photo upload
    $photo = $employee['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['photo'], '../../uploads/employees/', ['jpg', 'jpeg', 'png'], 2000000);
        if ($upload['success']) {
            // Delete old photo
            if (!empty($employee['photo']) && file_exists('../../uploads/employees/' . $employee['photo'])) {
                unlink('../../uploads/employees/' . $employee['photo']);
            }
            $photo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $query = "UPDATE employees SET
                      employee_code = :code,
                      full_name = :name,
                      email = :email,
                      phone = :phone,
                      gender = :gender,
                      birth_date = :birth_date,
                      birth_place = :birth_place,
                      address = :address,
                      department_id = :dept,
                      position_id = :position,
                      branch_id = :branch,
                      employment_status = :status,
                      join_date = :join_date,
                      end_date = :end_date,
                      photo = :photo,
                      updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                'code' => $employee_code,
                'name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'gender' => $gender,
                'birth_date' => $birth_date ?: null,
                'birth_place' => $birth_place,
                'address' => $address,
                'dept' => $department_id ?: null,
                'position' => $position_id ?: null,
                'branch' => $branch_id ?: null,
                'status' => $employment_status,
                'join_date' => $join_date,
                'end_date' => $end_date ?: null,
                'photo' => $photo,
                'id' => $id
            ]);
            
            $success = 'Data karyawan berhasil diupdate';
            
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM employees WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'NIK atau Email sudah digunakan';
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get options
$departments = $db->query("SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$positions = $db->query("SELECT id, position_name FROM positions WHERE is_active = 1 ORDER BY position_name")->fetchAll(PDO::FETCH_ASSOC);
$branches = $db->query("SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>✏️ Edit Karyawan</h1>
        <div>
            <a href="view_employee.php?id=<?php echo $id; ?>" class="btn" style="background: #3b82f6;">👁️ Lihat Detail</a>
            <a href="employees.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <!-- Data Pribadi -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Data Pribadi</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>NIK / Kode Karyawan *</label>
                        <input type="text" name="employee_code" class="form-control" required 
                               value="<?php echo htmlspecialchars($employee['employee_code'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="full_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($employee['full_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="gender" class="form-control" required>
                            <option value="">Pilih</option>
                            <option value="male" <?php echo ($employee['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo ($employee['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="birth_place" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['birth_place'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['birth_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Foto Karyawan</label>
                    <?php if (!empty($employee['photo']) && file_exists('../../uploads/employees/' . $employee['photo'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo BASE_URL; ?>uploads/employees/<?php echo htmlspecialchars($employee['photo']); ?>" 
                                 alt="Current Photo" 
                                 style="max-width: 150px; border-radius: 8px;">
                            <p style="color: #666; margin-top: 5px;">Foto saat ini</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <small class="text-muted">Format: JPG, PNG (Max 2MB). Kosongkan jika tidak ingin mengubah foto.</small>
                </div>
            </div>
        </div>
        
        <!-- Data Pekerjaan -->
        <div class="card">
            <div class="card-header">
                <h2>💼 Data Pekerjaan</h2>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Departemen</label>
                        <select name="department_id" class="form-control">
                            <option value="">Pilih Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="position_id" class="form-control">
                            <option value="">Pilih Jabatan</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo $pos['id']; ?>" <?php echo ($employee['position_id'] ?? '') == $pos['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cabang</label>
                        <select name="branch_id" class="form-control">
                            <option value="">Pilih Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo ($employee['branch_id'] ?? '') == $branch['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Status Kepegawaian *</label>
                        <select name="employment_status" class="form-control" required>
                            <option value="kontrak" <?php echo ($employee['employment_status'] ?? '') == 'kontrak' ? 'selected' : ''; ?>>Kontrak</option>
                            <option value="tetap" <?php echo ($employee['employment_status'] ?? '') == 'tetap' ? 'selected' : ''; ?>>Tetap</option>
                            <option value="probation" <?php echo ($employee['employment_status'] ?? '') == 'probation' ? 'selected' : ''; ?>>Probation</option>
                            <option value="magang" <?php echo ($employee['employment_status'] ?? '') == 'magang' ? 'selected' : ''; ?>>Magang</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Bergabung *</label>
                        <input type="date" name="join_date" class="form-control" required 
                               value="<?php echo htmlspecialchars($employee['join_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Berakhir Kontrak</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo htmlspecialchars($employee['end_date'] ?? ''); ?>">
                        <small class="text-muted">Kosongkan jika tidak ada</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Update Data</button>
            <a href="view_employee.php?id=<?php echo $id; ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<?php include '../../includes/footer.php'; ?>
