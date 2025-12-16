<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Edit Karyawan';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    redirect(BASE_URL . 'modules/kepegawaian/index.php');
}

// Get employee data
$query = "SELECT * FROM employees WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    redirect(BASE_URL . 'modules/kepegawaian/index.php');
}

// Get data untuk dropdown
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$branches = $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

// Get positions untuk department yang dipilih
$positions = $db->query("SELECT * FROM positions WHERE department_id = {$employee['department_id']} AND is_active = 1 ORDER BY level_code")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = sanitize($_POST['nik']);
    $full_name = sanitize($_POST['full_name']);
    $ktp_number = sanitize($_POST['ktp_number']);
    $birth_place = sanitize($_POST['birth_place']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $department_id = (int)$_POST['department_id'];
    $position_id = (int)$_POST['position_id'];
    $branch_id = (int)$_POST['branch_id'];
    $employment_status = $_POST['employment_status'];
    $join_date = $_POST['join_date'];
    $contract_end_date = !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null;
    
    // Validasi NIK & KTP unique (kecuali untuk data sendiri)
    $checkQuery = "SELECT COUNT(*) as count FROM employees 
                   WHERE (nik = :nik OR ktp_number = :ktp) AND id != :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute(['nik' => $nik, 'ktp' => $ktp_number, 'id' => $id]);
    
    if ($checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
        $error = 'NIK atau Nomor KTP sudah digunakan karyawan lain';
    } else {
        try {
            $query = "UPDATE employees SET
                      nik = :nik,
                      full_name = :full_name,
                      ktp_number = :ktp_number,
                      birth_place = :birth_place,
                      birth_date = :birth_date,
                      gender = :gender,
                      address = :address,
                      phone = :phone,
                      email = :email,
                      department_id = :department_id,
                      position_id = :position_id,
                      branch_id = :branch_id,
                      employment_status = :employment_status,
                      join_date = :join_date,
                      contract_end_date = :contract_end_date,
                      updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                'nik' => $nik,
                'full_name' => $full_name,
                'ktp_number' => $ktp_number,
                'birth_place' => $birth_place,
                'birth_date' => $birth_date,
                'gender' => $gender,
                'address' => $address,
                'phone' => $phone,
                'email' => $email,
                'department_id' => $department_id,
                'position_id' => $position_id,
                'branch_id' => $branch_id,
                'employment_status' => $employment_status,
                'join_date' => $join_date,
                'contract_end_date' => $contract_end_date,
                'id' => $id
            ]);
            
            redirect(BASE_URL . 'modules/kepegawaian/index.php?success=edit');
            
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
        <h1>Edit Data Karyawan</h1>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <h3>Data Pribadi</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>NIK Karyawan *</label>
                        <input type="text" name="nik" class="form-control" required 
                               value="<?php echo htmlspecialchars($employee['nik']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nomor KTP *</label>
                        <input type="text" name="ktp_number" class="form-control" required 
                               maxlength="16" pattern="[0-9]{16}"
                               value="<?php echo htmlspecialchars($employee['ktp_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin *</label>
                        <select name="gender" class="form-control" required>
                            <option value="L" <?php echo $employee['gender'] === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo $employee['gender'] === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="birth_place" class="form-control"
                               value="<?php echo htmlspecialchars($employee['birth_place']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control"
                               value="<?php echo $employee['birth_date']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($employee['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($employee['email']); ?>">
                    </div>
                </div>
                
                <hr style="margin: 30px 0;">
                
                <h3>Data Kepegawaian</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Departemen *</label>
                        <select name="department_id" id="department_id" class="form-control" required>
                            <option value="">Pilih Departemen</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"
                                        <?php echo $dept['id'] == $employee['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jabatan *</label>
                        <select name="position_id" id="position_id" class="form-control" required>
                            <?php foreach($positions as $pos): ?>
                                <option value="<?php echo $pos['id']; ?>"
                                        <?php echo $pos['id'] == $employee['position_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos['position_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cabang *</label>
                        <select name="branch_id" class="form-control" required>
                            <option value="">Pilih Cabang</option>
                            <?php foreach($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>"
                                        <?php echo $branch['id'] == $employee['branch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status Kepegawaian *</label>
                        <select name="employment_status" class="form-control" required>
                            <option value="probation" <?php echo $employee['employment_status'] === 'probation' ? 'selected' : ''; ?>>Probation</option>
                            <option value="kontrak" <?php echo $employee['employment_status'] === 'kontrak' ? 'selected' : ''; ?>>Kontrak</option>
                            <option value="tetap" <?php echo $employee['employment_status'] === 'tetap' ? 'selected' : ''; ?>>Tetap</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Bergabung *</label>
                        <input type="date" name="join_date" class="form-control" required
                               value="<?php echo $employee['join_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Akhir Kontrak</label>
                        <input type="date" name="contract_end_date" class="form-control"
                               value="<?php echo $employee['contract_end_date']; ?>">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">Update Data</button>
                    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('department_id').addEventListener('change', function() {
    const deptId = this.value;
    const positionSelect = document.getElementById('position_id');
    
    if (!deptId) {
        positionSelect.innerHTML = '<option value="">Pilih Departemen dulu</option>';
        return;
    }
    
    fetch(`get_positions.php?department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            positionSelect.innerHTML = '<option value="">Pilih Jabatan</option>';
            data.forEach(pos => {
                const option = document.createElement('option');
                option.value = pos.id;
                option.textContent = pos.position_name;
                positionSelect.appendChild(option);
            });
        });
});
</script>

<?php include '../../includes/footer.php'; ?>
