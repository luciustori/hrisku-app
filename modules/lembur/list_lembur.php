<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'super_admin'];
}

checkRole(['super_admin', 'admin', 'hrd']);

$page_title = 'Semua Lembur';

$database = new Database();
$db = $database->getConnection();

// Get all overtime with employee info
$query = "SELECT ot.*, 
          e.full_name,
          COALESCE(e.employee_code, CONCAT('EMP', LPAD(e.id, 4, '0'))) as employee_code,
          d.department_name
          FROM overtime_requests ot
          JOIN employees e ON ot.employee_id = e.id
          LEFT JOIN departments d ON e.department_id = d.id
          ORDER BY ot.created_at DESC";

try {
    $overtimes = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $overtimes = [];
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Semua Pengajuan Lembur</h1>
        <a href="index.php" class="btn btn-secondary">← Dashboard</a>
    </div>
    
    <!-- Filter Stats -->
    <div class="stats-mini">
        <?php
        $stats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total_hours' => 0
        ];
        foreach ($overtimes as $ot) {
            $stats[$ot['status']]++;
            if ($ot['status'] == 'approved') {
                $stats['total_hours'] += $ot['total_hours'];
            }
        }
        ?>
        <div class="stat-mini pending">
            <span class="stat-label">⏳ Pending</span>
            <span class="stat-value"><?php echo $stats['pending']; ?></span>
        </div>
        <div class="stat-mini approved">
            <span class="stat-label">✓ Disetujui</span>
            <span class="stat-value"><?php echo $stats['approved']; ?></span>
        </div>
        <div class="stat-mini rejected">
            <span class="stat-label">✗ Ditolak</span>
            <span class="stat-value"><?php echo $stats['rejected']; ?></span>
        </div>
        <div class="stat-mini hours">
            <span class="stat-label">⏰ Total Jam</span>
            <span class="stat-value"><?php echo number_format($stats['total_hours'], 1); ?></span>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Daftar Lembur (<?php echo count($overtimes); ?>)</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Durasi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($overtimes) > 0): ?>
                        <?php foreach ($overtimes as $ot): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ot['full_name'] ?? '-'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($ot['employee_code'] ?? '-'); ?></small>
                                <?php if (!empty($ot['department_name'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($ot['department_name']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo date('d M Y', strtotime($ot['overtime_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('l', strtotime($ot['overtime_date'])); ?></small>
                            </td>
                            <td>
                                <?php echo substr($ot['start_time'], 0, 5); ?> - <?php echo substr($ot['end_time'], 0, 5); ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <strong><?php echo number_format($ot['total_hours'], 1); ?></strong> jam
                                </span>
                            </td>
                            <td>
                                <?php 
                                $reason = $ot['reason'] ?? '';
                                echo htmlspecialchars(mb_substr($reason, 0, 40));
                                if (mb_strlen($reason) > 40) echo '...';
                                ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'badge-kontrak',
                                    'approved' => 'badge-tetap',
                                    'rejected' => 'badge-magang'
                                ];
                                $statusLabel = [
                                    'pending' => '⏳ Pending',
                                    'approved' => '✓ Disetujui',
                                    'rejected' => '✗ Ditolak'
                                ];
                                ?>
                                <span class="badge <?php echo $statusClass[$ot['status']]; ?>">
                                    <?php echo $statusLabel[$ot['status']]; ?>
                                </span>
                                <?php if ($ot['status'] == 'rejected' && !empty($ot['rejection_reason'])): ?>
                                    <br><small class="text-danger"><?php echo htmlspecialchars($ot['rejection_reason']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ot['status'] == 'pending'): ?>
                                    <form method="POST" action="approve.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $ot['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-action" style="background: #10b981;" title="Setujui">
                                            ✓
                                        </button>
                                    </form>
                                    <button onclick="showRejectModal(<?php echo $ot['id']; ?>)" class="btn-action btn-delete" title="Tolak">
                                        ✗
                                    </button>
                                <?php else: ?>
                                    <small class="text-muted">
                                        <?php echo date('d M Y', strtotime($ot['approved_at'] ?? $ot['updated_at'])); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                <p style="font-size: 48px; margin: 0;">📋</p>
                                <p style="margin: 10px 0 0;">Belum ada pengajuan lembur</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Tolak Pengajuan Lembur</h3>
            <button onclick="closeRejectModal()" class="modal-close">×</button>
        </div>
        <form method="POST" action="approve.php">
            <input type="hidden" name="id" id="rejectId">
            <input type="hidden" name="action" value="reject">
            <div class="modal-body">
                <div class="form-group">
                    <label>Alasan Penolakan *</label>
                    <textarea name="reason" class="form-control" rows="4" required 
                              placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<style>
.stats-mini {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-mini {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 5px;
    border-left: 4px solid;
}

.stat-mini.pending { border-color: #f59e0b; }
.stat-mini.approved { border-color: #10b981; }
.stat-mini.rejected { border-color: #ef4444; }
.stat-mini.hours { border-color: #3b82f6; }

.stat-label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}

.text-muted { color: #6b7280; font-size: 13px; }
.text-danger { color: #ef4444; font-size: 12px; }

.badge-info {
    background: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

/* Modal */
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
}

.modal.show { display: flex; }

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
}

.modal-close:hover { color: #374151; }

.modal-body { padding: 20px; }

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
function showRejectModal(id) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectModal').classList.add('show');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('show');
}

// Close modal when clicking outside
document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>

<?php include '../../includes/footer.php'; ?>
