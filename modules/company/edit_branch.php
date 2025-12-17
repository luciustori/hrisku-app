<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

checkRole(['super_admin', 'admin']);

$page_title = 'Edit Cabang';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: branches.php');
    exit();
}

$error = '';
$success = '';

// Get branch data
$stmt = $db->prepare("SELECT * FROM branches WHERE id = :id");
$stmt->execute(['id' => $id]);
$branch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$branch) {
    header('Location: branches.php');
    exit();
}

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
        $query = "UPDATE branches SET
                  branch_code = :code,
                  branch_name = :name,
                  address = :address,
                  phone = :phone,
                  email = :email,
                  latitude = :lat,
                  longitude = :lng,
                  radius_meter = :radius,
                  is_active = :active,
                  updated_at = NOW()
                  WHERE id = :id";
        
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
            'active' => $is_active,
            'id' => $id
        ]);
        
        $success = 'Data cabang berhasil diupdate';
        
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM branches WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Edit Cabang</h1>
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
            <h2>Form Edit Cabang</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Kode Cabang *</label>
                        <input type="text" name="branch_code" class="form-control" required 
                               value="<?php echo htmlspecialchars($branch['branch_code']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Cabang *</label>
                        <input type="text" name="branch_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($branch['branch_name']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($branch['address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Telepon *</label>
                        <input type="text" name="phone" class="form-control" required 
                               value="<?php echo htmlspecialchars($branch['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($branch['email']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Latitude *</label>
                        <input type="text" name="latitude" id="latitude" class="form-control" required 
                               value="<?php echo $branch['latitude']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Longitude *</label>
                        <input type="text" name="longitude" id="longitude" class="form-control" required 
                               value="<?php echo $branch['longitude']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Radius Absensi (meter) *</label>
                    <input type="number" name="radius_meter" class="form-control" required 
                           value="<?php echo $branch['radius_meter']; ?>">
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-secondary" onclick="getMyLocation()">📍 Update Lokasi</button>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $branch['is_active'] ? 'checked' : ''; ?>>
                        Cabang Aktif
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Cabang</button>
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
            alert('Lokasi berhasil diupdate!');
        },
        function(error) {
            alert('Gagal mengambil lokasi: ' + error.message);
        }
    );
}
</script>

<?php include '../../includes/footer.php'; ?>
