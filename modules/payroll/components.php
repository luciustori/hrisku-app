<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Komponen Gaji';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $name = sanitize($_POST['component_name']);
            $type = $_POST['component_type'];
            $isFixed = isset($_POST['is_fixed']) ? 1 : 0;
            $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;
            $calculationMethod = $_POST['calculation_method'];
            $defaultAmount = floatval($_POST['default_amount'] ?? 0);
            $description = sanitize($_POST['description'] ?? '');
            
            try {
                $stmt = $db->prepare("INSERT INTO salary_components 
                                      (component_name, component_type, is_fixed, is_taxable, 
                                       calculation_method, default_amount, description) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $type, $isFixed, $isTaxable, $calculationMethod, $defaultAmount, $description]);
                $_SESSION['success'] = 'Komponen gaji berhasil ditambahkan!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'edit') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['component_name']);
            $type = $_POST['component_type'];
            $isFixed = isset($_POST['is_fixed']) ? 1 : 0;
            $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;
            $calculationMethod = $_POST['calculation_method'];
            $defaultAmount = floatval($_POST['default_amount'] ?? 0);
            $description = sanitize($_POST['description'] ?? '');
            
            try {
                $stmt = $db->prepare("UPDATE salary_components SET 
                                      component_name = ?, component_type = ?, is_fixed = ?, 
                                      is_taxable = ?, calculation_method = ?, default_amount = ?, 
                                      description = ? WHERE id = ?");
                $stmt->execute([$name, $type, $isFixed, $isTaxable, $calculationMethod, $defaultAmount, $description, $id]);
                $_SESSION['success'] = 'Komponen gaji berhasil diupdate!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'delete') {
            $id = intval($_POST['id']);
            
            try {
                $stmt = $db->prepare("DELETE FROM salary_components WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Komponen gaji berhasil dihapus!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
        } elseif ($action == 'toggle') {
            $id = intval($_POST['id']);
            
            try {
                $stmt = $db->prepare("UPDATE salary_components SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Status komponen berhasil diubah!';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
        }
        
        header('Location: components.php');
        exit();
    }
}

// Get all components
$components = $db->query("SELECT * FROM salary_components ORDER BY component_type, component_name")->fetchAll(PDO::FETCH_ASSOC);

// Separate by type
$earnings = array_filter($components, fn($c) => $c['component_type'] == 'earning');
$deductions = array_filter($components, fn($c) => $c['component_type'] == 'deduction');

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Komponen Gaji</h1>
        <div>
            <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Komponen</button>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-mini">
        <div class="stat-mini" style="border-color: #10b981;">
            <span class="stat-label">💰 Pendapatan</span>
            <span class="stat-value"><?php echo count($earnings); ?></span>
        </div>
        <div class="stat-mini" style="border-color: #ef4444;">
            <span class="stat-label">➖ Potongan</span>
            <span class="stat-value"><?php echo count($deductions); ?></span>
        </div>
        <div class="stat-mini" style="border-color: #3b82f6;">
            <span class="stat-label">📊 Total</span>
            <span class="stat-value"><?php echo count($components); ?></span>
        </div>
    </div>
    
    <!-- Earnings -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
            <h2>💰 Komponen Pendapatan</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Komponen</th>
                        <th>Tipe</th>
                        <th>Metode</th>
                        <th>Default Amount</th>
                        <th>Kena Pajak</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($earnings) > 0): ?>
                        <?php foreach ($earnings as $comp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($comp['component_name']); ?></strong>
                                <?php if (!empty($comp['description'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($comp['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $comp['is_fixed'] ? '#6366f1' : '#f59e0b'; ?>;">
                                    <?php echo $comp['is_fixed'] ? 'Fixed' : 'Variable'; ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($comp['calculation_method']); ?></td>
                            <td>Rp <?php echo number_format($comp['default_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($comp['is_taxable']): ?>
                                    <span class="badge badge-tetap">✓ Ya</span>
                                <?php else: ?>
                                    <span class="badge badge-kontrak">✗ Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                    <button type="submit" class="badge" style="border: none; cursor: pointer; <?php echo $comp['is_active'] ? 'background: #10b981;' : 'background: #9ca3af;'; ?>">
                                        <?php echo $comp['is_active'] ? '✓ Aktif' : '✗ Nonaktif'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button onclick='editComponent(<?php echo json_encode($comp); ?>)' class="btn-action" title="Edit">
                                    ✏️
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus komponen ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                Belum ada komponen pendapatan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Deductions -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
            <h2>➖ Komponen Potongan</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Komponen</th>
                        <th>Tipe</th>
                        <th>Metode</th>
                        <th>Default Amount</th>
                        <th>Kena Pajak</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deductions) > 0): ?>
                        <?php foreach ($deductions as $comp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($comp['component_name']); ?></strong>
                                <?php if (!empty($comp['description'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($comp['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $comp['is_fixed'] ? '#6366f1' : '#f59e0b'; ?>;">
                                    <?php echo $comp['is_fixed'] ? 'Fixed' : 'Variable'; ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($comp['calculation_method']); ?></td>
                            <td>Rp <?php echo number_format($comp['default_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <?php if ($comp['is_taxable']): ?>
                                    <span class="badge badge-tetap">✓ Ya</span>
                                <?php else: ?>
                                    <span class="badge badge-kontrak">✗ Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                    <button type="submit" class="badge" style="border: none; cursor: pointer; <?php echo $comp['is_active'] ? 'background: #10b981;' : 'background: #9ca3af;'; ?>">
                                        <?php echo $comp['is_active'] ? '✓ Aktif' : '✗ Nonaktif'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button onclick='editComponent(<?php echo json_encode($comp); ?>)' class="btn-action" title="Edit">
                                    ✏️
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus komponen ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $comp['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete" title="Hapus">
                                        🗑️
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                Belum ada komponen potongan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="componentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah Komponen Gaji</h3>
            <button onclick="closeModal()" class="modal-close">×</button>
        </div>
        <form method="POST" id="componentForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="componentId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Komponen *</label>
                    <input type="text" name="component_name" id="componentName" class="form-control" required placeholder="Contoh: Tunjangan Transport">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipe *</label>
                        <select name="component_type" id="componentType" class="form-control" required>
                            <option value="earning">💰 Pendapatan</option>
                            <option value="deduction">➖ Potongan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode Kalkulasi *</label>
                        <select name="calculation_method" id="calculationMethod" class="form-control" required>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                            <option value="formula">Formula</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Default Amount</label>
                    <input type="number" name="default_amount" id="defaultAmount" class="form-control" min="0" step="0.01" value="0" placeholder="0">
                    <small class="text-muted">Nominal default (bisa diubah per karyawan)</small>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Keterangan komponen gaji..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_fixed" id="isFixed" value="1" checked>
                            <span>Fixed Component</span>
                        </label>
                        <small class="text-muted">Komponen tetap tiap bulan</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_taxable" id="isTaxable" value="1" checked>
                            <span>Kena Pajak</span>
                        </label>
                        <small class="text-muted">Termasuk dalam perhitungan pajak</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<style>
.stats-mini {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-mini {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 8px;
    border-left: 4px solid;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
}

.components-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.component-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.component-label {
    display: flex;
    flex-direction: column;
    font-weight: 500;
    color: #374151;
}

.component-label small {
    font-weight: 400;
    margin-top: 2px;
}

.text-muted { 
    color: #6b7280; 
    font-size: 13px; 
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Modal Styles - IMPORTANT! */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 20px;
}

.modal.show { 
    display: flex !important; 
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    margin: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 32px;
    cursor: pointer;
    color: #9ca3af;
    line-height: 1;
    padding: 0;
    width: 32px;
    height: 32px;
}

.modal-close:hover { 
    color: #374151; 
}

.modal-body { 
    padding: 20px; 
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    position: sticky;
    bottom: 0;
    background: white;
    z-index: 10;
}
</style>

<script>
console.log('Components.php script loaded');

function showAddModal() {
    console.log('showAddModal called');
    
    const modal = document.getElementById('componentModal');
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }
    
    document.getElementById('modalTitle').textContent = 'Tambah Komponen Gaji';
    document.getElementById('formAction').value = 'add';
    document.getElementById('componentId').value = '';
    document.getElementById('componentForm').reset();
    
    modal.classList.add('show');
    console.log('Modal should be visible now');
}

function editComponent(comp) {
    console.log('editComponent called with:', comp);
    
    const modal = document.getElementById('componentModal');
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }
    
    document.getElementById('modalTitle').textContent = 'Edit Komponen Gaji';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('componentId').value = comp.id;
    document.getElementById('componentName').value = comp.component_name;
    document.getElementById('componentType').value = comp.component_type;
    document.getElementById('calculationMethod').value = comp.calculation_method;
    document.getElementById('defaultAmount').value = comp.default_amount;
    document.getElementById('description').value = comp.description || '';
    document.getElementById('isFixed').checked = comp.is_fixed == 1;
    document.getElementById('isTaxable').checked = comp.is_taxable == 1;
    
    modal.classList.add('show');
    console.log('Edit modal should be visible now');
}

function closeModal() {
    console.log('closeModal called');
    const modal = document.getElementById('componentModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('componentForm').reset();
    }
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    const modal = document.getElementById('componentModal');
    if (modal) {
        console.log('Modal element exists');
        
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    } else {
        console.error('Modal element not found on page load!');
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

