<?php
require_once 'functions.php';
requireLogin();

// Handle actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle search and filters for employee list
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$employment_filter = $_GET['employment'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_employee'])) {
        $nip = $_POST['nip'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $department = $_POST['department'];
        $position = $_POST['position'];
        $hire_date = $_POST['hire_date'];
        $salary = $_POST['salary'];
        $monthly_salary = $_POST['monthly_salary'];
        $address = $_POST['address'];
        $status = $_POST['status'];
        $employment_type = $_POST['employment_type'];
        $birth_place = $_POST['birth_place'];
        $birth_date = $_POST['birth_date'];
        $grace_period = $_POST['grace_period'];
        
        // Handle photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $uploadDir = 'uploads/photos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $photo = $uploadDir . time() . '_' . $_FILES['photo']['name'];
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }
        
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO employees (nip, name, email, phone, department, position, hire_date, salary, monthly_salary, address, photo, status, employment_type, birth_place, birth_date, grace_period) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nip, $name, $email, $phone, $department, $position, $hire_date, $salary, $monthly_salary, $address, $photo, $status, $employment_type, $birth_place, $birth_date, $grace_period])) {
            header('Location: employees.php?success=1');
            exit;
        }
    }
    
    if (isset($_POST['edit_employee'])) {
        $id = $_POST['id'];
        $nip = $_POST['nip'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $department = $_POST['department'];
        $position = $_POST['position'];
        $hire_date = $_POST['hire_date'];
        $salary = $_POST['salary'];
        $monthly_salary = $_POST['monthly_salary'];
        $address = $_POST['address'];
        $status = $_POST['status'];
        $employment_type = $_POST['employment_type'];
        $birth_place = $_POST['birth_place'];
        $birth_date = $_POST['birth_date'];
        $grace_period = $_POST['grace_period'];
        
        global $pdo;
        $stmt = $pdo->prepare("UPDATE employees SET nip=?, name=?, email=?, phone=?, department=?, position=?, hire_date=?, salary=?, monthly_salary=?, address=?, status=?, employment_type=?, birth_place=?, birth_date=?, grace_period=? WHERE id=?");
        if ($stmt->execute([$nip, $name, $email, $phone, $department, $position, $hire_date, $salary, $monthly_salary, $address, $status, $employment_type, $birth_place, $birth_date, $grace_period, $id])) {
            header('Location: employees.php?success=2');
            exit;
        }
    }
}

// Handle delete
if ($action == 'delete' && $id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Location: employees.php?success=3');
        exit;
    }
}

// Build query with search and filters
global $pdo;
$sql = "SELECT * FROM employees WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (nip LIKE ? OR name LIKE ? OR position LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($department_filter) {
    $sql .= " AND department = ?";
    $params[] = $department_filter;
}

if ($employment_filter) {
    $sql .= " AND employment_type = ?";
    $params[] = $employment_filter;
}

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Get departments for filter
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Get single employee for view/edit
$employee = null;
if (($action == 'view' || $action == 'edit') && $id) {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pegawai - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('employees'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php echo renderTopBar('Data Pegawai'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success no-print">
                        <?php 
                        $messages = [
                            1 => 'Data pegawai berhasil ditambahkan!',
                            2 => 'Data pegawai berhasil diperbarui!',
                            3 => 'Data pegawai berhasil dihapus!'
                        ];
                        echo $messages[$_GET['success']];
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($action == 'list'): ?>
                    <!-- Print Header -->
                   

                    <!-- Employee List -->
                    <div class="data-table">
                        <div class="section-header no-print">
                            <h4>Data Pegawai</h4>
                            <div class="table-controls">
                                <a href="?action=add" class="btn-add">Tambah Data</a>
                                <button class="btn-add" onclick="window.print()">Print Report</button>
                                <button class="btn-secondary" onclick="resetFilters()">Reset Filter</button>
                            </div>
                        </div>
                        
                        <!-- Search and Filter Section -->
                        <div class="search-filter-section no-print">
                            <form method="GET" class="search-form">
                                <input type="hidden" name="action" value="list">
                                <div class="search-row">
                                    <div class="search-group">
                                        <input type="text" name="search" placeholder="Cari NIP, Nama, Jabatan, atau No HP..." 
                                               value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                                    </div>
                                    <div class="filter-group">
                                        <select name="department" class="filter-select">
                                            <option value="">Semua Departemen</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?php echo $dept; ?>" <?php echo $department_filter == $dept ? 'selected' : ''; ?>>
                                                    <?php echo $dept; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <select name="employment" class="filter-select">
                                            <option value="">Semua Jenis Pekerjaan</option>
                                            <option value="permanent" <?php echo $employment_filter == 'permanent' ? 'selected' : ''; ?>>Tetap</option>
                                            <option value="contract" <?php echo $employment_filter == 'contract' ? 'selected' : ''; ?>>Kontrak</option>
                                            <option value="intern" <?php echo $employment_filter == 'intern' ? 'selected' : ''; ?>>Magang</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <select name="status" class="filter-select">
                                            <option value="">Semua Status</option>
                                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                            <option value="terminated" <?php echo $status_filter == 'terminated' ? 'selected' : ''; ?>>Diberhentikan</option>
                                            <option value="resigned" <?php echo $status_filter == 'resigned' ? 'selected' : ''; ?>>Mengundurkan Diri</option>
                                        </select>
                                    </div>
                                    <div class="search-buttons">
                                        <button type="submit" class="btn-primary">Cari</button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if ($search || $department_filter || $employment_filter || $status_filter): ?>
                            <div class="search-results-info">
                                <p>Ditemukan <?php echo count($employees); ?> data pegawai</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="responsive-table-wrapper">
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIP</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Departemen</th>
                                        <th class="desktop-only">Gaji Bulanan</th>
                                        <th class="tablet-only desktop-only">Status Karyawan</th>
                                        <th class="desktop-only">Jenis Pekerjaan</th>
                                        <th class="tablet-only desktop-only">No HP</th>
                                        <th class="no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="10" style="text-align: center; color: #7f8c8d; padding: 40px;">
                                            <h5>Tidak ada data yang ditemukan</h5>
                                            <p>Coba ubah kriteria pencarian atau filter</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($employees as $index => $emp): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $emp['nip']; ?></td>
                                        <td>
                                            <div class="employee-cell">
                                                <strong><?php echo $emp['name']; ?></strong>
                                                <!-- <small class="mobile-only tablet-only"><?php echo $emp['position']; ?></small> -->
                                                <!-- Jabatan dihapus dari bawah nama -->
                                            </div>
                                        </td>
                                        <td class="desktop-only"><?php echo $emp['position']; ?></td>
                                        <td>
                                            <div class="department-cell">
                                                <?php echo $emp['department']; ?>
                                                <small class="mobile-only">
                                                    <span class="employment-badge employment-<?php echo $emp['employment_type']; ?>">
                                                        <?php 
                                                        $typeLabels = [
                                                            'permanent' => 'Tetap',
                                                            'contract' => 'Kontrak',
                                                            'intern' => 'Magang'
                                                        ];
                                                        echo $typeLabels[$emp['employment_type']] ?? ucfirst($emp['employment_type']); 
                                                        ?>
                                                    </span>
                                                </small>
                                            </div>
                                        </td>
                                        <td class="desktop-only">Rp <?php echo number_format($emp['monthly_salary'] ?: $emp['salary'], 0, ',', '.'); ?></td>
                                        <td class="tablet-only desktop-only">
                                            <span class="status-badge status-<?php echo $emp['status']; ?>">
                                                <?php 
                                                $statusLabels = [
                                                    'active' => 'Aktif',
                                                    'inactive' => 'Tidak Aktif', 
                                                    'terminated' => 'Diberhentikan',
                                                    'resigned' => 'Mengundurkan Diri'
                                                ];
                                                echo $statusLabels[$emp['status']] ?? ucfirst($emp['status']); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="desktop-only">
                                            <span class="employment-badge employment-<?php echo $emp['employment_type']; ?>">
                                                <?php 
                                                $typeLabels = [
                                                    'permanent' => 'Tetap',
                                                    'contract' => 'Kontrak',
                                                    'intern' => 'Magang'
                                                ];
                                                echo $typeLabels[$emp['employment_type']] ?? ucfirst($emp['employment_type']); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="tablet-only desktop-only"><?php echo $emp['phone']; ?></td>
                                        <td class="no-print">
                                            <div class="action-buttons">
                                                <a href="?action=view&id=<?php echo $emp['id']; ?>" class="btn-action btn-view" title="Lihat">Lihat</a>
                                                <a href="?action=edit&id=<?php echo $emp['id']; ?>" class="btn-action btn-edit" title="Edit">Edit</a>
                                                <a href="?action=delete&id=<?php echo $emp['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Print Summary -->
                      

                <?php elseif ($action == 'view' && $employee): ?>
                    <!-- View Employee Detail -->
                    <div class="form-container">
                        <div class="form-header">
                            <a href="employees.php" class="btn-back">← Kembali</a>
                            <h4>Detail Data Pegawai</h4>
                        </div>
                        
                        <!-- Employee Detail -->
                        <div class="employee-detail-grid">
                            <div class="employee-photo-section">
                                <?php if ($employee['photo']): ?>
                                    <div class="photo-container">
                                        <img src="<?php echo $employee['photo']; ?>" alt="Foto Pegawai" class="employee-photo">
                                        <div class="photo-overlay">
                                            <span class="photo-edit-btn">📷</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="no-photo">
                                        <span><?php echo strtoupper(substr($employee['name'], 0, 2)); ?></span>
                                        <div class="photo-overlay">
                                            <span class="photo-edit-btn">📷</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="employee-info">
                                    <h5><?php echo $employee['name']; ?></h5>
                                    <p class="employee-title"><?php echo $employee['position']; ?></p>
                                    <p class="employee-dept"><?php echo $employee['department']; ?></p>
                                    <div class="employee-status-badges">
                                        <span class="status-badge status-<?php echo $employee['status']; ?>">
                                            <?php 
                                            $statusLabels = [
                                                'active' => '✓ Aktif',
                                                'inactive' => '⏸ Tidak Aktif',
                                                'terminated' => '✕ Diberhentikan',
                                                'resigned' => '→ Resign'
                                            ];
                                            echo $statusLabels[$employee['status']] ?? ucfirst($employee['status']); 
                                            ?>
                                        </span>
                                        <span class="employment-badge employment-<?php echo $employee['employment_type']; ?>">
                                            <?php 
                                            $typeLabels = [
                                                'permanent' => '🏢 Tetap',
                                                'contract' => '📝 Kontrak',
                                                'intern' => '🎓 Magang'
                                            ];
                                            echo $typeLabels[$employee['employment_type']] ?? ucfirst($employee['employment_type']); 
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="employee-details-section">
                                <div class="detail-card">
                                    <h6 class="detail-section-title">📋 Informasi Personal</h6>
                                    <div class="detail-row">
                                        <label>🆔 NIP:</label>
                                        <span><?php echo $employee['nip']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>👤 Nama Lengkap:</label>
                                        <span><?php echo $employee['name']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>🎂 Tempat, Tanggal Lahir:</label>
                                        <span><?php echo ($employee['birth_place'] ?: 'Wonosobo') . ', ' . ($employee['birth_date'] ? date('d M Y', strtotime($employee['birth_date'])) : '1 Jun 2023'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>📧 Email:</label>
                                        <span><?php echo $employee['email'] ?: '-'; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>📱 No HP:</label>
                                        <span><?php echo $employee['phone']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>🏠 Alamat:</label>
                                        <span><?php echo $employee['address'] ?: 'H.Tua Wonosobo, Jawa Tengah'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="detail-card">
                                    <h6 class="detail-section-title">💼 Informasi Pekerjaan</h6>
                                    <div class="detail-row">
                                        <label>💼 Jabatan:</label>
                                        <span><?php echo $employee['position']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>🏢 Departemen:</label>
                                        <span><?php echo $employee['department']; ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>📅 Tanggal Masuk:</label>
                                        <span><?php echo date('d M Y', strtotime($employee['hire_date'])); ?></span>
                                    </div>
                                    <?php if ($employee['employment_type'] != 'permanent' && $employee['grace_period']): ?>
                                    <div class="detail-row">
                                        <label>⏰ Masa Tenggang Kerja:</label>
                                        <span class="grace-period-info">
                                            <?php echo date('d M Y', strtotime($employee['grace_period'])); ?>
                                            <?php 
                                            $daysRemaining = (strtotime($employee['grace_period']) - time()) / (60*60*24);
                                            if ($daysRemaining > 0) {
                                                echo "<small class='days-remaining'>(" . ceil($daysRemaining) . " hari lagi)</small>";
                                            } else {
                                                echo "<small class='days-expired'>(Sudah berakhir)</small>";
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-row">
                                        <label>💰 Gaji Bulanan:</label>
                                        <span class="salary-info">Rp <?php echo number_format($employee['monthly_salary'] ?: $employee['salary'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="?action=edit&id=<?php echo $employee['id']; ?>" class="btn-primary">Edit Data</a>
                            <a href="?action=delete&id=<?php echo $employee['id']; ?>" class="btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus Data</a>
                        </div>
                    </div>

                <?php elseif ($action == 'add' || ($action == 'edit' && $employee)): ?>
                    <!-- Add/Edit Employee Form -->
                    <div class="form-container">
                        <div class="form-header">
                            <a href="employees.php" class="btn-back">← Kembali</a>
                            <h4><?php echo $action == 'add' ? 'Tambah Data Pegawai' : 'Edit Data Pegawai'; ?></h4>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="employee-form">
                            <?php if ($action == 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid-compact">
                                <div class="form-left">
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="name">Nama Lengkap <span class="required">*</span></label>
                                            <input type="text" id="name" name="name" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['name'] ?? ''); ?>" 
                                                   required placeholder="Masukkan nama lengkap">
                                        </div>
                                        <div class="form-group">
                                            <label for="nip">NIP <span class="required">*</span></label>
                                            <input type="text" id="nip" name="nip" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['nip'] ?? ''); ?>" 
                                                   required placeholder="Nomor Induk Pegawai">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="birth_place">Tempat Lahir</label>
                                            <input type="text" id="birth_place" name="birth_place" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['birth_place'] ?? 'Wonosobo'); ?>" 
                                                   placeholder="Tempat lahir">
                                        </div>
                                        <div class="form-group">
                                            <label for="birth_date">Tanggal Lahir</label>
                                            <input type="date" id="birth_date" name="birth_date" class="form-control" 
                                                   value="<?php echo $employee['birth_date'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="position">Jabatan <span class="required">*</span></label>
                                            <input type="text" id="position" name="position" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>" 
                                                   required placeholder="Jabatan pegawai">
                                        </div>
                                        <div class="form-group">
                                            <label for="department">Departemen <span class="required">*</span></label>
                                            <select id="department" name="department" class="form-control" required>
                                                <option value="">Pilih Departemen</option>
                                                <option value="IT" <?php echo ($employee['department'] ?? '') == 'IT' ? 'selected' : ''; ?>>IT</option>
                                                <option value="HR" <?php echo ($employee['department'] ?? '') == 'HR' ? 'selected' : ''; ?>>HR</option>
                                                <option value="Finance" <?php echo ($employee['department'] ?? '') == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                                <option value="Production" <?php echo ($employee['department'] ?? '') == 'Production' ? 'selected' : ''; ?>>Production</option>
                                                <option value="Marketing" <?php echo ($employee['department'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="phone">No HP</label>
                                            <input type="tel" id="phone" name="phone" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" 
                                                   placeholder="08xxxxxxxxxx">
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" class="form-control" 
                                                   value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" 
                                                   placeholder="email@domain.com">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="address">Alamat</label>
                                        <textarea id="address" name="address" class="form-control" rows="3" 
                                                  placeholder="Alamat lengkap"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-right">
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="status">Status Pekerja <span class="required">*</span></label>
                                            <select id="status" name="status" class="form-control" required>
                                                <option value="active" <?php echo ($employee['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="inactive" <?php echo ($employee['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                                <option value="terminated" <?php echo ($employee['status'] ?? '') == 'terminated' ? 'selected' : ''; ?>>Diberhentikan</option>
                                                <option value="resigned" <?php echo ($employee['status'] ?? '') == 'resigned' ? 'selected' : ''; ?>>Mengundurkan Diri</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="employment_type">Jenis Pekerjaan <span class="required">*</span></label>
                                            <select id="employment_type" name="employment_type" class="form-control" required onchange="updateGracePeriod()">
                                                <option value="permanent" <?php echo ($employee['employment_type'] ?? '') == 'permanent' ? 'selected' : ''; ?>>Pegawai Tetap</option>
                                                <option value="contract" <?php echo ($employee['employment_type'] ?? '') == 'contract' ? 'selected' : ''; ?>>Kontrak</option>
                                                <option value="intern" <?php echo ($employee['employment_type'] ?? '') == 'intern' ? 'selected' : ''; ?>>Magang</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="hire_date">Tanggal Masuk <span class="required">*</span></label>
                                            <input type="date" id="hire_date" name="hire_date" class="form-control" 
                                                   value="<?php echo $employee['hire_date'] ?? ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="grace_period">Masa Tenggang Kerja</label>
                                            <input type="date" id="grace_period" name="grace_period" class="form-control" 
                                                   value="<?php echo $employee['grace_period'] ?? ''; ?>" 
                                                   placeholder="Untuk kontrak dan magang">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row-compact">
                                        <div class="form-group">
                                            <label for="salary">Gaji Pokok</label>
                                            <input type="number" id="salary" name="salary" class="form-control" 
                                                   value="<?php echo $employee['salary'] ?? ''; ?>" 
                                                   placeholder="Gaji pokok" min="0" step="1000">
                                        </div>
                                        <div class="form-group">
                                            <label for="monthly_salary">Gaji Bulanan</label>
                                            <input type="number" id="monthly_salary" name="monthly_salary" class="form-control" 
                                                   value="<?php echo $employee['monthly_salary'] ?? ''; ?>" 
                                                   placeholder="Total gaji per bulan" min="0" step="1000">
                                        </div>
                                    </div>
                                    
                                    <div class="upload-section-compact">
                                        <label for="photo">Upload Foto</label>
                                        <div class="upload-area">
                                            <div class="upload-box-compact">
                                                <span>📷</span>
                                                <small>Pilih Foto</small>
                                            </div>
                                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                                            <button type="button" class="btn-upload-compact" onclick="document.getElementById('photo').click()">Browse File</button>
                                        </div>
                                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: var(--space-xs); display: block;">
                                            Format: JPG, PNG, max 2MB
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <?php if ($action == 'add'): ?>
                                    <button type="submit" name="add_employee" class="btn-primary">
                                        <span>💾</span> Simpan Data
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="edit_employee" class="btn-primary">
                                        <span>✏️</span> Update Data
                                    </button>
                                <?php endif; ?>
                                <button type="reset" class="btn-secondary">
                                    <span>🔄</span> Reset
                                </button>
                                <a href="employees.php" class="btn-secondary">
                                    <span>❌</span> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="footer no-print">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>
    
    <script>
        function resetFilters() {
            window.location.href = 'employees.php';
        }
        
        function updateGracePeriod() {
            const employmentType = document.querySelector('select[name="employment_type"]').value;
            const hireDateInput = document.querySelector('input[name="hire_date"]');
            const gracePeriodInput = document.getElementById('grace_period');
            
            if (hireDateInput.value) {
                const hireDate = new Date(hireDateInput.value);
                let gracePeriod = null;
                
                if (employmentType === 'contract') {
                    gracePeriod = new Date(hireDate);
                    gracePeriod.setFullYear(gracePeriod.getFullYear() + 1);
                } else if (employmentType === 'intern') {
                    gracePeriod = new Date(hireDate);
                    gracePeriod.setMonth(gracePeriod.getMonth() + 6);
                } else {
                    gracePeriodInput.value = '';
                    gracePeriodInput.disabled = true;
                    return;
                }
                
                if (gracePeriod) {
                    gracePeriodInput.value = gracePeriod.toISOString().split('T')[0];
                    gracePeriodInput.disabled = false;
                }
            }
        }
        
        document.querySelector('input[name="hire_date"]').addEventListener('change', updateGracePeriod);
        
        document.addEventListener('DOMContentLoaded', function() {
            updateGracePeriod();
        });
    </script>
</body>
</html>