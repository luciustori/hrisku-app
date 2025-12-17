<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Hari Libur';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $holiday_name = sanitize($_POST['holiday_name']);
        $holiday_date = $_POST['holiday_date'];
        $holiday_type = $_POST['holiday_type'];
        $year = date('Y', strtotime($holiday_date));
        $description = sanitize($_POST['description']);
        
        try {
            $query = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, year, description) 
                      VALUES (:name, :date, :type, :year, :desc)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                'name' => $holiday_name,
                'date' => $holiday_date,
                'type' => $holiday_type,
                'year' => $year,
                'desc' => $description
            ]);
            $success = 'Hari libur berhasil ditambahkan';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action == 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $db->prepare("DELETE FROM holidays WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $success = 'Hari libur berhasil dihapus';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get holidays
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$holidays = $db->prepare("SELECT * FROM holidays WHERE year = :year ORDER BY holiday_date ASC");
$holidays->execute(['year' => $year]);
$holidays = $holidays->fetchAll(PDO::FETCH_ASSOC);

// Get available years
$years = $db->query("SELECT DISTINCT year FROM holidays ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) {
    $years = [date('Y')];
}

// Group by month
$holidays_by_month = [];
foreach ($holidays as $holiday) {
    $month = date('n', strtotime($holiday['holiday_date']));
    $holidays_by_month[$month][] = $holiday;
}

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Hari Libur Nasional & Cuti Bersama</h1>
        <div>
            <select class="form-control" onchange="location.href='?year=' + this.value" style="width: auto; display: inline-block;">
                <?php foreach($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                        Tahun <?php echo $y; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
            <h3>Total Libur</h3>
            <div class="stat-number"><?php echo count($holidays); ?></div>
            <div class="stat-label">Hari Libur</div>
        </div>
        
        <div class="stat-card">
            <h3>Libur Nasional</h3>
            <div class="stat-number">
                <?php echo count(array_filter($holidays, fn($h) => $h['holiday_type'] == 'nasional')); ?>
            </div>
            <div class="stat-label">Hari</div>
        </div>
        
        <div class="stat-card">
            <h3>Cuti Bersama</h3>
            <div class="stat-number">
                <?php echo count(array_filter($holidays, fn($h) => $h['holiday_type'] == 'cuti_bersama')); ?>
            </div>
            <div class="stat-label">Hari</div>
        </div>
    </div>
    
    <!-- Add Holiday Form -->
    <div class="card">
        <div class="card-header">
            <h2>Tambah Hari Libur</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Libur *</label>
                        <input type="text" name="holiday_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tanggal *</label>
                        <input type="date" name="holiday_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tipe Libur *</label>
                        <select name="holiday_type" class="form-control" required>
                            <option value="nasional">Libur Nasional</option>
                            <option value="cuti_bersama">Cuti Bersama</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Tambah Libur</button>
            </form>
        </div>
    </div>
    
    <!-- Holidays by Month -->
    <?php if (!empty($holidays_by_month)): ?>
        <?php foreach ($months as $monthNum => $monthName): ?>
            <?php if (isset($holidays_by_month[$monthNum])): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $monthName; ?> <?php echo $year; ?></h2>
                    <span class="badge badge-tetap">
                        <?php echo count($holidays_by_month[$monthNum]); ?> hari
                    </span>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Libur</th>
                                <th>Tipe</th>
                                <th>Deskripsi</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidays_by_month[$monthNum] as $holiday): ?>
                            <tr>
                                <td>
                                    <strong><?php echo formatDate($holiday['holiday_date'], 'd M Y'); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php 
                                        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                        echo $days[date('w', strtotime($holiday['holiday_date']))];
                                        ?>
                                    </small>
                                </td>
                                <td><strong><?php echo htmlspecialchars($holiday['holiday_name']); ?></strong></td>
                                <td>
                                    <?php if ($holiday['holiday_type'] == 'nasional'): ?>
                                        <span class="badge" style="background: #ef4444;">Libur Nasional</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Cuti Bersama</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($holiday['description'] ?: '-'); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Yakin hapus hari libur ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $holiday['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 60px;">
                <p style="color: #999; font-size: 18px;">Belum ada data hari libur untuk tahun <?php echo $year; ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
