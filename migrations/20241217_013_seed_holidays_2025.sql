-- Migration 013: Seed Holidays 2025
-- Libur Nasional & Cuti Bersama Tahun 2025

INSERT INTO holidays (holiday_name, holiday_date, holiday_type, year, description) VALUES
-- Januari
('Tahun Baru 2025', '2025-01-01', 'nasional', 2025, 'Tahun Baru Masehi'),
('Cuti Bersama Tahun Baru', '2025-01-02', 'cuti_bersama', 2025, 'Cuti Bersama'),
('Cuti Bersama Tahun Baru', '2025-01-03', 'cuti_bersama', 2025, 'Cuti Bersama'),

-- Maret
('Isra Miraj', '2025-03-27', 'nasional', 2025, 'Isra Miraj Nabi Muhammad SAW'),

-- April
('Wafat Isa Almasih', '2025-04-18', 'nasional', 2025, 'Wafat Yesus Kristus'),
('Hari Raya Paskah', '2025-04-20', 'nasional', 2025, 'Hari Raya Paskah'),

-- Mei
('Hari Buruh', '2025-05-01', 'nasional', 2025, 'Hari Buruh Internasional'),
('Kenaikan Isa Almasih', '2025-05-29', 'nasional', 2025, 'Kenaikan Yesus Kristus'),
('Hari Raya Idul Fitri 1446 H', '2025-05-30', 'nasional', 2025, 'Hari Raya Idul Fitri'),
('Hari Raya Idul Fitri 1446 H', '2025-05-31', 'nasional', 2025, 'Hari Raya Idul Fitri'),

-- Juni
('Cuti Bersama Idul Fitri', '2025-06-02', 'cuti_bersama', 2025, 'Cuti Bersama Idul Fitri'),
('Cuti Bersama Idul Fitri', '2025-06-03', 'cuti_bersama', 2025, 'Cuti Bersama Idul Fitri'),
('Hari Raya Waisak', '2025-06-12', 'nasional', 2025, 'Hari Raya Waisak 2569'),
('Pancasila', '2025-06-01', 'nasional', 2025, 'Hari Lahir Pancasila'),

-- Agustus
('Hari Raya Idul Adha', '2025-08-06', 'nasional', 2025, 'Hari Raya Idul Adha 1446 H'),
('Cuti Bersama Idul Adha', '2025-08-07', 'cuti_bersama', 2025, 'Cuti Bersama Idul Adha'),
('Kemerdekaan RI', '2025-08-17', 'nasional', 2025, 'HUT Kemerdekaan RI ke-80'),
('Tahun Baru Islam', '2025-08-27', 'nasional', 2025, 'Tahun Baru Islam 1447 H'),

-- Oktober
('Maulid Nabi Muhammad SAW', '2025-10-05', 'nasional', 2025, 'Maulid Nabi Muhammad SAW'),

-- Desember
('Hari Raya Natal', '2025-12-25', 'nasional', 2025, 'Hari Raya Natal'),
('Cuti Bersama Natal', '2025-12-26', 'cuti_bersama', 2025, 'Cuti Bersama Natal');
