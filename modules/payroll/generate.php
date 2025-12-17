<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Generate Payroll';

$database = new Database();
$db = $database->getConnection();

// Handle generate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'generate') {
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);
        
        try {
            $db->beginTransaction();
            
            // Check if payroll already exists
            $existing = $db->query("SELECT id FROM payrolls WHERE period_month = $month AND period_year = $year")->fetch();
            
            if ($existing) {
                throw new Exception('Payroll untuk periode ini sudah ada!');
            }
            
            // Create payroll header
            $stmt = $db->prepare("INSERT INTO payrolls (period_month, period_year, status, processed_by) 
                                  VALUES (?, ?, 'draft', ?)");
            $stmt->execute([$month, $year, $_SESSION['user']['id']]);
            $payrollId = $db->lastInsertId();
            
            // Get all active employees with salary
            $employees = $db->query("SELECT e.id, e.full_name, es.basic_salary
                                     FROM employees e
                                     LEFT JOIN employee_salaries es ON e.id = es.employee_id
                                     WHERE e.is_active = 1 AND es.basic_salary IS NOT NULL
                                     ORDER BY e.full_name")->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($employees)) {
                throw new Exception('Tidak ada karyawan dengan gaji yang sudah di-set!');
            }
            
            $totalEmployees = 0;
            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            
            // Process each employee
            foreach ($employees as $emp) {
                $empId = $emp['id'];
                $basicSalary = floatval($emp['basic_salary']);
                
                // Get earnings components
                $earnings = $db->query("SELECT SUM(amount) as total 
                                       FROM employee_salary_components esc
                                       JOIN salary_components sc ON esc.component_id = sc.id
                                       WHERE esc.employee_id = $empId 
                                       AND sc.component_type = 'earning'
                                       AND esc.is_active = 1")->fetchColumn();
                
                // Get deductions components
                $deductions = $db->query("SELECT SUM(amount) as total 
                                         FROM employee_salary_components esc
                                         JOIN salary_components sc ON esc.component_id = sc.id
                                         WHERE esc.employee_id = $empId 
                                         AND sc.component_type = 'deduction'
                                         AND esc.is_active = 1")->fetchColumn();
                
                // Get approved overtime for this month
                $overtimeData = $db->query("SELECT COALESCE(SUM(total_hours), 0) as hours
                                           FROM overtime_requests
                                           WHERE employee_id = $empId
                                           AND status = 'approved'
                                           AND MONTH(overtime_date) = $month
                                           AND YEAR(overtime_date) = $year")->fetch();
                
                $overtimeHours = floatval($overtimeData['hours'] ?? 0);
                $overtimePay = $overtimeHours * 50000; // Rp 50,000 per hour (configurable)
                
                // Calculate totals
                $totalEarnings = $basicSalary + floatval($earnings) + $overtimePay;
                $totalDeduction = floatval($deductions);
                $netSalary = $totalEarnings - $totalDeduction;
                
                // Insert payroll detail
                $stmt = $db->prepare("INSERT INTO payroll_details 
                                     (payroll_id, employee_id, basic_salary, total_earnings, 
                                      total_deductions, net_salary, overtime_hours, overtime_pay, days_worked) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([
                    $payrollId, $empId, $basicSalary, $totalEarnings,
                    $totalDeduction, $netSalary, $overtimeHours, $overtimePay
                ]);
                
                $detailId = $db->lastInsertId();
                
                // Insert component breakdown
                $components = $db->query("SELECT sc.id, sc.component_name, sc.component_type, esc.amount
                                         FROM employee_salary_components esc
                                         JOIN salary_components sc ON esc.component_id = sc.id
                                         WHERE esc.employee_id = $empId AND esc.is_active = 1")
                               ->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($components as $comp) {
                    $stmt = $db->prepare("INSERT INTO payroll_detail_components 
                                         (payroll_detail_id, component_id, component_name, component_type, amount) 
                                         VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $detailId, $comp['id'], $comp['component_name'], 
                        $comp['component_type'], $comp['amount']
                    ]);
                }
                
                // Add overtime component if exists
                if ($overtimePay > 0) {
                    $stmt = $db->prepare("INSERT INTO payroll_detail_components 
                                         (payroll_detail_id, component_id, component_name, component_type, amount) 
                                         VALUES (?, 5, 'Lembur', 'earning', ?)");
                    $stmt->execute([$detailId, $overtimePay]);
                }
                
                $totalEmployees++;
                $totalGross += $totalEarnings;
                $totalDeductions += $totalDeduction;
                $totalNet += $netSalary;
            }
            
            // Update payroll header
            $stmt = $db->prepare("UPDATE payrolls SET 
                                 total_employees = ?, total_gross = ?, 
                                 total_deductions = ?, total_net = ?,
                                 processed_at = NOW()
                                 WHERE id = ?");
            $stmt->execute([$totalEmployees, $totalGross, $totalDeductions, $totalNet, $payrollId]);
            
            $db->commit();
            $_SESSION['success'] = "Payroll berhasil di-generate untuk $totalEmployees karyawan!";
            header('Location: slip.php?payroll_id=' . $payrollId);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
    }
}

// Get existing payrolls
$payrolls = $db->query("SELECT * FROM payrolls ORDER BY period_year DESC, period_month DESC LIMIT 12")
               ->fetchAll(PDO::FETCH_ASSOC);

// Get employee count with salary
$empCount = $db->query("SELECT COUNT(DISTINCT e.id) 
                       FROM employees e
                       JOIN employee_salaries es ON e.id = es.employee_id
                       WHERE e.is_active = 1")->fetchColumn();

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🔄 Generate Payroll</h1>
        <a href="index.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <!-- Generate Form -->
    <div class="card" style="max-width: 600px; margin: 0 auto 30px;">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2>📅 Pilih Periode Payroll</h2>
        </div>
        <div class="card-body" style="padding: 30px;">
            <form method="POST" id="generateForm">
                <input type="hidden" name="action" value="generate">
                
                <div class="info-box" style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <p style="margin: 0; color: #1e40af; font-weight: 500;">
                        📊 <strong><?php echo $empCount; ?></strong> karyawan siap untuk payroll
                    </p>
                    <small style="color: #60a5fa;">
                        Sistem akan menghitung gaji pokok + tunjangan + lembur - potongan
                    </small>
                </div>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Bulan *</label>
                        <select name="month" class="form-control" required>
                            <?php
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            $currentMonth = date('n');
                            for ($i = 1; $i <= 12; $i++) {
                                $selected = ($i == $currentMonth) ? 'selected' : '';
                                echo "<option value='$i' $selected>{$months[$i-1]}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label>Tahun *</label>
                        <select name="year" class="form-control" required>
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) {
                                $selected = ($y == $currentYear) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="alert alert-warning" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
                    <strong>⚠️ Perhatian:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <li>Pastikan semua data gaji karyawan sudah benar</li>
                        <li>Payroll yang sudah di-generate tidak bisa dihapus</li>
                        <li>Hanya karyawan dengan gaji yang sudah di-set yang akan diproses</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;" 
                        onclick="return confirm('Generate payroll untuk periode yang dipilih?')">
                    🔄 Generate Payroll Sekarang
                </button>
            </form>
        </div>
    </div>
    
    <!-- History -->
    <div class="card">
        <div class="card-header">
            <h2>📜 Riwayat Payroll</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Karyawan</th>
                        <th>Total Gross</th>
                        <th>Total Potongan</th>
                        <th>Total Net</th>
                        <th>Status</th>
                        <th>Tanggal Generate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payrolls) > 0): ?>
                        <?php foreach ($payrolls as $p): ?>
                        <?php
                        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $monthNames[$p['period_month']] . ' ' . $p['period_year']; ?></strong>
                            </td>
                            <td><?php echo number_format($p['total_employees']); ?> orang</td>
                            <td>Rp <?php echo number_format($p['total_gross'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($p['total_deductions'], 0, ',', '.'); ?></td>
                            <td><strong>Rp <?php echo number_format($p['total_net'], 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php
                                $statusClass = ['draft' => 'badge-kontrak', 'processed' => 'badge-info', 'paid' => 'badge-tetap'];
                                $statusLabel = ['draft' => '📝 Draft', 'processed' => '✅ Processed', 'paid' => '💸 Paid'];
                                ?>
                                <span class="badge <?php echo $statusClass[$p['status']]; ?>">
                                    <?php echo $statusLabel[$p['status']]; ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo date('d M Y H:i', strtotime($p['processed_at'])); ?></small>
                            </td>
                            <td>
                                <a href="slip.php?payroll_id=<?php echo $p['id']; ?>" class="btn-action" title="Lihat Detail">
                                    👁️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">📋</p>
                                <p style="margin: 10px 0 0;">Belum ada payroll yang di-generate</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.form-row {
    display: flex;
    gap: 15px;
}

.alert-warning {
    font-size: 14px;
    color: #92400e;
}

.alert-warning ul {
    font-size: 13px;
}

.info-box {
    font-size: 14px;
}
</style>

<?php include '../../includes/footer.php'; ?>
