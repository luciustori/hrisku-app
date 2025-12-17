-- Migration 012: Seed Shifts Data
-- Data shift default sesuai requirement

INSERT INTO shifts (shift_name, shift_code, start_time, end_time, work_days, is_mod, mod_value, color_code) VALUES
('Shift Reguler', 'REG', '08:00:00', '17:00:00', 'senin-jumat', 0, 0, '#667eea'),
('Shift 1', 'S1', '06:00:00', '14:00:00', 'senin-minggu', 0, 0, '#10b981'),
('Shift 2', 'S2', '14:00:00', '22:00:00', 'senin-minggu', 0, 0, '#f59e0b'),
('Piket MOD', 'MOD', '08:00:00', '17:00:00', 'sabtu-minggu-libur', 1, 100000, '#ef4444');
