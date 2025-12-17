<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];

$database = new Database();
$db = $database->getConnection();
$user = $_SESSION['user'];

$emp = $db->query("SELECT id FROM employees WHERE email='{$user['username']}' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$overtimes = [];
if ($emp) {
    $overtimes = $db->query("SELECT * FROM overtime_requests WHERE employee_id={$emp['id']} ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Lembur Saya</h1>
        <div>
            <a href="pengajuan.php" class="btn btn-primary">+ Ajukan Lembur</a>
            <a href="index.php" class="btn btn-secondary">← Dashboard</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h2>Riwayat Lembur</h2></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($overtimes) > 0): foreach ($overtimes as $ot): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($ot['overtime_date'])); ?></td>
                        <td><?php echo substr($ot['start_time'], 0, 5) . ' - ' . substr($ot['end_time'], 0, 5); ?></td>
                        <td><strong><?php echo $ot['total_hours']; ?> jam</strong></td>
                        <td><?php echo htmlspecialchars(substr($ot['reason'], 0, 50)) . '...'; ?></td>
                        <td>
                            <?php
                            $badges = ['pending' => 'badge-kontrak', 'approved' => 'badge-tetap', 'rejected' => 'badge-magang'];
                            $labels = ['pending' => '⏳ Pending', 'approved' => '✓ Disetujui', 'rejected' => '✗ Ditolak'];
                            ?>
                            <span class="badge <?php echo $badges[$ot['status']]; ?>">
                                <?php echo $labels[$ot['status']]; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;">Belum ada data lembur</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
