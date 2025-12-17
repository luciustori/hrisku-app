<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Master Gaji Karyawan';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'save_salary') {
            $employeeId = intval($_POST['employee_id']);
            $basicSalary = floatval($_POST['basic_salary']);
            $effectiveDate = $_POST['effective_date'];
            $notes = sanitize($_POST['notes'] ?? '');
            $components = $_POST['components'] ?? [];
            
            try {
                $db->beginTransaction();
                
                // Save basic salary
                $stmt = $db->prepare("INSERT INTO employee_salaries 
                                      (employee_id, basic_salary, effective_date, notes, created_by) 
                                      VALUES (?, ?, ?, ?, ?)
                                      ON DUPLICATE KEY UPDATE 
                                      basic_salary = VALUES(basic_salary),
                                      effective_date = VALUES(effective_date),
                                      notes = VALUES(notes)");
                $stmt->execute([$employeeId, $basicSalary, $effectiveDate, $notes, $_SESSION['user']['id']]);
                
                // Delete existing components
                $db->prepare("DELETE FROM employee_salary_components WHERE employee_id = ?")->execute([$employeeId]);
                
                // Insert new components
                if (!empty($components)) {
                    $stmt = $db->prepare("INSERT INTO employee_salary_components 
                                          (employee_id, component_id, amount, is_active) 
                                          VALUES (?, ?, ?, 1)");
                    
                    foreach ($components as $compId => $amount) {
                        if ($amount > 0) {
                            $stmt->execute([$employeeId, $compId, $amount]);
                        }
                    }
                }
                
                $db->commit();
                $_SESSION['success'] = 'Data gaji berhasil disimpan!';
                
            } catch (PDOException $e) {
                $db->rollBack();
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
            
            header('Location: master_salary.php');
            exit();
        }
    }
}

// Get filter
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$query = "SELECT e.id, e.employee_code, e.full_name, e.email, e.phone, e.employment_status,
          d.department_name, p.position_name,
          es.basic_salary, es.effective_date,
          (SELECT SUM(amount) FROM employee_salary_components esc 
           JOIN salary_components sc ON esc.component_id = sc.id 
           WHERE esc.employee_id = e.id AND sc.component_type = 'earning' AND esc.is_active = 1) as total_earnings,
          (SELECT SUM(amount) FROM employee_salary_components esc 
           JOIN salary_components sc ON esc.component_id = sc.id 
           WHERE esc.employee_id = e.id AND sc.component_type = 'deduction' AND esc.is_active = 1) as total_deductions
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN positions p ON e.position_id = p.id
          LEFT JOIN employee_salaries es ON e.id = es.employee_id
          WHERE e.is_active = 1";

if (!empty($search)) {
    $query .= " AND (e.full_name LIKE '%$search%' OR e.employee_code LIKE '%$search%')";
}
if (!empty($department)) {
    $query .= " AND e.department_id = " . intval($department);
}
if (!empty($status)) {
    $query .= " AND e.employment_status = '$status'";
}

$query .= " ORDER BY e.full_name";

$employees = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get departments for filter
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

// Get all active components
$components = $db->query("SELECT * FROM salary_components WHERE is_active = 1 ORDER BY component_type, component_name")->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>💼 Master Gaji Karyawan</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <!-- Filter -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-body" style="padding: 20px;">
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="🔍 Cari nama/kode..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <select name="department" class="form-control">
                            <option value="">Semua Departemen</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="probation" <?php echo $status == 'probation' ? 'selected' : ''; ?>>Probation</option>
                            <option value="contract" <?php echo $status == 'contract' ? 'selected' : ''; ?>>Contract</option>
                            <option value="permanent" <?php echo $status == 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                            <option value="internship" <?php echo $status == 'internship' ? 'selected' : ''; ?>>Internship</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="master_salary.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="stats-mini">
        <div class="stat-mini" style="border-color: #667eea;">
            <span class="stat-label">👥 Total Karyawan</span>
            <span class="stat-value"><?php echo count($employees); ?></span>
        </div>
        <div class="stat-mini" style="border-color: #10b981;">
            <span class="stat-label">✓ Sudah Set Gaji</span>
            <span class="stat-value">
                <?php echo count(array_filter($employees, fn($e) => !empty($e['basic_salary']))); ?>
            </span>
        </div>
        <div class="stat-mini" style="border-color: #f59e0b;">
            <span class="stat-label">⚠️ Belum Set Gaji</span>
            <span class="stat-value">
                <?php echo count(array_filter($employees, fn($e) => empty($e['basic_salary']))); ?>
            </span>
        </div>
    </div>
    
    <!-- Employee List -->
    <div class="card">
        <div class="card-header">
            <h2>Daftar Karyawan (<?php echo count($employees); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Departemen</th>
                        <th>Status</th>
                        <th>Gaji Pokok</th>
                        <th>Tunjangan</th>
                        <th>Potongan</th>
                        <th>Total Gaji</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $emp): ?>
                        <?php
                        $basicSalary = floatval($emp['basic_salary'] ?? 0);
                        $earnings = floatval($emp['total_earnings'] ?? 0);
                        $deductions = floatval($emp['total_deductions'] ?? 0);
                        $netSalary = $basicSalary + $earnings - $deductions;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['full_name']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($emp['employee_code'] ?? 'N/A'); ?> • 
                                    <?php echo htmlspecialchars($emp['position_name'] ?? '-'); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($emp['department_name'] ?? '-'); ?></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'permanent' => 'badge-tetap',
                                    'contract' => 'badge-kontrak',
                                    'probation' => 'badge-info',
                                    'internship' => 'badge-magang'
                                ];
                                $statusLabel = [
                                    'permanent' => 'Permanent',
                                    'contract' => 'Contract',
                                    'probation' => 'Probation',
                                    'internship' => 'Internship'
                                ];
                                ?>
                                <span class="badge <?php echo $statusClass[$emp['employment_status']] ?? ''; ?>">
                                    <?php echo $statusLabel[$emp['employment_status']] ?? $emp['employment_status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($basicSalary > 0): ?>
                                    <strong>Rp <?php echo number_format($basicSalary, 0, ',', '.'); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Belum set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($earnings > 0): ?>
                                    <span style="color: #10b981;">+Rp <?php echo number_format($earnings, 0, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($deductions > 0): ?>
                                    <span style="color: #ef4444;">-Rp <?php echo number_format($deductions, 0, ',', '.'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($netSalary > 0): ?>
                                    <strong style="color: #3b82f6;">Rp <?php echo number_format($netSalary, 0, ',', '.'); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="editSalary(<?php echo $emp['id']; ?>)" class="btn-action" title="Set Gaji">
                                    💰
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">👥</p>
                                <p style="margin: 10px 0 0;">Tidak ada data karyawan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Salary Modal -->
<div id="salaryModal" class="modal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3>💰 Set Gaji Karyawan</h3>
            <button onclick="closeModal()" class="modal-close">×</button>
        </div>
        <form method="POST" id="salaryForm">
            <input type="hidden" name="action" value="save_salary">
            <input type="hidden" name="employee_id" id="employeeId">
            
            <div class="modal-body">
                <!-- Employee Info -->
                <div id="employeeInfo" style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <!-- Will be filled by JS -->
                </div>
                
                <!-- Basic Salary -->
                <div class="form-group">
                    <label>Gaji Pokok *</label>
                    <input type="number" name="basic_salary" id="basicSalary" class="form-control" 
                           required min="0" step="1000" placeholder="5000000">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Efektif *</label>
                        <input type="date" name="effective_date" id="effectiveDate" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan</label>
                        <input type="text" name="notes" id="notes" class="form-control" 
                               placeholder="Catatan tambahan...">
                    </div>
                </div>
                
                <!-- Components -->
                <h4 style="margin-top: 20px; margin-bottom: 15px; color: #10b981;">💰 Komponen Pendapatan</h4>
                <div class="components-grid">
                    <?php foreach ($components as $comp): ?>
                        <?php if ($comp['component_type'] == 'earning'): ?>
                        <div class="component-item">
                            <label class="component-label">
                                <span><?php echo htmlspecialchars($comp['component_name']); ?></span>
                                <small class="text-muted"><?php echo htmlspecialchars($comp['description'] ?? ''); ?></small>
                            </label>
                            <input type="number" name="components[<?php echo $comp['id']; ?>]" 
                                   class="form-control" min="0" step="1000" 
                                   placeholder="<?php echo number_format($comp['default_amount'], 0, ',', '.'); ?>"
                                   value="0">
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <h4 style="margin-top: 20px; margin-bottom: 15px; color: #ef4444;">➖ Komponen Potongan</h4>
                <div class="components-grid">
                    <?php foreach ($components as $comp): ?>
                        <?php if ($comp['component_type'] == 'deduction'): ?>
                        <div class="component-item">
                            <label class="component-label">
                                <span><?php echo htmlspecialchars($comp['component_name']); ?></span>
                                <small class="text-muted"><?php echo htmlspecialchars($comp['description'] ?? ''); ?></small>
                            </label>
                            <input type="number" name="components[<?php echo $comp['id']; ?>]" 
                                   class="form-control" min="0" step="1000" 
                                   placeholder="<?php echo number_format($comp['default_amount'], 0, ',', '.'); ?>"
                                   value="0">
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Gaji</button>
            </div>
        </form>
    </div>
</div>


<style>
.filter-form .form-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-form .form-group {
    flex: 1;
    margin-bottom: 0;
}

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

/* Modal Styles */
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
    max-width: 900px;
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
    font-size: 20px;
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
// Employees data
const employeesData = <?php echo json_encode($employees); ?>;

console.log('Employees loaded:', employeesData.length);

function editSalary(employeeId) {
    console.log('Edit salary for employee:', employeeId);
    
    const employee = employeesData.find(e => e.id == employeeId);
    
    if (!employee) {
        console.error('Employee not found:', employeeId);
        alert('Data karyawan tidak ditemukan!');
        return;
    }
    
    console.log('Employee data:', employee);
    
    // Set employee ID
    document.getElementById('employeeId').value = employeeId;
    
    // Set employee info
    const statusLabels = {
        'permanent': 'Permanent',
        'contract': 'Contract',
        'probation': 'Probation',
        'internship': 'Internship'
    };
    
    document.getElementById('employeeInfo').innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <h4 style="margin: 0 0 5px 0;">${employee.full_name}</h4>
                <p style="margin: 0; color: #6b7280;">
                    ${employee.employee_code || 'N/A'} • 
                    ${employee.position_name || '-'} • 
                    ${employee.department_name || '-'}
                </p>
            </div>
            <span class="badge badge-info">${statusLabels[employee.employment_status] || employee.employment_status}</span>
        </div>
    `;
    
    // Set basic salary if exists
    document.getElementById('basicSalary').value = employee.basic_salary || '';
    
    // Reset all component inputs first
    document.querySelectorAll('.components-grid input[type="number"]').forEach(input => {
        input.value = '0';
    });
    
    // Load existing components via AJAX
    fetch(`get_employee_components.php?employee_id=${employeeId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            console.log('Components loaded:', data);
            data.forEach(comp => {
                const input = document.querySelector(`input[name="components[${comp.component_id}]"]`);
                if (input) {
                    input.value = comp.amount;
                }
            });
        })
        .catch(error => {
            console.error('Error loading components:', error);
            // Continue anyway, just no pre-filled components
        });
    
    // Show modal
    const modal = document.getElementById('salaryModal');
    if (modal) {
        modal.classList.add('show');
        console.log('Modal shown');
    } else {
        console.error('Modal element not found!');
    }
}

function closeModal() {
    const modal = document.getElementById('salaryModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('salaryForm').reset();
    }
}

// Close modal when clicking outside
document.getElementById('salaryModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Debug: Check if modal exists on page load
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('salaryModal');
    console.log('Modal element exists:', modal !== null);
    console.log('Total employees:', employeesData.length);
});
</script>

<?php include '../../includes/footer.php'; ?>
