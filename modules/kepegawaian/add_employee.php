<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Tambah Karyawan';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

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
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['photo'], '../../uploads/employees/', ['jpg', 'jpeg', 'png'], 2000000); // 2MB
        if ($upload['success']) {
            $photo = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $query = "INSERT INTO employees (
                        employee_code, full_name, email, phone, gender, 
                        birth_date, birth_place, address, 
                        department_id, position_id, branch_id,
                        employment_status, join_date, end_date, photo, is_active
                      ) VALUES (
                        :code, :name, :email, :phone, :gender,
                        :birth_date, :birth_place, :address,
                        :dept, :position, :branch,
                        :status, :join_date, :end_date, :photo, 1
                      )";
            
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
                'photo' => $photo
            ]);
            
            $success = 'Karyawan berhasil ditambahkan';
            header('Refresh: 2; URL=employees.php');
            
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
        <h1>➕ Tambah Karyawan Baru</h1>
        <a href="employees.php" class="btn btn-secondary">← Kembali</a>
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
                        <input type="text" name="employee_code" class="form-control" required placeholder="EMP001">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="John Doe">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com">
                    </div>
                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="08123456789">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="gender" class="form-control" required>
                            <option value="">Pilih</option>
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="birth_place" class="form-control" placeholder="Jakarta">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap karyawan"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Foto Karyawan</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <small class="text-muted">Format: JPG, PNG (Max 2MB)</small>
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
                                <option value="<?php echo $dept['id']; ?>">
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
                                <option value="<?php echo $pos['id']; ?>">
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
                                <option value="<?php echo $branch['id']; ?>">
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
                            <option value="kontrak">Kontrak</option>
                            <option value="tetap">Tetap</option>
                            <option value="probation">Probation</option>
                            <option value="magang">Magang</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Bergabung *</label>
                        <input type="date" name="join_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Berakhir Kontrak</label>
                        <input type="date" name="end_date" class="form-control">
                        <small class="text-muted">Kosongkan jika tidak ada</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Simpan Karyawan</button>
            <a href="employees.php" class="btn btn-secondary">Batal</a>
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
