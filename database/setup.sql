CREATE DATABASE IF NOT EXISTS chickdream_hr;
USE chickdream_hr;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'hr', 'manager', 'employee') DEFAULT 'employee',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    department VARCHAR(100),
    position VARCHAR(100),
    hire_date DATE,
    status ENUM('active', 'inactive', 'terminated', 'resigned') DEFAULT 'active',
    employment_type ENUM('permanent', 'contract', 'intern') DEFAULT 'permanent',
    salary DECIMAL(12,2) DEFAULT 0,
    monthly_salary DECIMAL(12,2) DEFAULT 0,
    address TEXT,
    photo VARCHAR(255),
    birth_place VARCHAR(100),
    birth_date DATE,
    grace_period DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    nip VARCHAR(20),
    date DATE,
    check_in TIME,
    check_out TIME,
    status ENUM('hadir', 'sakit', 'izin', 'alpha') DEFAULT 'hadir',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- Placements table
CREATE TABLE IF NOT EXISTS placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    location VARCHAR(255),
    start_date DATE,
    end_date DATE,
    placement_type ENUM('permanent', 'temporary', 'loan', 'rotation', 'project') DEFAULT 'permanent',
    duration_months INT DEFAULT NULL,
    description TEXT,
    notes TEXT,
    status ENUM('active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Payroll table
CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_month INT NOT NULL,
    period_year INT NOT NULL,
    basic_salary DECIMAL(12,2) DEFAULT 0,
    allowances DECIMAL(12,2) DEFAULT 0,
    overtime DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    gross_salary DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) DEFAULT 0,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_payroll (employee_id, period_month, period_year)
);

-- User permissions table
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module VARCHAR(50) NOT NULL,
    permission VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (user_id, module, permission)
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'email', 'url') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Users
INSERT IGNORE INTO users (email, password, name, role, status) VALUES 
('superadmin@chickdream.com', MD5('superadmin123'), 'Super Administrator', 'super_admin', 'active'),
('admin@chickdream.com', MD5('admin123'), 'Administrator', 'admin', 'active'),
('hr@chickdream.com', MD5('hr123'), 'Siti Nurhaliza', 'hr', 'active'),
('manager@chickdream.com', MD5('manager123'), 'Bambang Sutrisno', 'manager', 'active'),
('employee@chickdream.com', MD5('emp123'), 'Andi Pratama', 'employee', 'active');

-- Insert Employees with comprehensive data
INSERT IGNORE INTO employees (nip, name, email, phone, department, position, hire_date, status, employment_type, salary, monthly_salary, address, birth_place, birth_date, grace_period) VALUES
('EMP001', 'Super Administrator', 'superadmin@chickdream.com', '081234567890', 'IT', 'System Administrator', '2023-01-15', 'active', 'permanent', 5000000, 5500000, 'Jl. Raya Wonosobo No. 123, Wonosobo, Jawa Tengah', 'Wonosobo', '1995-06-01', NULL),
('EMP002', 'Siti Nurhaliza', 'siti@chickdream.com', '081234567891', 'HR', 'HR Manager', '2023-02-01', 'active', 'permanent', 4500000, 5000000, 'Jl. Pemuda No. 45, Wonosobo, Jawa Tengah', 'Wonosobo', '1990-03-15', NULL),
('EMP003', 'Bambang Sutrisno', 'bambang@chickdream.com', '081234567892', 'Production', 'Production Manager', '2023-02-15', 'active', 'permanent', 4800000, 5200000, 'Jl. Sudirman No. 67, Wonosobo, Jawa Tengah', 'Wonosobo', '1988-12-10', NULL),
('EMP004', 'Andi Pratama', 'andi@chickdream.com', '081234567893', 'Finance', 'Finance Manager', '2023-03-01', 'active', 'permanent', 4700000, 5100000, 'Jl. Diponegoro No. 89, Wonosobo, Jawa Tengah', 'Wonosobo', '1992-08-20', NULL),
('EMP005', 'Budi Santoso', 'budi@chickdream.com', '081234567894', 'Marketing', 'Marketing Staff', '2023-01-20', 'active', 'permanent', 3800000, 4200000, 'Jl. Ahmad Yani No. 12, Wonosobo, Jawa Tengah', 'Wonosobo', '1993-05-25', NULL),
('EMP006', 'Sari Dewi', 'sari@chickdream.com', '081234567895', 'Finance', 'Accountant', '2023-02-10', 'active', 'permanent', 3600000, 4000000, 'Jl. Gatot Subroto No. 34, Wonosobo, Jawa Tengah', 'Wonosobo', '1991-09-18', NULL),
('EMP007', 'Joko Widodo', 'joko@chickdream.com', '081234567896', 'Production', 'Production Staff', '2023-02-20', 'active', 'permanent', 3400000, 3800000, 'Jl. Veteran No. 56, Wonosobo, Jawa Tengah', 'Wonosobo', '1989-11-30', NULL),
('EMP008', 'Maya Sari', 'maya@chickdream.com', '081234567897', 'IT', 'Junior Developer', '2024-03-15', 'active', 'contract', 3200000, 3600000, 'Jl. Pahlawan No. 78, Wonosobo, Jawa Tengah', 'Wonosobo', '1998-01-10', '2025-03-15'),
('EMP009', 'Agus Setiawan', 'agus@chickdream.com', '081234567898', 'Marketing', 'Marketing Intern', '2024-04-01', 'active', 'intern', 1500000, 1800000, 'Jl. Merdeka No. 90, Wonosobo, Jawa Tengah', 'Wonosobo', '2000-05-20', '2024-10-01'),
('EMP010', 'Linda Dewi', 'linda@chickdream.com', '081234567899', 'Finance', 'Senior Accountant', '2022-06-15', 'inactive', 'permanent', 4800000, 5200000, 'Jl. Kartini No. 21, Wonosobo, Jawa Tengah', 'Wonosobo', '1987-11-08', NULL),
('EMP011', 'Rudi Hartono', 'rudi@chickdream.com', '081234567800', 'Production', 'Former Supervisor', '2022-01-10', 'resigned', 'permanent', 4200000, 4600000, 'Jl. Cut Nyak Dien No. 43, Wonosobo, Jawa Tengah', 'Wonosobo', '1985-07-30', NULL),
('EMP012', 'Dewi Sartika', 'dewi@chickdream.com', '081234567801', 'HR', 'HR Staff', '2024-01-05', 'active', 'contract', 3000000, 3400000, 'Jl. RA Kartini No. 65, Wonosobo, Jawa Tengah', 'Wonosobo', '1996-04-12', '2025-01-05'),
('EMP013', 'Ahmad Yani', 'ahmad@chickdream.com', '081234567802', 'IT', 'Network Administrator', '2023-09-01', 'active', 'permanent', 4000000, 4400000, 'Jl. Hasanudin No. 87, Wonosobo, Jawa Tengah', 'Wonosobo', '1994-02-28', NULL),
('EMP014', 'Fatimah Azzahra', 'fatimah@chickdream.com', '081234567803', 'Marketing', 'Digital Marketing', '2024-02-14', 'active', 'contract', 2800000, 3200000, 'Jl. Tuanku Imam Bonjol No. 19, Wonosobo, Jawa Tengah', 'Wonosobo', '1997-12-05', '2025-02-14'),
('EMP015', 'Hendra Wijaya', 'hendra@chickdream.com', '081234567804', 'Production', 'Quality Control', '2023-11-20', 'active', 'permanent', 3500000, 3900000, 'Jl. Pangeran Diponegoro No. 31, Wonosobo, Jawa Tengah', 'Wonosobo', '1990-09-14', NULL);

-- Insert Attendance data for last 30 days
INSERT IGNORE INTO attendance (nip, date, check_in, check_out, status, notes) VALUES
-- Recent attendance data
('EMP001', '2024-12-20', '08:00:00', '17:00:00', 'hadir', 'Normal work day'),
('EMP002', '2024-12-20', '08:15:00', '17:15:00', 'hadir', 'Slightly late'),
('EMP003', '2024-12-20', '07:45:00', '16:45:00', 'hadir', 'Early arrival'),
('EMP004', '2024-12-20', NULL, NULL, 'sakit', 'Sick leave with medical certificate'),
('EMP005', '2024-12-20', '08:30:00', '17:30:00', 'hadir', 'Normal work day'),
('EMP006', '2024-12-20', '08:00:00', '17:00:00', 'hadir', 'Normal work day'),
('EMP007', '2024-12-20', NULL, NULL, 'izin', 'Family emergency'),
('EMP008', '2024-12-20', '09:00:00', '18:00:00', 'hadir', 'Overtime work'),
('EMP009', '2024-12-20', '08:30:00', '16:30:00', 'hadir', 'Intern schedule'),
('EMP012', '2024-12-20', '08:00:00', '17:00:00', 'hadir', 'Normal work day'),
('EMP013', '2024-12-20', '07:30:00', '16:30:00', 'hadir', 'Server maintenance'),
('EMP014', '2024-12-20', '08:45:00', '17:45:00', 'hadir', 'Campaign work'),
('EMP015', '2024-12-20', '08:00:00', '17:00:00', 'hadir', 'Quality inspection'),

-- Previous day
('EMP001', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP002', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP003', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP004', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP005', '2024-12-19', NULL, NULL, 'alpha', 'No show'),
('EMP006', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP007', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP008', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP009', '2024-12-19', '08:30:00', '16:30:00', 'hadir', NULL),
('EMP012', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP013', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL),
('EMP014', '2024-12-19', NULL, NULL, 'izin', 'Personal matters'),
('EMP015', '2024-12-19', '08:00:00', '17:00:00', 'hadir', NULL);

-- Insert Placements
INSERT IGNORE INTO placements (employee_id, location, start_date, placement_type, duration_months, description, notes, status) VALUES
(1, 'Kantor Pusat Wonosobo', '2023-01-15', 'permanent', NULL, 'System Administrator di kantor pusat', 'Bertanggung jawab atas seluruh infrastruktur IT', 'active'),
(2, 'Kantor Pusat Wonosobo', '2023-02-01', 'permanent', NULL, 'HR Manager di kantor pusat', 'Mengelola seluruh aspek SDM perusahaan', 'active'),
(3, 'Pabrik Produksi Jepara', '2023-02-15', 'permanent', NULL, 'Production Manager di pabrik', 'Mengawasi seluruh proses produksi', 'active'),
(4, 'Kantor Pusat Wonosobo', '2023-03-01', 'permanent', NULL, 'Finance Manager di kantor pusat', 'Mengelola keuangan perusahaan', 'active'),
(5, 'Cabang Jakarta', '2024-06-01', 'temporary', 6, 'Ekspansi pasar Jakarta', 'Membuka pasar baru di wilayah Jakarta', 'active'),
(6, 'Kantor Pusat Wonosobo', '2023-02-10', 'permanent', NULL, 'Accountant di finance dept', 'Mengelola pembukuan perusahaan', 'active'),
(7, 'Pabrik Produksi Jepara', '2023-02-20', 'permanent', NULL, 'Production Staff di pabrik', 'Operator mesin produksi', 'active'),
(8, 'Proyek Sistem Baru', '2024-03-15', 'project', 12, 'Pengembangan sistem HR baru', 'Developer untuk sistem informasi HR', 'active'),
(9, 'Divisi Marketing', '2024-04-01', 'temporary', 6, 'Program magang marketing', 'Pembelajaran praktik marketing', 'active'),
(12, 'Kantor Pusat Wonosobo', '2024-01-05', 'loan', 12, 'Dipinjamkan ke HR dept', 'Support operasional HR', 'active'),
(13, 'Data Center Jakarta', '2024-09-01', 'rotation', 4, 'Rotasi ke data center', 'Pembelajaran infrastruktur besar', 'active'),
(14, 'Cabang Semarang', '2024-02-14', 'temporary', 8, 'Pengembangan market Semarang', 'Digital marketing untuk wilayah Semarang', 'active'),
(15, 'Pabrik Produksi Jepara', '2023-11-20', 'permanent', NULL, 'Quality Control pabrik', 'Kontrol kualitas produk', 'active');

-- Insert Payroll data for current and previous months
INSERT IGNORE INTO payroll (employee_id, period_month, period_year, basic_salary, allowances, overtime, deductions, tax, gross_salary, net_salary, status, approved_by, approved_at) VALUES
-- December 2024 payroll
(1, 12, 2024, 5000000, 500000, 200000, 100000, 285000, 5700000, 5315000, 'approved', 1, '2024-12-15 10:00:00'),
(2, 12, 2024, 4500000, 450000, 150000, 50000, 247500, 5100000, 4802500, 'approved', 1, '2024-12-15 10:00:00'),
(3, 12, 2024, 4800000, 480000, 300000, 80000, 278000, 5580000, 5222000, 'approved', 1, '2024-12-15 10:00:00'),
(4, 12, 2024, 4700000, 470000, 100000, 60000, 263500, 5270000, 4946500, 'approved', 1, '2024-12-15 10:00:00'),
(5, 12, 2024, 3800000, 380000, 250000, 70000, 221500, 4430000, 4138500, 'draft', NULL, NULL),
(6, 12, 2024, 3600000, 360000, 120000, 40000, 204000, 4080000, 3836000, 'draft', NULL, NULL),
(7, 12, 2024, 3400000, 340000, 180000, 50000, 196000, 3920000, 3674000, 'draft', NULL, NULL),
(8, 12, 2024, 3200000, 320000, 80000, 30000, 180000, 3600000, 3390000, 'draft', NULL, NULL),
(9, 12, 2024, 1500000, 150000, 50000, 20000, 85000, 1700000, 1595000, 'draft', NULL, NULL),
(12, 12, 2024, 3000000, 300000, 100000, 40000, 170000, 3400000, 3190000, 'draft', NULL, NULL),
(13, 12, 2024, 4000000, 400000, 200000, 60000, 230000, 4600000, 4310000, 'draft', NULL, NULL),
(14, 12, 2024, 2800000, 280000, 150000, 35000, 161500, 3230000, 3033500, 'draft', NULL, NULL),
(15, 12, 2024, 3500000, 350000, 100000, 45000, 197500, 3950000, 3707500, 'draft', NULL, NULL),

-- November 2024 payroll (all approved and paid)
(1, 11, 2024, 5000000, 500000, 150000, 80000, 282500, 5650000, 5287500, 'paid', 1, '2024-11-15 10:00:00'),
(2, 11, 2024, 4500000, 450000, 100000, 50000, 245000, 5050000, 4755000, 'paid', 1, '2024-11-15 10:00:00'),
(3, 11, 2024, 4800000, 480000, 200000, 70000, 274000, 5480000, 5136000, 'paid', 1, '2024-11-15 10:00:00'),
(4, 11, 2024, 4700000, 470000, 80000, 60000, 262500, 5250000, 4927500, 'paid', 1, '2024-11-15 10:00:00'),
(5, 11, 2024, 3800000, 380000, 200000, 65000, 219000, 4380000, 4096000, 'paid', 1, '2024-11-15 10:00:00'),
(6, 11, 2024, 3600000, 360000, 100000, 40000, 203000, 4060000, 3817000, 'paid', 1, '2024-11-15 10:00:00'),
(7, 11, 2024, 3400000, 340000, 150000, 45000, 194500, 3890000, 3650500, 'paid', 1, '2024-11-15 10:00:00'),
(8, 11, 2024, 3200000, 320000, 60000, 30000, 179000, 3580000, 3371000, 'paid', 1, '2024-11-15 10:00:00'),
(9, 11, 2024, 1500000, 150000, 40000, 15000, 83750, 1690000, 1591250, 'paid', 1, '2024-11-15 10:00:00'),
(12, 11, 2024, 3000000, 300000, 80000, 35000, 167250, 3380000, 3177750, 'paid', 1, '2024-11-15 10:00:00'),
(13, 11, 2024, 4000000, 400000, 150000, 55000, 224750, 4550000, 4270250, 'paid', 1, '2024-11-15 10:00:00'),
(14, 11, 2024, 2800000, 280000, 120000, 30000, 158500, 3200000, 3011500, 'paid', 1, '2024-11-15 10:00:00'),
(15, 11, 2024, 3500000, 350000, 80000, 40000, 196500, 3930000, 3693500, 'paid', 1, '2024-11-15 10:00:00');

-- Clear existing permissions and insert comprehensive role-based permissions
DELETE FROM user_permissions;

-- Super Admin permissions (full access)
SET @super_admin_id = (SELECT id FROM users WHERE role = 'super_admin' LIMIT 1);
INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@super_admin_id, 'dashboard', 'lihat'),
(@super_admin_id, 'dashboard', 'export'),
(@super_admin_id, 'employees', 'lihat'),
(@super_admin_id, 'employees', 'tambah'),
(@super_admin_id, 'employees', 'edit'),
(@super_admin_id, 'employees', 'hapus'),
(@super_admin_id, 'employees', 'export'),
(@super_admin_id, 'employees', 'import'),
(@super_admin_id, 'work_period', 'lihat'),
(@super_admin_id, 'work_period', 'export'),
(@super_admin_id, 'leave_period', 'lihat'),
(@super_admin_id, 'leave_period', 'export'),
(@super_admin_id, 'placement', 'lihat'),
(@super_admin_id, 'placement', 'tambah'),
(@super_admin_id, 'placement', 'edit'),
(@super_admin_id, 'placement', 'hapus'),
(@super_admin_id, 'placement', 'export'),
(@super_admin_id, 'attendance', 'lihat'),
(@super_admin_id, 'attendance', 'tambah'),
(@super_admin_id, 'attendance', 'edit'),
(@super_admin_id, 'attendance', 'hapus'),
(@super_admin_id, 'attendance', 'export'),
(@super_admin_id, 'payroll', 'lihat'),
(@super_admin_id, 'payroll', 'tambah'),
(@super_admin_id, 'payroll', 'edit'),
(@super_admin_id, 'payroll', 'hapus'),
(@super_admin_id, 'payroll', 'approve'),
(@super_admin_id, 'payroll', 'export'),
(@super_admin_id, 'reports', 'lihat'),
(@super_admin_id, 'reports', 'export'),
(@super_admin_id, 'settings', 'lihat'),
(@super_admin_id, 'settings', 'edit'),
(@super_admin_id, 'settings', 'backup'),
(@super_admin_id, 'settings', 'restore'),
(@super_admin_id, 'user_management', 'lihat'),
(@super_admin_id, 'user_management', 'tambah'),
(@super_admin_id, 'user_management', 'edit'),
(@super_admin_id, 'user_management', 'hapus'),
(@super_admin_id, 'system_admin', 'lihat'),
(@super_admin_id, 'system_admin', 'backup'),
(@super_admin_id, 'system_admin', 'restore'),
(@super_admin_id, 'system_admin', 'logs'),
(@super_admin_id, 'system_admin', 'maintenance');

-- Admin permissions (management without system admin)
SET @admin_id = (SELECT id FROM users WHERE email = 'admin@chickdream.com');
INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@admin_id, 'dashboard', 'lihat'),
(@admin_id, 'dashboard', 'export'),
(@admin_id, 'employees', 'lihat'),
(@admin_id, 'employees', 'tambah'),
(@admin_id, 'employees', 'edit'),
(@admin_id, 'employees', 'hapus'),
(@admin_id, 'employees', 'export'),
(@admin_id, 'work_period', 'lihat'),
(@admin_id, 'work_period', 'export'),
(@admin_id, 'leave_period', 'lihat'),
(@admin_id, 'leave_period', 'export'),
(@admin_id, 'placement', 'lihat'),
(@admin_id, 'placement', 'tambah'),
(@admin_id, 'placement', 'edit'),
(@admin_id, 'placement', 'hapus'),
(@admin_id, 'placement', 'export'),
(@admin_id, 'attendance', 'lihat'),
(@admin_id, 'attendance', 'tambah'),
(@admin_id, 'attendance', 'edit'),
(@admin_id, 'attendance', 'hapus'),
(@admin_id, 'attendance', 'export'),
(@admin_id, 'payroll', 'lihat'),
(@admin_id, 'payroll', 'tambah'),
(@admin_id, 'payroll', 'edit'),
(@admin_id, 'payroll', 'approve'),
(@admin_id, 'payroll', 'export'),
(@admin_id, 'reports', 'lihat'),
(@admin_id, 'reports', 'export'),
(@admin_id, 'settings', 'lihat'),
(@admin_id, 'settings', 'edit'),
(@admin_id, 'user_management', 'lihat'),
(@admin_id, 'user_management', 'tambah'),
(@admin_id, 'user_management', 'edit');

-- HR permissions (HR operations without delete)
SET @hr_id = (SELECT id FROM users WHERE email = 'hr@chickdream.com');
INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@hr_id, 'dashboard', 'lihat'),
(@hr_id, 'employees', 'lihat'),
(@hr_id, 'employees', 'tambah'),
(@hr_id, 'employees', 'edit'),
(@hr_id, 'employees', 'export'),
(@hr_id, 'work_period', 'lihat'),
(@hr_id, 'work_period', 'export'),
(@hr_id, 'leave_period', 'lihat'),
(@hr_id, 'leave_period', 'export'),
(@hr_id, 'placement', 'lihat'),
(@hr_id, 'placement', 'tambah'),
(@hr_id, 'placement', 'edit'),
(@hr_id, 'placement', 'export'),
(@hr_id, 'attendance', 'lihat'),
(@hr_id, 'attendance', 'tambah'),
(@hr_id, 'attendance', 'edit'),
(@hr_id, 'attendance', 'export'),
(@hr_id, 'payroll', 'lihat'),
(@hr_id, 'payroll', 'tambah'),
(@hr_id, 'payroll', 'edit'),
(@hr_id, 'payroll', 'export'),
(@hr_id, 'reports', 'lihat'),
(@hr_id, 'reports', 'export');

-- Manager permissions (read-only for reporting)
SET @manager_id = (SELECT id FROM users WHERE email = 'manager@chickdream.com');
INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@manager_id, 'dashboard', 'lihat'),
(@manager_id, 'employees', 'lihat'),
(@manager_id, 'employees', 'export'),
(@manager_id, 'work_period', 'lihat'),
(@manager_id, 'work_period', 'export'),
(@manager_id, 'leave_period', 'lihat'),
(@manager_id, 'leave_period', 'export'),
(@manager_id, 'placement', 'lihat'),
(@manager_id, 'placement', 'export'),
(@manager_id, 'attendance', 'lihat'),
(@manager_id, 'attendance', 'export'),
(@manager_id, 'reports', 'lihat'),
(@manager_id, 'reports', 'export');

-- Employee permissions (minimal access)
SET @employee_id = (SELECT id FROM users WHERE email = 'employee@chickdream.com');
INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@employee_id, 'dashboard', 'lihat'),
(@employee_id, 'attendance', 'lihat');

-- Insert system settings
INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'PT. CHICKDREAM ABADI WONOSOBO', 'text', 'Nama perusahaan'),
('company_address', 'Jl. Raya Wonosobo No. 123, Wonosobo, Jawa Tengah', 'text', 'Alamat perusahaan'),
('company_phone', '0286-123456', 'text', 'Nomor telepon perusahaan'),
('company_email', 'info@chickdream.com', 'email', 'Email perusahaan'),
('working_hours_start', '08:00', 'text', 'Jam kerja mulai'),
('working_hours_end', '17:00', 'text', 'Jam kerja selesai'),
('overtime_rate', '1.5', 'number', 'Rate lembur (per jam)'),
('tax_rate', '5', 'number', 'Persentase pajak (%)'),
('allowance_rate', '10', 'number', 'Persentase tunjangan (%)'),
('backup_auto', '1', 'boolean', 'Backup otomatis'),
('max_late_minutes', '15', 'number', 'Maksimal terlambat (menit)');

-- Update employee IDs for existing records
UPDATE attendance SET employee_id = (SELECT id FROM employees WHERE employees.nip = attendance.nip);
UPDATE placements p JOIN employees e ON e.nip IN ('EMP001', 'EMP002', 'EMP003', 'EMP004', 'EMP005', 'EMP006', 'EMP007', 'EMP008', 'EMP009', 'EMP012', 'EMP013', 'EMP014', 'EMP015') 
SET p.employee_id = e.id 
WHERE p.employee_id IN (1,2,3,4,5,6,7,8,9,12,13,14,15) 
AND e.id = p.employee_id;
('admin2@chickdream.com', MD5('admin123'), 'Administrator', 'admin', 'active');

-- Get admin user ID and assign permissions
SET @admin_id = (SELECT id FROM users WHERE email = 'admin2@chickdream.com');

INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES
(@admin_id, 'dashboard', 'lihat'),
(@admin_id, 'employees', 'lihat'),
(@admin_id, 'employees', 'tambah'),
(@admin_id, 'employees', 'edit'),
(@admin_id, 'employees', 'hapus'),
(@admin_id, 'employees', 'export'),
(@admin_id, 'work_period', 'lihat'),
(@admin_id, 'work_period', 'edit'),
(@admin_id, 'work_period', 'export'),
(@admin_id, 'leave_period', 'lihat'),
(@admin_id, 'leave_period', 'edit'),
(@admin_id, 'leave_period', 'export'),
(@admin_id, 'placement', 'lihat'),
(@admin_id, 'placement', 'tambah'),
(@admin_id, 'placement', 'edit'),
(@admin_id, 'placement', 'hapus'),
(@admin_id, 'placement', 'export'),
(@admin_id, 'attendance', 'lihat'),
(@admin_id, 'attendance', 'tambah'),
(@admin_id, 'attendance', 'edit'),
(@admin_id, 'attendance', 'hapus'),
(@admin_id, 'attendance', 'export'),
(@admin_id, 'payroll', 'lihat'),
(@admin_id, 'payroll', 'tambah'),
(@admin_id, 'payroll', 'edit'),
(@admin_id, 'payroll', 'approve'),
(@admin_id, 'payroll', 'export'),
(@admin_id, 'reports', 'lihat'),
(@admin_id, 'reports', 'tambah'),
(@admin_id, 'reports', 'edit'),
(@admin_id, 'reports', 'export'),
(@admin_id, 'settings', 'lihat'),
(@admin_id, 'settings', 'edit'),
(@admin_id, 'user_management', 'lihat'),
(@admin_id, 'user_management', 'tambah'),
(@admin_id, 'user_management', 'edit'),
(@admin_id, 'user_management', 'hapus');
