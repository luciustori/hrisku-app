-- Migration 002: Companies Table
-- Drop if exists
DROP TABLE IF EXISTS companies;

CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_code VARCHAR(50) UNIQUE NOT NULL,
    tax_id VARCHAR(50) COMMENT 'NPWP',
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(100),
    logo VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_code (company_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default company
INSERT INTO companies (company_name, company_code, tax_id, address, phone, email, is_active)
VALUES 
('PT Teknologi Indonesia', 'PTI', '00.000.000.0-000.000', 'Jakarta Pusat', '021-12345678', 'info@pti.co.id', 1);
