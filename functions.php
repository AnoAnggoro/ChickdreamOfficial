<?php
session_start();
require_once 'config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = MD5(?)");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function getTotalEmployees() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
    return $stmt->fetchColumn();
}

function getTodayAttendance() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'hadir'");
    return $stmt->fetchColumn();
}

function getTodaySick() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'izin'");
    return $stmt->fetchColumn();
}

function getTodayAbsent() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'alpha'");
    return $stmt->fetchColumn();
}

function getTodayAttendanceList() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT a.*, e.name 
        FROM attendance a 
        LEFT JOIN employees e ON a.nip = e.nip 
        WHERE a.date = CURDATE() 
        ORDER BY a.check_in ASC
    ");
    return $stmt->fetchAll();
}

function updateGracePeriodBasedOnEmploymentType() {
    global $pdo;
    
    // Set grace_period to NULL for permanent employees
    $pdo->exec("UPDATE employees SET grace_period = NULL WHERE employment_type = 'permanent'");
    
    // Set grace_period for contract employees (1 year from hire_date)
    $pdo->exec("UPDATE employees SET grace_period = DATE_ADD(hire_date, INTERVAL 1 YEAR) WHERE employment_type = 'contract' AND grace_period IS NULL");
    
    // Set grace_period for intern employees (6 months from hire_date)
    $pdo->exec("UPDATE employees SET grace_period = DATE_ADD(hire_date, INTERVAL 6 MONTH) WHERE employment_type = 'intern' AND grace_period IS NULL");
}

function checkAndAddColumns() {
    global $pdo;
    
    // Check and add grace_period column
    try {
        $pdo->query("SELECT grace_period FROM employees LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN grace_period DATE DEFAULT NULL");
    }
    
    // Check and add employment_type column
    try {
        $pdo->query("SELECT employment_type FROM employees LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN employment_type ENUM('permanent', 'contract', 'intern') DEFAULT 'permanent'");
    }
    
    // Check and add monthly_salary column
    try {
        $pdo->query("SELECT monthly_salary FROM employees LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN monthly_salary DECIMAL(12,2) DEFAULT 0");
        $pdo->exec("UPDATE employees SET monthly_salary = salary WHERE monthly_salary = 0");
    }
    
    // Update status enum to include new values
    try {
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'terminated', 'resigned') DEFAULT 'active'");
    } catch (PDOException $e) {
        // Column might already be updated
    }
    
    // Check and add other missing columns
    $columns = [
        'salary' => 'DECIMAL(12,2) DEFAULT 0',
        'address' => 'TEXT',
        'photo' => 'VARCHAR(255)',
        'birth_place' => 'VARCHAR(100)',
        'birth_date' => 'DATE',
        'work_period' => 'VARCHAR(50)'
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            $pdo->query("SELECT $column FROM employees LIMIT 1");
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE employees ADD COLUMN $column $definition");
        }
    }
    
    // Update grace periods based on employment type
    updateGracePeriodBasedOnEmploymentType();
}

function getEmployeeStats() {
    global $pdo;
    
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'inactive'")->fetchColumn(),
        'terminated' => $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'terminated'")->fetchColumn(),
        'resigned' => $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'resigned'")->fetchColumn(),
        'permanent' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'permanent'")->fetchColumn(),
        'contract' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'contract'")->fetchColumn(),
        'intern' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'intern'")->fetchColumn()
    ];
    
    return $stats;
}

function getDashboardStats() {
    global $pdo;
    
    $stats = [
        // Employee stats
        'total_employees' => $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn(),
        'active_employees' => $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn(),
        'permanent_employees' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'permanent' AND status = 'active'")->fetchColumn(),
        'contract_employees' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'contract' AND status = 'active'")->fetchColumn(),
        'intern_employees' => $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_type = 'intern' AND status = 'active'")->fetchColumn(),
        
        // Contract specific stats
        'contract_active' => $pdo->query("
            SELECT COUNT(*) FROM employees 
            WHERE employment_type = 'contract' 
            AND status = 'active'
            AND grace_period IS NOT NULL 
            AND DATEDIFF(grace_period, CURDATE()) > 0
        ")->fetchColumn(),
        
        'contract_expired' => $pdo->query("
            SELECT COUNT(*) FROM employees 
            WHERE employment_type = 'contract' 
            AND grace_period IS NOT NULL 
            AND DATEDIFF(grace_period, CURDATE()) <= 0
        ")->fetchColumn(),
        
        'contract_ending_soon' => $pdo->query("
            SELECT COUNT(*) FROM employees 
            WHERE employment_type IN ('contract', 'intern') 
            AND status = 'active'
            AND grace_period IS NOT NULL 
            AND DATEDIFF(grace_period, CURDATE()) BETWEEN 1 AND 30
        ")->fetchColumn(),
        
        // Grace period stats (kontrak & magang yang mendekati batas)
        'grace_period_critical' => $pdo->query("
            SELECT COUNT(*) FROM employees 
            WHERE employment_type IN ('contract', 'intern') 
            AND status = 'active'
            AND grace_period IS NOT NULL 
            AND DATEDIFF(grace_period, CURDATE()) BETWEEN 0 AND 30
        ")->fetchColumn(),
        
        'grace_period_warning' => $pdo->query("
            SELECT COUNT(*) FROM employees 
            WHERE employment_type IN ('contract', 'intern') 
            AND status = 'active'
            AND grace_period IS NOT NULL 
            AND DATEDIFF(grace_period, CURDATE()) BETWEEN 31 AND 60
        ")->fetchColumn(),
        
        // Placement stats
        'active_placements' => $pdo->query("SELECT COUNT(*) FROM placements WHERE status = 'active'")->fetchColumn(),
        'temporary_placements' => $pdo->query("SELECT COUNT(*) FROM placements WHERE status = 'active' AND placement_type != 'permanent'")->fetchColumn(),
        'placement_ending_soon' => $pdo->query("
            SELECT COUNT(*) FROM placements 
            WHERE status = 'active' 
            AND duration_months IS NOT NULL 
            AND DATEDIFF(DATE_ADD(start_date, INTERVAL duration_months MONTH), CURDATE()) BETWEEN 0 AND 30
        ")->fetchColumn(),
        
        // Today attendance stats
        'today_present' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'hadir'")->fetchColumn(),
        'today_sick' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'sakit'")->fetchColumn(),
        'today_permit' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'izin'")->fetchColumn(),
        'today_absent' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'alpha'")->fetchColumn(),
        
        // Monthly attendance stats
        'month_present' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND status = 'hadir'")->fetchColumn(),
        'month_sick' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND status = 'sakit'")->fetchColumn(),
        'month_permit' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND status = 'izin'")->fetchColumn(),
        'month_absent' => $pdo->query("SELECT COUNT(*) FROM attendance WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND status = 'alpha'")->fetchColumn(),
    ];
    
    return $stats;
}

function getRecentActivities() {
    global $pdo;
    
    // Get recent attendance (last 5)
    $recent_attendance = $pdo->query("
        SELECT a.*, e.name, 'attendance' as type
        FROM attendance a 
        LEFT JOIN employees e ON a.nip = e.nip 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Get recent placements (last 5)
    $recent_placements = $pdo->query("
        SELECT p.*, e.name, 'placement' as type
        FROM placements p 
        LEFT JOIN employees e ON p.employee_id = e.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Get employees with critical grace period
    $critical_grace = $pdo->query("
        SELECT e.*, 'grace_period' as type,
               DATEDIFF(e.grace_period, CURDATE()) as days_remaining
        FROM employees e 
        WHERE e.employment_type IN ('contract', 'intern') 
        AND e.status = 'active'
        AND e.grace_period IS NOT NULL 
        AND DATEDIFF(e.grace_period, CURDATE()) BETWEEN 0 AND 30
        ORDER BY e.grace_period ASC
        LIMIT 5
    ")->fetchAll();
    
    return [
        'attendance' => $recent_attendance,
        'placements' => $recent_placements,
        'critical_grace' => $critical_grace
    ];
}

function getDashboardInfo() {
    global $pdo;
    
    $info = [
        // Employee Info
        'newest_employees' => $pdo->query("
            SELECT name, position, department, hire_date 
            FROM employees 
            WHERE status = 'active' 
            ORDER BY hire_date DESC 
            LIMIT 3
        ")->fetchAll(),
        
        'birthday_this_month' => $pdo->query("
            SELECT name, birth_date, department, position
            FROM employees 
            WHERE MONTH(birth_date) = MONTH(CURDATE()) 
            AND status = 'active'
            ORDER BY DAY(birth_date) ASC
            LIMIT 5
        ")->fetchAll(),
        
        'work_anniversary' => $pdo->query("
            SELECT name, hire_date, department, position,
                   YEAR(CURDATE()) - YEAR(hire_date) as years_worked
            FROM employees 
            WHERE MONTH(hire_date) = MONTH(CURDATE()) 
            AND DAY(hire_date) = DAY(CURDATE())
            AND status = 'active'
            LIMIT 5
        ")->fetchAll(),
        
        // Grace Period Info
        'expiring_contracts' => $pdo->query("
            SELECT name, nip, grace_period, employment_type, department,
                   DATEDIFF(grace_period, CURDATE()) as days_remaining
            FROM employees 
            WHERE employment_type IN ('contract', 'intern')
            AND grace_period IS NOT NULL
            AND DATEDIFF(grace_period, CURDATE()) BETWEEN 0 AND 90
            AND status = 'active'
            ORDER BY grace_period ASC
            LIMIT 5
        ")->fetchAll(),
        
        // Attendance Info
        'absent_today' => $pdo->query("
            SELECT e.name, e.nip, e.department, a.status
            FROM employees e
            LEFT JOIN attendance a ON e.nip = a.nip AND a.date = CURDATE()
            WHERE e.status = 'active' 
            AND (a.status IN ('sakit', 'izin', 'alpha') OR a.id IS NULL)
            ORDER BY e.name
            LIMIT 10
        ")->fetchAll(),
        
        'late_attendance' => $pdo->query("
            SELECT e.name, e.nip, a.check_in, a.date
            FROM attendance a
            JOIN employees e ON a.nip = e.nip
            WHERE a.check_in > '09:00:00'
            AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND a.status = 'hadir'
            ORDER BY a.date DESC, a.check_in DESC
            LIMIT 5
        ")->fetchAll(),
        
        // Placement Info
        'recent_placements' => $pdo->query("
            SELECT e.name, p.location, p.placement_type, p.start_date, p.duration_months
            FROM placements p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 5
        ")->fetchAll(),
        
        'ending_placements' => $pdo->query("
            SELECT e.name, p.location, p.placement_type, p.start_date, p.duration_months,
                   DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH) as end_date,
                   DATEDIFF(DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
            FROM placements p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.status = 'active'
            AND p.duration_months IS NOT NULL
            AND DATEDIFF(DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH), CURDATE()) BETWEEN 0 AND 30
            ORDER BY end_date ASC
            LIMIT 5
        ")->fetchAll(),
        
        // Department Statistics
        'department_stats' => $pdo->query("
            SELECT department, 
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                   SUM(CASE WHEN employment_type = 'permanent' THEN 1 ELSE 0 END) as permanent_count,
                   SUM(CASE WHEN employment_type = 'contract' THEN 1 ELSE 0 END) as contract_count,
                   SUM(CASE WHEN employment_type = 'intern' THEN 1 ELSE 0 END) as intern_count
            FROM employees 
            GROUP BY department
            ORDER BY total DESC
        ")->fetchAll(),
        
        // Monthly Statistics
        'monthly_attendance_stats' => $pdo->query("
            SELECT 
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha,
                COUNT(*) as total
            FROM attendance 
            WHERE MONTH(date) = MONTH(CURDATE()) 
            AND YEAR(date) = YEAR(CURDATE())
        ")->fetch(),
        
        // System Activity
        'recent_activity' => $pdo->query("
            SELECT 'employee' as type, name as title, 'Pegawai baru ditambahkan' as description, created_at
            FROM employees 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT 'attendance' as type, CONCAT('Absensi ', nip) as title, CONCAT('Status: ', status) as description, created_at
            FROM attendance 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
            UNION ALL
            SELECT 'placement' as type, 'Penempatan baru' as title, location as description, created_at
            FROM placements 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
            LIMIT 10
        ")->fetchAll(),
        
        // Contract & Placement specific info
        'expired_contracts' => $pdo->query("
            SELECT name, nip, grace_period, employment_type, department,
                   ABS(DATEDIFF(grace_period, CURDATE())) as days_expired
            FROM employees 
            WHERE employment_type IN ('contract', 'intern')
            AND grace_period IS NOT NULL
            AND DATEDIFF(grace_period, CURDATE()) <= 0
            ORDER BY grace_period DESC
            LIMIT 5
        ")->fetchAll(),
        
        'active_contracts' => $pdo->query("
            SELECT name, nip, grace_period, employment_type, department,
                   DATEDIFF(grace_period, CURDATE()) as days_remaining
            FROM employees 
            WHERE employment_type = 'contract'
            AND status = 'active'
            AND grace_period IS NOT NULL
            AND DATEDIFF(grace_period, CURDATE()) > 30
            ORDER BY grace_period ASC
            LIMIT 5
        ")->fetchAll(),
        
        'placements_ending_soon' => $pdo->query("
            SELECT e.name, p.location, p.placement_type, p.start_date, p.duration_months,
                   DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH) as end_date,
                   DATEDIFF(DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH), CURDATE()) as days_remaining
            FROM placements p
            JOIN employees e ON p.employee_id = e.id
            WHERE p.status = 'active'
            AND p.duration_months IS NOT NULL
            AND DATEDIFF(DATE_ADD(p.start_date, INTERVAL p.duration_months MONTH), CURDATE()) BETWEEN 0 AND 30
            ORDER BY end_date ASC
            LIMIT 5
        ")->fetchAll(),
    ];
    
    return $info;
}

function hasPermission($module, $permission) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Super Admin has all permissions
    if ($_SESSION['user_role'] === 'super_admin') {
        return true;
    }
    
    // Admin has most permissions except system admin functions
    if ($_SESSION['user_role'] === 'admin' && $module !== 'system_admin') {
        return true;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM user_permissions WHERE user_id = ? AND module = ? AND permission = ?");
    $stmt->execute([$_SESSION['user_id'], $module, $permission]);
    return $stmt->fetch() !== false;
}

function requirePermission($module, $permission) {
    if (!hasPermission($module, $permission)) {
        // For AJAX requests, return JSON error instead of HTML
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You do not have permission to access this resource.']);
            exit;
        }
        
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. You do not have permission to access this resource.');
    }
}

function getUserPermissions($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT module, permission FROM user_permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $permissions = [];
    while ($row = $stmt->fetch()) {
        $permissions[$row['module']][$row['permission']] = 1;
    }
    return $permissions;
}

function getSidebarMenus() {
    $menus = [
        [
            'id' => 'dashboard',
            'icon' => '📊',
            'title' => 'Dashboard',
            'url' => 'dashboard.php',
            'permission_module' => 'dashboard',
            'permission_action' => 'lihat',
            'always_visible' => true
        ],
        [
            'id' => 'employees',
            'icon' => '👥',
            'title' => 'Data Pegawai',
            'url' => 'employees.php',
            'permission_module' => 'employees',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'work-period',
            'icon' => '📅',
            'title' => 'Masa Kerja',
            'url' => 'work-period.php',
            'permission_module' => 'work_period',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'leave-period',
            'icon' => '⏰',
            'title' => 'Masa Tenggang Kerja',
            'url' => 'leave-period.php',
            'permission_module' => 'leave_period',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'placement',
            'icon' => '📍',
            'title' => 'Penempatan Kerja',
            'url' => 'placement.php',
            'permission_module' => 'placement',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'attendance',
            'icon' => '✅',
            'title' => 'Absensi',
            'url' => 'attendance.php',
            'permission_module' => 'attendance',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'payroll',
            'icon' => '💰',
            'title' => 'Payroll',
            'url' => 'payroll.php',
            'permission_module' => 'payroll',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'reports',
            'icon' => '📊',
            'title' => 'Laporan',
            'url' => 'reports.php',
            'permission_module' => 'reports',
            'permission_action' => 'lihat'
        ],
        [
            'id' => 'settings',
            'icon' => '⚙️',
            'title' => 'Pengaturan',
            'url' => 'settings.php',
            'permission_module' => 'settings',
            'permission_action' => 'lihat',
            'admin_only' => true
        ],
        [
            'id' => 'permissions',
            'icon' => '🔐',
            'title' => 'Permission & Users',
            'url' => 'permissions.php',
            'permission_module' => 'user_management',
            'permission_action' => 'lihat',
            'admin_only' => true
        ],
        [
            'id' => 'system-admin',
            'icon' => '🛠️',
            'title' => 'System Administration',
            'url' => 'system-admin.php',
            'permission_module' => 'system_admin',
            'permission_action' => 'lihat',
            'super_admin_only' => true
        ]
    ];
    
    return $menus;
}

function renderSidebar($currentPage = '') {
    $menus = getSidebarMenus();
    $currentPageFile = basename($_SERVER['PHP_SELF']);
    
    ob_start();
    ?>
    <div class="sidebar">
        <div class="company-header">
            <h3>PT. CHICKDREAM</h3>
            <small>ABADI WONOSOBO</small>
        </div>
        
        <ul class="nav-menu">
            <?php foreach ($menus as $menu): ?>
                <?php if (isMenuVisible($menu)): ?>
                    <li>
                        <a href="<?php echo $menu['url']; ?>" 
                           class="<?php echo (strpos($currentPageFile, $menu['id']) !== false || $currentPage === $menu['id']) ? 'active' : ''; ?>">
                            <span class="icon"><?php echo $menu['icon']; ?></span>
                            <span><?php echo $menu['title']; ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <li class="logout-item">
                <a href="logout.php">
                    <span class="icon">🚪</span>
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function isMenuVisible($menu) {
    // Always show dashboard
    if (isset($menu['always_visible']) && $menu['always_visible']) {
        return true;
    }
    
    // Check super admin only menus
    if (isset($menu['super_admin_only']) && $menu['super_admin_only']) {
        if ($_SESSION['user_role'] !== 'super_admin') {
            return false;
        }
    }
    
    // Check admin only menus
    if (isset($menu['admin_only']) && $menu['admin_only']) {
        if (!in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            return false;
        }
    }
    
    // Check permission-based access
    if (isset($menu['permission_module']) && isset($menu['permission_action'])) {
        return hasPermission($menu['permission_module'], $menu['permission_action']);
    }
    
    return true;
}

function getMenuPermissions() {
    return [
        'dashboard' => [
            'title' => 'Dashboard',
            'permissions' => ['lihat', 'export']
        ],
        'employees' => [
            'title' => 'Data Pegawai',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'import', 'approve']
        ],
        'work_period' => [
            'title' => 'Masa Kerja',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'leave_period' => [
            'title' => 'Masa Tenggang Kerja',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'placement' => [
            'title' => 'Penempatan Kerja',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'attendance' => [
            'title' => 'Absensi',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'payroll' => [
            'title' => 'Payroll',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'approve', 'export', 'import']
        ],
        'reports' => [
            'title' => 'Laporan',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'settings' => [
            'title' => 'Pengaturan Sistem',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'backup', 'restore']
        ],
        'user_management' => [
            'title' => 'Manajemen User',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'approve']
        ],
        'system_admin' => [
            'title' => 'System Administration',
            'permissions' => ['lihat', 'tambah', 'edit', 'hapus', 'backup', 'restore', 'logs', 'maintenance']
        ]
    ];
}

function getRoleMenuAccess() {
    return [
        'super_admin' => [
            'dashboard', 'employees', 'work_period', 'leave_period', 
            'placement', 'attendance', 'payroll', 'reports', 
            'settings', 'user_management', 'system_admin'
        ],
        'admin' => [
            'dashboard', 'employees', 'work_period', 'leave_period', 
            'placement', 'attendance', 'payroll', 'reports', 
            'settings', 'user_management'
        ],
        'hr' => [
            'dashboard', 'employees', 'work_period', 'leave_period', 
            'placement', 'attendance', 'payroll', 'reports'
        ],
        'manager' => [
            'dashboard', 'employees', 'work_period', 'leave_period', 
            'placement', 'attendance', 'reports'
        ],
        'employee' => [
            'dashboard', 'attendance'
        ]
    ];
}

// Call this function to ensure all columns exist
checkAndAddColumns();

function getUserDisplayInfo() {
    $userInfo = [
        'name' => $_SESSION['user_name'],
        'role_display' => ''
    ];
    
    switch ($_SESSION['user_role']) {
        case 'super_admin':
            $userInfo['role_display'] = 'Super Administrator';
            break;
        case 'admin':
            $userInfo['role_display'] = 'Administrator';
            break;
        case 'hr':
            $userInfo['role_display'] = 'HR Manager';
            break;
        case 'manager':
            $userInfo['role_display'] = 'Manager';
            break;
        case 'employee':
            $userInfo['role_display'] = 'Employee';
            break;
        default:
            $userInfo['role_display'] = ucfirst($_SESSION['user_role']);
    }
    
    return $userInfo;
}

function renderTopBar($pageTitle = '') {
    $userInfo = getUserDisplayInfo();
    
    ob_start();
    ?>
    <div class="topbar">
        <h4><?php echo $pageTitle; ?></h4>
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($userInfo['name'], 0, 1)); ?></div>
            <div class="user-details">
                <span><?php echo $userInfo['name']; ?></span>
                <small><?php echo $userInfo['role_display']; ?></small>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
