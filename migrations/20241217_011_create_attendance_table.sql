-- Migration 011: Attendance Table
-- Data absensi check-in dan check-out karyawan

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    schedule_id INT NULL,
    attendance_date DATE NOT NULL,
    check_in_time DATETIME NULL,
    check_in_latitude DECIMAL(10, 8) NULL,
    check_in_longitude DECIMAL(11, 8) NULL,
    check_in_photo VARCHAR(255) NULL,
    check_in_notes TEXT,
    check_out_time DATETIME NULL,
    check_out_latitude DECIMAL(10, 8) NULL,
    check_out_longitude DECIMAL(11, 8) NULL,
    check_out_photo VARCHAR(255) NULL,
    check_out_notes TEXT,
    status ENUM('hadir', 'terlambat', 'pulang_cepat', 'alpha', 'izin', 'cuti', 'sakit') DEFAULT 'hadir',
    work_duration INT NULL COMMENT 'Durasi kerja dalam menit',
    late_duration INT NULL COMMENT 'Durasi terlambat dalam menit',
    overtime_duration INT NULL COMMENT 'Durasi lembur dalam menit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES employee_schedules(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (employee_id, attendance_date),
    INDEX idx_employee (employee_id),
    INDEX idx_date (attendance_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
