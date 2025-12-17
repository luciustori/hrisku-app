<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Tambah Cabang';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $branch_code = sanitize($_POST['branch_code']);
    $branch_name = sanitize($_POST['branch_name']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $email = sanitize($_POST['email']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $radius_meter = (int)$_POST['radius_meter'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        $query = "INSERT INTO branches (branch_code, branch_name, address, phone, email, latitude, longitude, radius_meter, is_active)
                  VALUES (:code, :name, :address, :phone, :email, :lat, :lng, :radius, :active)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            'code' => $branch_code,
            'name' => $branch_name,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'lat' => $latitude,
            'lng' => $longitude,
            'radius' => $radius_meter,
            'active' => $is_active
        ]);
        
        $success = 'Cabang berhasil ditambahkan';
        
        // Redirect after 2 seconds
        header('Refresh: 2; URL=branches.php');
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Tambah Cabang Baru</h1>
        <a href="branches.php" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Form Tambah Cabang</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Cabang *</label>
                        <input type="text" name="branch_code" class="form-control" required placeholder="Contoh: JKT, BDG, SBY">
                    </div>
                    <div class="form-group">
                        <label>Nama Cabang *</label>
                        <input type="text" name="branch_name" class="form-control" required placeholder="Contoh: Kantor Pusat Jakarta">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Telepon *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="021-12345678">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" placeholder="cabang@perusahaan.com">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude *</label>
                        <input type="text" name="latitude" id="latitude" class="form-control" required placeholder="-6.200000">
                        <small class="text-muted">Contoh: -6.200000</small>
                    </div>
                    <div class="form-group">
                        <label>Longitude *</label>
                        <input type="text" name="longitude" id="longitude" class="form-control" required placeholder="106.816666">
                        <small class="text-muted">Contoh: 106.816666</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Radius Absensi (meter) *</label>
                    <input type="number" name="radius_meter" class="form-control" required value="100">
                    <small class="text-muted">Radius area valid untuk absensi (default: 100 meter)</small>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-secondary" onclick="getMyLocation()">📍 Ambil Lokasi Saya</button>
                    <small class="text-muted">Klik untuk mengisi koordinat otomatis dari lokasi Anda saat ini</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Cabang Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Cabang</button>
                <a href="branches.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
function getMyLocation() {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung Geolocation');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
            alert('Lokasi berhasil diambil!');
        },
        function(error) {
            alert('Gagal mengambil lokasi: ' + error.message);
        }
    );
}
</script>

<?php include '../../includes/footer.php'; ?>
