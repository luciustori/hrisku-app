<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin']);

$page_title = 'Link User ke Employee';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $employee_id = (int)$_POST['employee_id'];
    
    try {
        $stmt = $db->prepare("UPDATE users SET employee_id = :emp_id WHERE id = :user_id");
        $stmt->execute(['emp_id' => $employee_id, 'user_id' => $user_id]);
        $success = 'User berhasil dihubungkan dengan employee';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get users without employee link
$users = $db->query("SELECT * FROM users WHERE employee_id IS NULL ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

// Get employees without user link
$employees = $db->query("SELECT e.* FROM employees e 
                         LEFT JOIN users u ON e.id = u.employee_id 
                         WHERE u.id IS NULL AND e.is_active = 1
                         ORDER BY e.full_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Hubungkan User dengan Employee</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Link User ke Employee</h2>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <p class="text-muted">Semua user sudah terhubung dengan employee</p>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>User Account *</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Pilih User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?> 
                                    (<?php echo ucfirst($user['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Employee *</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Pilih Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    [<?php echo htmlspecialchars($emp['nik']); ?>] 
                                    <?php echo htmlspecialchars($emp['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Hubungkan</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Current Links -->
    <div class="card">
        <div class="card-header">
            <h2>User yang Sudah Terhubung</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>NIK</th>
                        <th>Nama Employee</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $linked = $db->query("SELECT u.*, e.nik, e.full_name 
                                          FROM users u 
                                          JOIN employees e ON u.employee_id = e.id 
                                          ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (count($linked) > 0): ?>
                        <?php foreach ($linked as $link): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($link['username']); ?></td>
                            <td><span class="badge badge-<?php echo $link['role']; ?>"><?php echo ucfirst($link['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($link['nik']); ?></td>
                            <td><strong><?php echo htmlspecialchars($link['full_name']); ?></strong></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $link['id']; ?>">
                                    <input type="hidden" name="employee_id" value="">
                                    <button type="submit" class="btn-action btn-delete" onclick="return confirm('Lepas hubungan?')">
                                        Unlink
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Belum ada user yang terhubung</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
