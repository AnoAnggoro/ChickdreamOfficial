<?php
require_once '../config/database.php';

// Function to generate random attendance for past 30 days
function generateAttendanceData($pdo) {
    $employees = $pdo->query("SELECT nip FROM employees WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    
    for ($i = 30; $i >= 1; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Skip weekends
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek >= 6) continue; // Skip Saturday (6) and Sunday (7)
        
        foreach ($employees as $nip) {
            // 85% chance of attendance
            $attendanceChance = rand(1, 100);
            
            if ($attendanceChance <= 85) {
                // Present
                $checkIn = sprintf('%02d:%02d:00', rand(7, 9), rand(0, 59));
                $checkOut = sprintf('%02d:%02d:00', rand(16, 18), rand(0, 59));
                $status = 'hadir';
            } elseif ($attendanceChance <= 92) {
                // Sick leave
                $checkIn = null;
                $checkOut = null;
                $status = 'sakit';
            } elseif ($attendanceChance <= 97) {
                // Permission
                $checkIn = null;
                $checkOut = null;
                $status = 'izin';
            } else {
                // Absent
                $checkIn = null;
                $checkOut = null;
                $status = 'alpha';
            }
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO attendance (nip, date, check_in, check_out, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nip, $date, $checkIn, $checkOut, $status]);
        }
    }
}

// Function to generate payroll for past 6 months
function generatePayrollData($pdo) {
    $employees = $pdo->query("SELECT id, monthly_salary FROM employees WHERE status = 'active'")->fetchAll();
    
    for ($monthsBack = 6; $monthsBack >= 1; $monthsBack--) {
        $month = date('n', strtotime("-$monthsBack months"));
        $year = date('Y', strtotime("-$monthsBack months"));
        
        foreach ($employees as $employee) {
            $basicSalary = $employee['monthly_salary'] * 0.85; // 85% basic, 15% allowances
            $allowances = $employee['monthly_salary'] * 0.15;
            $overtime = rand(0, 500000); // Random overtime
            $deductions = rand(0, 100000); // Random deductions
            
            $grossSalary = $basicSalary + $allowances + $overtime;
            $tax = $grossSalary * 0.05; // 5% tax
            $netSalary = $grossSalary - $tax - $deductions;
            
            $status = ($monthsBack <= 2) ? 'draft' : 'paid'; // Last 2 months are draft
            $approvedBy = ($status == 'paid') ? 1 : null;
            $approvedAt = ($status == 'paid') ? date('Y-m-d H:i:s', strtotime("-$monthsBack months +15 days")) : null;
            
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO payroll 
                (employee_id, period_month, period_year, basic_salary, allowances, overtime, deductions, tax, gross_salary, net_salary, status, approved_by, approved_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employee['id'], $month, $year, $basicSalary, $allowances, $overtime, 
                $deductions, $tax, $grossSalary, $netSalary, $status, $approvedBy, $approvedAt
            ]);
        }
    }
}

// Function to assign role-based permissions
function assignRolePermissions($pdo) {
    // Define permissions for each role
    $rolePermissions = [
        'super_admin' => [
            'dashboard' => ['lihat', 'export'],
            'employees' => ['lihat', 'tambah', 'edit', 'hapus', 'export', 'import'],
            'work_period' => ['lihat', 'export'],
            'leave_period' => ['lihat', 'export'],
            'placement' => ['lihat', 'tambah', 'edit', 'hapus', 'export'],
            'attendance' => ['lihat', 'tambah', 'edit', 'hapus', 'export'],
            'payroll' => ['lihat', 'tambah', 'edit', 'hapus', 'approve', 'export'],
            'reports' => ['lihat', 'export'],
            'settings' => ['lihat', 'edit', 'backup', 'restore'],
            'user_management' => ['lihat', 'tambah', 'edit', 'hapus'],
            'system_admin' => ['lihat', 'backup', 'restore', 'logs', 'maintenance']
        ],
        'admin' => [
            'dashboard' => ['lihat', 'export'],
            'employees' => ['lihat', 'tambah', 'edit', 'hapus', 'export'],
            'work_period' => ['lihat', 'export'],
            'leave_period' => ['lihat', 'export'],
            'placement' => ['lihat', 'tambah', 'edit', 'hapus', 'export'],
            'attendance' => ['lihat', 'tambah', 'edit', 'hapus', 'export'],
            'payroll' => ['lihat', 'tambah', 'edit', 'approve', 'export'],
            'reports' => ['lihat', 'export'],
            'settings' => ['lihat', 'edit'],
            'user_management' => ['lihat', 'tambah', 'edit']
        ],
        'hr' => [
            'dashboard' => ['lihat'],
            'employees' => ['lihat', 'tambah', 'edit', 'export'],
            'work_period' => ['lihat', 'export'],
            'leave_period' => ['lihat', 'export'],
            'placement' => ['lihat', 'tambah', 'edit', 'export'],
            'attendance' => ['lihat', 'tambah', 'edit', 'export'],
            'payroll' => ['lihat', 'tambah', 'edit', 'export'],
            'reports' => ['lihat', 'export']
        ],
        'manager' => [
            'dashboard' => ['lihat'],
            'employees' => ['lihat', 'export'],
            'work_period' => ['lihat', 'export'],
            'leave_period' => ['lihat', 'export'],
            'placement' => ['lihat', 'export'],
            'attendance' => ['lihat', 'export'],
            'reports' => ['lihat', 'export']
        ],
        'employee' => [
            'dashboard' => ['lihat'],
            'attendance' => ['lihat']
        ]
    ];
    
    // Clear existing permissions
    $pdo->exec("DELETE FROM user_permissions");
    
    // Get all users
    $users = $pdo->query("SELECT id, role FROM users")->fetchAll();
    
    foreach ($users as $user) {
        $permissions = $rolePermissions[$user['role']] ?? [];
        
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $module, $action]);
            }
        }
    }
    
    echo "Role-based permissions assigned successfully!\n";
}

try {
    echo "Generating attendance data...\n";
    generateAttendanceData($pdo);
    
    echo "Generating payroll data...\n";
    generatePayrollData($pdo);
    
    echo "Assigning role-based permissions...\n";
    assignRolePermissions($pdo);
    
    echo "Sample data generated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
