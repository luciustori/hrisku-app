<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Slip Gaji';

$database = new Database();
$db = $database->getConnection();

$payrollId = intval($_GET['payroll_id'] ?? 0);
$employeeId = intval($_GET['employee_id'] ?? 0);

if ($payrollId == 0) {
    $_SESSION['error'] = 'Payroll ID tidak valid!';
    header('Location: index.php');
    exit();
}

// Get payroll header
$payroll = $db->query("SELECT p.*, u.username as processed_by_name
                       FROM payrolls p
                       LEFT JOIN users u ON p.processed_by = u.id
                       WHERE p.id = $payrollId")->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    $_SESSION['error'] = 'Payroll tidak ditemukan!';
    header('Location: index.php');
    exit();
}

// Get payroll details
$query = "SELECT pd.*, 
          e.employee_code, e.full_name, e.email, e.phone, e.employment_status,
          d.department_name, p.position_name, b.branch_name
          FROM payroll_details pd
          JOIN employees e ON pd.employee_id = e.id
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN positions p ON e.position_id = p.id
          LEFT JOIN branches b ON e.branch_id = b.id
          WHERE pd.payroll_id = $payrollId";

if ($employeeId > 0) {
    $query .= " AND pd.employee_id = $employeeId";
}

$query .= " ORDER BY e.full_name";

$details = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get month name
$monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$periodName = $monthNames[$payroll['period_month']] . ' ' . $payroll['period_year'];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>💰 Slip Gaji - <?php echo $periodName; ?></h1>
        <div>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print All</button>
            <a href="generate.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <!-- Payroll Summary -->
    <div class="card no-print" style="margin-bottom: 30px;">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2>📊 Ringkasan Payroll</h2>
        </div>
        <div class="card-body" style="padding: 30px;">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Total Karyawan</div>
                    <div class="summary-value"><?php echo number_format($payroll['total_employees']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Gross</div>
                    <div class="summary-value">Rp <?php echo number_format($payroll['total_gross'], 0, ',', '.'); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Potongan</div>
                    <div class="summary-value" style="color: #ef4444;">Rp <?php echo number_format($payroll['total_deductions'], 0, ',', '.'); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Total Net</div>
                    <div class="summary-value" style="color: #10b981; font-size: 32px;">Rp <?php echo number_format($payroll['total_net'], 0, ',', '.'); ?></div>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; color: #6b7280;">
                    <strong>Status:</strong> 
                    <?php
                    $statusClass = ['draft' => 'badge-kontrak', 'processed' => 'badge-info', 'paid' => 'badge-tetap'];
                    $statusLabel = ['draft' => '📝 Draft', 'processed' => '✅ Processed', 'paid' => '💸 Paid'];
                    ?>
                    <span class="badge <?php echo $statusClass[$payroll['status']]; ?>">
                        <?php echo $statusLabel[$payroll['status']]; ?>
                    </span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Diproses oleh:</strong> <?php echo htmlspecialchars($payroll['processed_by_name']); ?>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($payroll['processed_at'])); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Employee Slips -->
    <?php foreach ($details as $index => $detail): ?>
    <?php
    // Get component breakdown
    $components = $db->query("SELECT * FROM payroll_detail_components 
                              WHERE payroll_detail_id = {$detail['id']}
                              ORDER BY component_type, component_name")
                     ->fetchAll(PDO::FETCH_ASSOC);
    
    $earnings = array_filter($components, fn($c) => $c['component_type'] == 'earning');
    $deductions = array_filter($components, fn($c) => $c['component_type'] == 'deduction');
    ?>
    
    <div class="slip-page <?php echo $index > 0 ? 'page-break' : ''; ?>">
        <!-- Slip Header -->
        <div class="slip-header">
            <div class="company-info">
                <h2 style="margin: 0 0 5px 0; color: #1e40af;">HRISKU App</h2>
                <p style="margin: 0; color: #6b7280; font-size: 14px;">
                    PT. Technology Solutions Indonesia<br>
                    Jl. Sudirman No. 123, Jakarta Selatan<br>
                    Tel: (021) 1234-5678
                </p>
            </div>
            <div class="slip-title">
                <h1 style="margin: 0; color: #1e40af;">SLIP GAJI</h1>
                <p style="margin: 5px 0 0; color: #6b7280; font-size: 16px;">
                    Periode: <strong><?php echo $periodName; ?></strong>
                </p>
            </div>
        </div>
        
        <!-- Employee Info -->
        <div class="employee-section">
            <table class="info-table">
                <tr>
                    <td width="150"><strong>Nama</strong></td>
                    <td>: <?php echo htmlspecialchars($detail['full_name']); ?></td>
                    <td width="150"><strong>NIK</strong></td>
                    <td>: <?php echo htmlspecialchars($detail['employee_code']); ?></td>
                </tr>
                <tr>
                    <td><strong>Jabatan</strong></td>
                    <td>: <?php echo htmlspecialchars($detail['position_name'] ?? '-'); ?></td>
                    <td><strong>Departemen</strong></td>
                    <td>: <?php echo htmlspecialchars($detail['department_name'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>: <?php echo ucfirst($detail['employment_status']); ?></td>
                    <td><strong>Cabang</strong></td>
                    <td>: <?php echo htmlspecialchars($detail['branch_name'] ?? 'Head Office'); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Salary Details -->
        <div class="salary-section">
            <div class="salary-column">
                <h3 style="margin: 0 0 15px; color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 8px;">
                    💰 PENDAPATAN
                </h3>
                <table class="salary-table">
                    <tr>
                        <td><strong>Gaji Pokok</strong></td>
                        <td class="amount">Rp <?php echo number_format($detail['basic_salary'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php foreach ($earnings as $comp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comp['component_name']); ?></td>
                        <td class="amount">Rp <?php echo number_format($comp['amount'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($detail['overtime_hours'] > 0): ?>
                    <tr>
                        <td>Lembur (<?php echo number_format($detail['overtime_hours'], 1); ?> jam)</td>
                        <td class="amount">Rp <?php echo number_format($detail['overtime_pay'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="total-row" style="border-top: 2px solid #10b981;">
                        <td><strong>TOTAL PENDAPATAN</strong></td>
                        <td class="amount"><strong>Rp <?php echo number_format($detail['total_earnings'], 0, ',', '.'); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="salary-column">
                <h3 style="margin: 0 0 15px; color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 8px;">
                    ➖ POTONGAN
                </h3>
                <table class="salary-table">
                    <?php if (count($deductions) > 0): ?>
                        <?php foreach ($deductions as $comp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comp['component_name']); ?></td>
                            <td class="amount">Rp <?php echo number_format($comp['amount'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #9ca3af;">Tidak ada potongan</td>
                        </tr>
                    <?php endif; ?>
                    <tr class="total-row" style="border-top: 2px solid #ef4444;">
                        <td><strong>TOTAL POTONGAN</strong></td>
                        <td class="amount"><strong>Rp <?php echo number_format($detail['total_deductions'], 0, ',', '.'); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Net Salary -->
        <div class="net-salary">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; color: white;">GAJI BERSIH (TAKE HOME PAY)</h2>
                <h1 style="margin: 0; color: white; font-size: 36px;">
                    Rp <?php echo number_format($detail['net_salary'], 0, ',', '.'); ?>
                </h1>
            </div>
            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.8); font-size: 14px;">
                <em>Terbilang: <?php echo terbilang($detail['net_salary']); ?> Rupiah</em>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="slip-footer">
            <div style="text-align: center;">
                <p style="margin: 0 0 60px; color: #6b7280;">Karyawan,</p>
                <p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                    <strong><?php echo htmlspecialchars($detail['full_name']); ?></strong>
                </p>
            </div>
            <div style="text-align: center;">
                <p style="margin: 0 0 60px; color: #6b7280;">HRD,</p>
                <p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 200px;">
                    <strong><?php echo htmlspecialchars($payroll['processed_by_name']); ?></strong>
                </p>
            </div>
        </div>
        
        <div class="slip-note">
            <small style="color: #9ca3af;">
                Slip gaji ini dicetak otomatis oleh sistem HRISKU pada <?php echo date('d M Y H:i'); ?>. 
                Untuk pertanyaan terkait gaji, silakan hubungi HRD.
            </small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
/* Print Styles */
@media print {
    .no-print, .navbar, .page-header, .btn, button {
        display: none !important;
    }
    
    .container {
        width: 100%;
        max-width: none;
        padding: 0;
        margin: 0;
    }
    
    .page-break {
        page-break-before: always;
    }
    
    .slip-page {
        padding: 20px;
    }
}

/* Slip Styles */
.slip-page {
    background: white;
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
}

.slip-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    padding-bottom: 20px;
    border-bottom: 3px solid #1e40af;
    margin-bottom: 30px;
}

.slip-title {
    text-align: right;
}

.employee-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 8px 10px;
    font-size: 14px;
}

.salary-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.salary-table {
    width: 100%;
    border-collapse: collapse;
}

.salary-table td {
    padding: 10px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.salary-table td.amount {
    text-align: right;
    font-weight: 600;
}

.salary-table .total-row td {
    padding-top: 15px;
    font-size: 16px;
    border-bottom: none;
}

.net-salary {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.slip-footer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin: 40px 0 20px;
}

.slip-note {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

/* Summary Grid */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.summary-item {
    text-align: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
}

.summary-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 10px;
}

.summary-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e40af;
}

@media (max-width: 768px) {
    .salary-section {
        grid-template-columns: 1fr;
    }
    
    .summary-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>
