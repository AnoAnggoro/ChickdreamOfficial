<?php
require_once 'functions.php';
requireLogin();
requirePermission('user_management', 'lihat');

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_user'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $name = $_POST['name'];
        $role = $_POST['role'];
        
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role) VALUES (?, MD5(?), ?, ?)");
        if ($stmt->execute([$email, $password, $name, $role])) {
            $userId = $pdo->lastInsertId();
            
            // Assign permissions based on role
            assignDefaultPermissions($userId, $role);
            
            header('Location: permissions.php?success=1');
            exit;
        }
    }
    
    if (isset($_POST['update_permissions'])) {
        $userId = $_POST['user_id'];
        $permissions = $_POST['permissions'] ?? [];
        
        global $pdo;
        // Clear existing permissions
        $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
        
        // Insert new permissions
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, module, permission) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $module, $action]);
            }
        }
        
        header('Location: permissions.php?success=2');
        exit;
    }
    
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
        if ($stmt->execute([$userId])) {
            header('Location: permissions.php?success=3');
            exit;
        }
    }
}

function assignDefaultPermissions($userId, $role) {
    global $pdo;
    
    $defaultPermissions = [
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
    
    $permissions = $defaultPermissions[$role] ?? [];
    
    foreach ($permissions as $module => $actions) {
        foreach ($actions as $action) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_permissions (user_id, module, permission) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $module, $action]);
        }
    }
}

// Get all users
global $pdo;
$users = $pdo->query("SELECT * FROM users ORDER BY role, name")->fetchAll();

// Get available permissions
$menuPermissions = getMenuPermissions();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission & Users - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php echo renderSidebar('permissions'); ?>
        
        <div class="main-content">
            <?php echo renderTopBar('Permission & Users'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        $messages = [
                            1 => 'User berhasil ditambahkan!',
                            2 => 'Permission berhasil diperbarui!',
                            3 => 'User berhasil dihapus!'
                        ];
                        echo $messages[$_GET['success']];
                        ?>
                    </div>
                <?php endif; ?>

                <!-- User Management -->
                <div class="data-table">
                    <div class="section-header">
                        <h4>Manajemen User & Permission</h4>
                        <div class="table-controls">
                            <?php if (hasPermission('user_management', 'tambah')): ?>
                                <button class="btn-add" onclick="openModal('addUserModal')">Tambah User</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Permissions</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <span class="role-<?php echo $user['role']; ?>">
                                        <?php 
                                        $roleLabels = [
                                            'super_admin' => 'Super Admin',
                                            'admin' => 'Administrator',
                                            'hr' => 'HR Manager',
                                            'manager' => 'Manager',
                                            'employee' => 'Employee'
                                        ];
                                        echo $roleLabels[$user['role']] ?? ucfirst($user['role']); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status'] == 'active' ? 'hadir' : 'alpha'; ?>">
                                        <?php echo $user['status'] == 'active' ? 'Aktif' : 'Tidak Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT module) as module_count FROM user_permissions WHERE user_id = ?");
                                    $stmt->execute([$user['id']]);
                                    $permCount = $stmt->fetchColumn();
                                    echo $permCount . ' modules';
                                    ?>
                                </td>
                                <td>
                                    <button onclick="viewUser(<?php echo $user['id']; ?>)" class="btn-action btn-view">Detail</button>
                                    <?php if (hasPermission('user_management', 'edit')): ?>
                                        <button onclick="editPermissions(<?php echo $user['id']; ?>)" class="btn-action btn-edit">Permissions</button>
                                    <?php endif; ?>
                                    <?php if (hasPermission('user_management', 'hapus') && $user['role'] != 'super_admin'): ?>
                                        <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-action btn-delete">Hapus</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Tambah User Baru</h4>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="employee">Employee</option>
                                <option value="manager">Manager</option>
                                <option value="hr">HR Manager</option>
                                <option value="admin">Administrator</option>
                                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                                    <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_user" class="btn-primary">Tambah User</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('addUserModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Permissions Modal -->
    <div id="permissionsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h4>Edit Permissions</h4>
                <span class="close" onclick="closeModal('permissionsModal')">&times;</span>
            </div>
            <form method="POST" id="permissionsForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div id="permissionsGrid" class="permission-grid">
                        <!-- Permissions will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_permissions" class="btn-primary">Update Permissions</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('permissionsModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Define menu permissions in JavaScript
        const menuPermissions = <?php echo json_encode($menuPermissions); ?>;
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function viewUser(userId) {
            fetch(`get_user.php?id=${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const permissions = data.permissions.reduce((acc, perm) => {
                        if (!acc[perm.module]) acc[perm.module] = [];
                        acc[perm.module].push(perm.permission);
                        return acc;
                    }, {});
                    
                    let permissionsList = '';
                    for (const [module, actions] of Object.entries(permissions)) {
                        permissionsList += `${module}: ${actions.join(', ')}\n`;
                    }
                    
                    alert(`User: ${data.name}\nEmail: ${data.email}\nRole: ${data.role}\n\nPermissions:\n${permissionsList || 'Tidak ada permissions'}`);
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading user data: ' + error.message);
                });
        }
        
        function editPermissions(userId) {
            fetch(`get_user.php?id=${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    document.getElementById('edit_user_id').value = userId;
                    
                    const permissions = data.permissions.reduce((acc, perm) => {
                        if (!acc[perm.module]) acc[perm.module] = [];
                        acc[perm.module].push(perm.permission);
                        return acc;
                    }, {});
                    
                    let html = '';
                    
                    for (const [module, config] of Object.entries(menuPermissions)) {
                        html += `<div class="permission-module">
                            <h6>${config.title}</h6>
                            <div class="permission-actions">`;
                        
                        for (const action of config.permissions) {
                            const checked = permissions[module] && permissions[module].includes(action) ? 'checked' : '';
                            html += `<label class="permission-item">
                                <input type="checkbox" name="permissions[${module}][]" value="${action}" ${checked}>
                                <span>${action}</span>
                            </label>`;
                        }
                        
                        html += `</div></div>`;
                    }
                    
                    document.getElementById('permissionsGrid').innerHTML = html;
                    openModal('permissionsModal');
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading user data: ' + error.message);
                });
        }
        
        function deleteUser(userId) {
            if (confirm('Yakin ingin menghapus user ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="delete_user" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Test if menuPermissions is loaded correctly
        console.log('Menu permissions loaded:', menuPermissions);
    </script>

    <style>
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .permission-module {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 15px;
            background: var(--surface-hover);
        }
        
        .permission-module h6 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .permission-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .permission-item:hover {
            background: rgba(79, 70, 229, 0.1);
        }
        
        .permission-item input[type="checkbox"] {
            margin: 0;
        }
        
        .permission-item span {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .role-super_admin { 
            background: linear-gradient(135deg, #7c3aed, #a855f7); 
            color: white; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .role-admin { 
            background: linear-gradient(135deg, #ef4444, #f87171); 
            color: white; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .role-hr { 
            background: linear-gradient(135deg, #3b82f6, #60a5fa); 
            color: white; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .role-manager { 
            background: linear-gradient(135deg, #f59e0b, #fbbf24); 
            color: white; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .role-employee { 
            background: linear-gradient(135deg, #10b981, #34d399); 
            color: white; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
    </style>
</body>
</html>
           