<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Data Karyawan';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = (int)$_POST['id'];
    
    try {
        // Soft delete
        $stmt = $db->prepare("UPDATE employees SET is_active = 0, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $success = 'Karyawan berhasil dinonaktifkan';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$status_filter = $_GET['status'] ?? '';
$branch_filter = $_GET['branch'] ?? '';

// Build query
$query = "SELECT e.*, 
          d.department_name, 
          p.position_name, 
          b.branch_name
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN positions p ON e.position_id = p.id
          LEFT JOIN branches b ON e.branch_id = b.id
          WHERE e.is_active = 1";

$params = [];

if (!empty($search)) {
    $query .= " AND (e.full_name LIKE :search OR e.employee_code LIKE :search OR e.email LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($department_filter)) {
    $query .= " AND e.department_id = :dept";
    $params['dept'] = $department_filter;
}

if (!empty($status_filter)) {
    $query .= " AND e.employment_status = :status";
    $params['status'] = $status_filter;
}

if (!empty($branch_filter)) {
    $query .= " AND e.branch_id = :branch";
    $params['branch'] = $branch_filter;
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options
$departments = $db->query("SELECT id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$branches = $db->query("SELECT id, branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👥 Data Karyawan</h1>
        <div>
            <a href="add_employee.php" class="btn btn-primary">+ Tambah Karyawan</a>
            <a href="index.php" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="card">
        <div class="card-header">
            <h2>🔍 Filter & Pencarian</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Cari Karyawan</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama, NIK, atau Email" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Departemen</label>
                        <select name="department" class="form-control">
                            <option value="">Semua Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="tetap" <?php echo $status_filter == 'tetap' ? 'selected' : ''; ?>>Tetap</option>
                            <option value="kontrak" <?php echo $status_filter == 'kontrak' ? 'selected' : ''; ?>>Kontrak</option>
                            <option value="probation" <?php echo $status_filter == 'probation' ? 'selected' : ''; ?>>Probation</option>
                            <option value="magang" <?php echo $status_filter == 'magang' ? 'selected' : ''; ?>>Magang</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cabang</label>
                        <select name="branch" class="form-control">
                            <option value="">Semua Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['id']; ?>" <?php echo $branch_filter == $branch['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['branch_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">🔍 Cari</button>
                    <a href="employees.php" class="btn btn-secondary">Reset</a>
                    <button type="button" onclick="window.print()" class="btn" style="background: #10b981;">🖨️ Print</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Employees List -->
    <div class="card">
        <div class="card-header">
            <h2>Daftar Karyawan (<?php echo count($employees); ?> orang)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>NIK / Nama</th>
                        <th>Departemen / Jabatan</th>
                        <th>Cabang</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <?php if (!empty($emp['photo']) && file_exists('../../uploads/employees/' . $emp['photo'])): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/employees/<?php echo htmlspecialchars($emp['photo']); ?>" 
                                         alt="Photo" 
                                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                                        <?php echo $emp['gender'] == 'male' ? '👨' : '👩'; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['full_name'] ?? ''); ?></strong><br>
                                <small style="color: #666;">NIK: <?php echo htmlspecialchars($emp['employee_code'] ?? ''); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['position_name'] ?? '-'); ?></strong><br>
                                <small><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($emp['branch_name'] ?? '-'); ?>
                            </td>
                            <td>
                                <small>
                                    <?php if (!empty($emp['phone'])): ?>
                                        📞 <?php echo htmlspecialchars($emp['phone']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($emp['email'])): ?>
                                        📧 <?php echo htmlspecialchars($emp['email']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'tetap' => 'badge-tetap',
                                    'kontrak' => 'badge-kontrak',
                                    'probation' => 'badge-probation',
                                    'magang' => 'badge-magang'
                                ];
                                $status = $emp['employment_status'] ?? 'kontrak';
                                ?>
                                <span class="badge <?php echo $statusClass[$status] ?? 'badge-kontrak'; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_employee.php?id=<?php echo $emp['id']; ?>" class="btn-action" style="background: #3b82f6;">Detail</a>
                                <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin nonaktifkan karyawan ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $emp['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">Nonaktifkan</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <p style="color: #999; margin-bottom: 20px;">
                                    <?php if (!empty($search) || !empty($department_filter) || !empty($status_filter) || !empty($branch_filter)): ?>
                                        Tidak ada karyawan yang sesuai dengan filter
                                    <?php else: ?>
                                        Belum ada data karyawan
                                    <?php endif; ?>
                                </p>
                                <a href="add_employee.php" class="btn btn-primary">+ Tambah Karyawan Pertama</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .page-header, .btn, .btn-action, .card-header h2, form, .alert {
        display: none !important;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    table {
        font-size: 12px;
    }
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.form-control {
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
</style>

<?php include '../../includes/footer.php'; ?>
