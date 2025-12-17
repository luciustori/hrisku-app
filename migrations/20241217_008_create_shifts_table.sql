-- Migration 008: Shifts Table
-- Shift Reguler, Shift 1, Shift 2, dan Piket MOD

CREATE TABLE shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(100) NOT NULL,
    shift_code VARCHAR(20) UNIQUE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    work_days VARCHAR(50) NOT NULL COMMENT 'senin-jumat, senin-minggu, sabtu-minggu',
    is_mod TINYINT(1) DEFAULT 0 COMMENT 'Piket MOD untuk hari libur',
    mod_value DECIMAL(10,2) DEFAULT 0 COMMENT 'Nilai MOD dalam rupiah',
    color_code VARCHAR(7) DEFAULT '#667eea',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shift_code (shift_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
