-- 1. Master Komponen Gaji
CREATE TABLE salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('earning', 'deduction') NOT NULL,
    is_fixed BOOLEAN DEFAULT true,
    is_taxable BOOLEAN DEFAULT true,
    calculation_method ENUM('fixed', 'percentage', 'formula') DEFAULT 'fixed',
    default_amount DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (component_type),
    INDEX idx_active (is_active)
);

-- 2. Master Gaji Karyawan
CREATE TABLE employee_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    effective_date DATE NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_date (effective_date)
);

-- 3. Detail Komponen per Karyawan
CREATE TABLE employee_salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    component_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE CASCADE,
    UNIQUE KEY unique_emp_comp (employee_id, component_id),
    INDEX idx_employee (employee_id),
    INDEX idx_component (component_id)
);

-- 4. Payroll Bulanan (Header)
CREATE TABLE payrolls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_month INT NOT NULL,
    period_year INT NOT NULL,
    total_employees INT DEFAULT 0,
    total_gross DECIMAL(15,2) DEFAULT 0,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    total_net DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'processed', 'paid') DEFAULT 'draft',
    processed_by INT,
    processed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_period (period_month, period_year),
    INDEX idx_period (period_year, period_month),
    INDEX idx_status (status)
);

-- 5. Payroll Detail per Karyawan
CREATE TABLE payroll_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_id INT NOT NULL,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_earnings DECIMAL(15,2) DEFAULT 0,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    days_worked INT DEFAULT 0,
    days_absent INT DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    leave_deduction DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payroll_emp (payroll_id, employee_id),
    INDEX idx_payroll (payroll_id),
    INDEX idx_employee (employee_id)
);

-- 6. Breakdown Komponen per Detail
CREATE TABLE payroll_detail_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_detail_id INT NOT NULL,
    component_id INT NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('earning', 'deduction') NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_detail_id) REFERENCES payroll_details(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE CASCADE,
    INDEX idx_detail (payroll_detail_id),
    INDEX idx_component (component_id)
);

-- Insert Default Components
INSERT INTO salary_components (component_name, component_type, is_fixed, is_taxable, description) VALUES
('Gaji Pokok', 'earning', true, true, 'Gaji pokok bulanan'),
('Tunjangan Transport', 'earning', true, false, 'Tunjangan transportasi'),
('Tunjangan Makan', 'earning', true, false, 'Tunjangan makan'),
('Tunjangan Kehadiran', 'earning', false, false, 'Tunjangan berdasarkan kehadiran'),
('Lembur', 'earning', false, true, 'Pembayaran lembur'),
('Bonus', 'earning', false, true, 'Bonus kinerja'),
('BPJS Kesehatan', 'deduction', true, false, 'Potongan BPJS Kesehatan (1%)'),
('BPJS Ketenagakerjaan', 'deduction', true, false, 'Potongan BPJS TK (2%)'),
('PPh 21', 'deduction', false, true, 'Pajak Penghasilan'),
('Potongan Absen', 'deduction', false, false, 'Potongan ketidakhadiran'),
('Potongan Pinjaman', 'deduction', false, false, 'Potongan cicilan pinjaman');
