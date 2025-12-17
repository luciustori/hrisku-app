<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Master Shift';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $shift_name = sanitize($_POST['shift_name']);
        $shift_code = sanitize($_POST['shift_code']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $work_days = sanitize($_POST['work_days']);
        $is_mod = isset($_POST['is_mod']) ? 1 : 0;
        $mod_value = $is_mod ? (float)$_POST['mod_value'] : 0;
        $color_code = sanitize($_POST['color_code']);
        
        try {
            $query = "INSERT INTO shifts (shift_name, shift_code, start_time, end_time, work_days, is_mod, mod_value, color_code) 
                      VALUES (:name, :code, :start, :end, :days, :mod, :mod_val, :color)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                'name' => $shift_name,
                'code' => $shift_code,
                'start' => $start_time,
                'end' => $end_time,
                'days' => $work_days,
                'mod' => $is_mod,
                'mod_val' => $mod_value,
                'color' => $color_code
            ]);
            $success = 'Shift berhasil ditambahkan';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $shift_name = sanitize($_POST['shift_name']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $work_days = sanitize($_POST['work_days']);
        $is_mod = isset($_POST['is_mod']) ? 1 : 0;
        $mod_value = $is_mod ? (float)$_POST['mod_value'] : 0;
        $color_code = sanitize($_POST['color_code']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $query = "UPDATE shifts SET 
                      shift_name = :name, 
                      start_time = :start, 
                      end_time = :end, 
                      work_days = :days, 
                      is_mod = :mod, 
                      mod_value = :mod_val, 
                      color_code = :color,
                      is_active = :active
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                'name' => $shift_name,
                'start' => $start_time,
                'end' => $end_time,
                'days' => $work_days,
                'mod' => $is_mod,
                'mod_val' => $mod_value,
                'color' => $color_code,
                'active' => $is_active,
                'id' => $id
            ]);
            $success = 'Shift berhasil diupdate';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all shifts
$shifts = $db->query("SELECT * FROM shifts ORDER BY is_mod, shift_code")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Master Shift Kerja</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Add Shift Form -->
    <div class="card">
        <div class="card-header">
            <h2>Tambah Shift Baru</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Shift *</label>
                        <input type="text" name="shift_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kode Shift *</label>
                        <input type="text" name="shift_code" class="form-control" required maxlength="20">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Jam Mulai *</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Selesai *</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Hari Kerja *</label>
                        <select name="work_days" class="form-control" required>
                            <option value="senin-jumat">Senin - Jumat</option>
                            <option value="senin-minggu">Senin - Minggu</option>
                            <option value="sabtu-minggu">Sabtu - Minggu</option>
                            <option value="sabtu-minggu-libur">Sabtu, Minggu & Libur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Warna Badge</label>
                        <input type="color" name="color_code" class="form-control" value="#667eea">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_mod" id="is_mod_add" onchange="toggleModValue('add')">
                        Piket MOD (Manager On Duty)
                    </label>
                </div>
                
                <div class="form-group" id="mod_value_add" style="display: none;">
                    <label>Nilai MOD (Rp)</label>
                    <input type="number" name="mod_value" class="form-control" value="100000" step="1000">
                </div>
                
                <button type="submit" class="btn btn-primary">Tambah Shift</button>
            </form>
        </div>
    </div>
    
    <!-- Shift List -->
    <div class="card">
        <div class="card-header">
            <h2>Daftar Shift</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Shift</th>
                        <th>Jam Kerja</th>
                        <th>Hari</th>
                        <th>MOD</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($shifts as $shift): ?>
                    <tr>
                        <td>
                            <span class="shift-badge" style="background: <?php echo $shift['color_code']; ?>">
                                <?php echo htmlspecialchars($shift['shift_code']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($shift['shift_name']); ?></strong></td>
                        <td><?php echo date('H:i', strtotime($shift['start_time'])); ?> - <?php echo date('H:i', strtotime($shift['end_time'])); ?></td>
                        <td><?php echo ucwords(str_replace('-', ' ', $shift['work_days'])); ?></td>
                        <td>
                            <?php if($shift['is_mod']): ?>
                                <span class="badge" style="background: #ef4444;">
                                    <?php echo formatRupiah($shift['mod_value']); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($shift['is_active']): ?>
                                <span class="badge badge-tetap">Aktif</span>
                            <?php else: ?>
                                <span class="badge" style="background: #ccc;">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-action btn-edit" onclick="editShift(<?php echo htmlspecialchars(json_encode($shift)); ?>)">
                                Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Shift</h2>
        <form method="POST" action="" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label>Nama Shift *</label>
                <input type="text" name="shift_name" id="edit_shift_name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Jam Mulai *</label>
                    <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Jam Selesai *</label>
                    <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Hari Kerja *</label>
                    <select name="work_days" id="edit_work_days" class="form-control" required>
                        <option value="senin-jumat">Senin - Jumat</option>
                        <option value="senin-minggu">Senin - Minggu</option>
                        <option value="sabtu-minggu">Sabtu - Minggu</option>
                        <option value="sabtu-minggu-libur">Sabtu, Minggu & Libur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Warna Badge</label>
                    <input type="color" name="color_code" id="edit_color_code" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_mod" id="edit_is_mod" onchange="toggleModValue('edit')">
                    Piket MOD
                </label>
            </div>
            
            <div class="form-group" id="mod_value_edit" style="display: none;">
                <label>Nilai MOD (Rp)</label>
                <input type="number" name="mod_value" id="edit_mod_value" class="form-control" step="1000">
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" id="edit_is_active" checked>
                    Shift Aktif
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Shift</button>
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function toggleModValue(type) {
    const checkbox = document.getElementById('is_mod_' + type);
    const modValueDiv = document.getElementById('mod_value_' + type);
    modValueDiv.style.display = checkbox.checked ? 'block' : 'none';
}

function editShift(shift) {
    document.getElementById('edit_id').value = shift.id;
    document.getElementById('edit_shift_name').value = shift.shift_name;
    document.getElementById('edit_start_time').value = shift.start_time;
    document.getElementById('edit_end_time').value = shift.end_time;
    document.getElementById('edit_work_days').value = shift.work_days;
    document.getElementById('edit_color_code').value = shift.color_code;
    document.getElementById('edit_is_mod').checked = shift.is_mod == 1;
    document.getElementById('edit_mod_value').value = shift.mod_value;
    document.getElementById('edit_is_active').checked = shift.is_active == 1;
    
    toggleModValue('edit');
    
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
