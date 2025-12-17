<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Company Management';

$database = new Database();
$db = $database->getConnection();

// Get company info (ambil company pertama atau yang aktif)
$companyQuery = "SELECT * FROM companies WHERE is_active = 1 ORDER BY id ASC LIMIT 1";
$stmt = $db->query($companyQuery);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics
$stats = [
    'total_branches' => $db->query("SELECT COUNT(*) FROM branches WHERE is_active = 1")->fetchColumn(),
    'total_departments' => $db->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn(),
    'total_positions' => $db->query("SELECT COUNT(*) FROM positions WHERE is_active = 1")->fetchColumn()
];

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏢 Company Management</h1>
        <a href="../dashboard/index.php" class="btn btn-secondary">← Dashboard</a>
    </div>
    
    <!-- Company Info Card -->
    <?php if ($company): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Informasi Perusahaan</h2>
            <a href="edit_company.php?id=<?php echo $company['id']; ?>" class="btn btn-primary">Edit</a>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">NAMA PERUSAHAAN</h4>
                    <p style="font-size: 18px; font-weight: bold; margin: 0;">
                        <?php echo htmlspecialchars($company['company_name'] ?? '-'); ?>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">NPWP</h4>
                    <p style="font-size: 16px; margin: 0;">
                        <?php echo htmlspecialchars($company['tax_id'] ?? '-'); ?>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">ALAMAT</h4>
                    <p style="font-size: 16px; margin: 0;">
                        <?php echo nl2br(htmlspecialchars($company['address'] ?? '-')); ?>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">TELEPON</h4>
                    <p style="font-size: 16px; margin: 0;">
                        <?php echo htmlspecialchars($company['phone'] ?? '-'); ?>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">EMAIL</h4>
                    <p style="font-size: 16px; margin: 0;">
                        <?php echo htmlspecialchars($company['email'] ?? '-'); ?>
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 5px; color: #666;">WEBSITE</h4>
                    <p style="font-size: 16px; margin: 0;">
                        <?php 
                        $website = $company['website'] ?? '';
                        if ($website): 
                        ?>
                            <a href="<?php echo htmlspecialchars($website); ?>" target="_blank">
                                <?php echo htmlspecialchars($website); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($company['logo'])): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <h4 style="margin-bottom: 10px; color: #666;">LOGO PERUSAHAAN</h4>
                    <?php 
                    $logoPath = '../../uploads/companies/' . $company['logo'];
                    if (file_exists($logoPath)): 
                    ?>
                        <img src="<?php echo BASE_URL; ?>uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" 
                             alt="Company Logo" 
                             style="max-height: 100px; border: 1px solid #ddd; padding: 10px;">
                    <?php else: ?>
                        <p style="color: #999;">Logo tidak ditemukan</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 40px;">
            <h3>Belum ada data perusahaan</h3>
            <p style="color: #666; margin-bottom: 20px;">Tambahkan data perusahaan terlebih dahulu</p>
            <a href="add_company.php" class="btn btn-primary">+ Tambah Perusahaan</a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f6;">🏪</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_branches']; ?></h3>
                <p>Cabang Aktif</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b981;">🏛️</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_departments']; ?></h3>
                <p>Departemen</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b;">👔</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_positions']; ?></h3>
                <p>Jabatan</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="card">
        <div class="card-header">
            <h2>Menu Perusahaan</h2>
        </div>
        <div class="quick-links">
            <a href="companies.php" class="quick-link-card">
                <div class="quick-link-icon">🏢</div>
                <h3>Perusahaan</h3>
                <p>Kelola data perusahaan</p>
            </a>
            
            <a href="branches.php" class="quick-link-card">
                <div class="quick-link-icon">🏪</div>
                <h3>Cabang</h3>
                <p>Kelola cabang/lokasi</p>
            </a>
            
            <a href="departments.php" class="quick-link-card">
                <div class="quick-link-icon">🏛️</div>
                <h3>Departemen</h3>
                <p>Kelola departemen</p>
            </a>
            
            <a href="manage_positions.php" class="quick-link-card">
                <div class="quick-link-icon">👔</div>
                <h3>Jabatan</h3>
                <p>Kelola jabatan</p>
            </a>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}

.stat-info h3 {
    margin: 0;
    font-size: 32px;
    color: #1f2937;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #6b7280;
    font-size: 14px;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.quick-link-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 20px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.quick-link-card:hover {
    transform: translateY(-5px);
}

.quick-link-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.quick-link-card h3 {
    margin: 10px 0;
    font-size: 20px;
    color: white;
}

.quick-link-card p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}

.quick-link-card:nth-child(2) {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.quick-link-card:nth-child(3) {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.quick-link-card:nth-child(4) {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}
</style>

<?php include '../../includes/footer.php'; ?>
