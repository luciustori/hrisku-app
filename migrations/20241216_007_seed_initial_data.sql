-- Migration 007: Seed Initial Data

-- Insert company
INSERT INTO companies (id, company_name, company_address, company_phone, company_email, npwp) 
VALUES (1, 'PT Contoh Indonesia', 'Jl. Sudirman No. 123, Jakarta Selatan', '021-12345678', 'info@contoh.co.id', '01.234.567.8-901.000');

-- Insert main branch
INSERT INTO branches (company_id, branch_name, branch_address, branch_phone, latitude, longitude, radius_meter) 
VALUES (1, 'Kantor Pusat Jakarta', 'Jl. Sudirman No. 123, Jakarta Selatan', '021-12345678', -6.208763, 106.845599, 100);

-- Insert departments
INSERT INTO departments (company_id, department_name, description) VALUES 
(1, 'Teknologi Informasi', 'Departemen IT dan Teknologi'),
(1, 'Human Resources', 'Departemen SDM dan Kepegawaian'),
(1, 'Finance', 'Departemen Keuangan'),
(1, 'Operations', 'Departemen Operasional'),
(1, 'Marketing', 'Departemen Pemasaran');

-- Insert positions
INSERT INTO positions (department_id, position_name, level_code) VALUES 
-- IT Department
(1, 'Direktur IT', 1),
(1, 'Manager IT', 2),
(1, 'Supervisor IT', 3),
(1, 'Koordinator IT', 4),
(1, 'Staff IT', 5),
-- HR Department
(2, 'Manager HRD', 2),
(2, 'Supervisor HRD', 3),
(2, 'Staff HRD', 5),
-- Finance Department
(3, 'Manager Finance', 2),
(3, 'Staff Finance', 5),
-- Operations
(4, 'Manager Operations', 2),
(4, 'Supervisor Operations', 3),
(4, 'Koordinator Operations', 4),
(4, 'Staff Operations', 5),
-- Marketing
(5, 'Manager Marketing', 2),
(5, 'Staff Marketing', 5);

-- Insert default super admin (password: admin123)
INSERT INTO users (username, password, role, employee_id, is_active) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 1);
