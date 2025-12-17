<?php
/**
 * Helper Functions untuk HRISKU App
 * Semua function utility ada di sini
 */

// Format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Format Date Indonesia
function formatDate($date, $format = 'd M Y') {
    if (!$date) return '-';
    
    $months = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
        'May' => 'Mei', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Agt',
        'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des'
    ];
    
    $formatted = date($format, strtotime($date));
    
    foreach ($months as $eng => $ind) {
        $formatted = str_replace($eng, $ind, $formatted);
    }
    
    return $formatted;
}

// Format Date Time Indonesia
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return formatDate($datetime, 'd M Y H:i');
}

// Sanitize Input
function sanitize($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate Random String
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload File Handler
 */
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5000000) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    if ($fileError !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
        ];
        
        $message = isset($errorMessages[$fileError]) ? $errorMessages[$fileError] : 'Upload error: ' . $fileError;
        return ['success' => false, 'message' => $message];
    }
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    if ($fileSize > $maxSize) {
        $maxSizeMB = $maxSize / 1000000;
        return ['success' => false, 'message' => 'File too large (max ' . $maxSizeMB . 'MB)'];
    }
    
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    $newFileName = uniqid('file_', true) . '.' . $fileExt;
    $targetPath = $targetDir . $newFileName;
    
    if (move_uploaded_file($fileTmp, $targetPath)) {
        return [
            'success' => true, 
            'filename' => $newFileName,
            'path' => $targetPath,
            'message' => 'File uploaded successfully'
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

/**
 * Delete File
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get Status Badge Class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'tetap' => 'badge-tetap',
        'kontrak' => 'badge-kontrak',
        'probation' => 'badge-probation',
        'magang' => 'badge-magang',
        'aktif' => 'badge-tetap',
        'nonaktif' => 'badge-probation',
        'hadir' => 'badge-tetap',
        'terlambat' => 'badge-warning',
        'izin' => 'badge-kontrak',
        'sakit' => 'badge-warning',
        'alpha' => 'badge-probation'
    ];
    
    return $classes[$status] ?? 'badge-default';
}

/**
 * Safe HTML escape that handles null values
 */
function e($string, $default = '') {
    if ($string === null || $string === '') {
        return $default;
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Safe output for nullable strings
 */
function safe($value, $default = '-') {
    return !empty($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $default;
}

/**
 * Pagination Helper
 */
function getPagination($total, $perPage, $currentPage, $baseUrl) {
    $totalPages = ceil($total / $perPage);
    
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">← Prev</a>';
    }
    
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link ' . $active . '">' . $i . '</a>';
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next →</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Logger
 */
function logActivity($user_id, $action, $description, $db) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (:user_id, :action, :description)");
        $stmt->execute([
            'user_id' => $user_id,
            'action' => $action,
            'description' => $description
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('Log Activity Error: ' . $e->getMessage());
        return false;
    }
}

// Helper function to convert number to words (Indonesian)
function terbilang($angka) {
    $angka = abs($angka);
    $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    
    if ($angka < 12) {
        return $baca[$angka];
    } elseif ($angka < 20) {
        return terbilang($angka - 10) . " Belas";
    } elseif ($angka < 100) {
        return terbilang($angka / 10) . " Puluh " . terbilang($angka % 10);
    } elseif ($angka < 200) {
        return "Seratus " . terbilang($angka - 100);
    } elseif ($angka < 1000) {
        return terbilang($angka / 100) . " Ratus " . terbilang($angka % 100);
    } elseif ($angka < 2000) {
        return "Seribu " . terbilang($angka - 1000);
    } elseif ($angka < 1000000) {
        return terbilang($angka / 1000) . " Ribu " . terbilang($angka % 1000);
    } elseif ($angka < 1000000000) {
        return terbilang($angka / 1000000) . " Juta " . terbilang($angka % 1000000);
    } elseif ($angka < 1000000000000) {
        return terbilang($angka / 1000000000) . " Miliar " . terbilang($angka % 1000000000);
    } else {
        return terbilang($angka / 1000000000000) . " Triliun " . terbilang($angka % 1000000000000);
    }
}
