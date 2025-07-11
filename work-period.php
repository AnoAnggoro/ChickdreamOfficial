<?php
require_once 'functions.php';
requireLogin();

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        header('Location: work-period.php?success=1');
        exit;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$employment_filter = $_GET['employment'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
global $pdo;
$sql = "
    SELECT e.id, e.nip, e.name, e.department, e.position, e.hire_date, e.status, e.employment_type,
           DATEDIFF(CURDATE(), e.hire_date) as days_worked,
           CONCAT(FLOOR(DATEDIFF(CURDATE(), e.hire_date)/365), ' tahun ', 
                  FLOOR((DATEDIFF(CURDATE(), e.hire_date)%365)/30), ' bulan') as calculated_period
    FROM employees e 
    WHERE e.status IN ('active', 'inactive', 'terminated', 'resigned')
";

$params = [];

if ($search) {
    $sql .= " AND (e.nip LIKE ? OR e.name LIKE ? OR e.position LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($department_filter) {
    $sql .= " AND e.department = ?";
    $params[] = $department_filter;
}

if ($employment_filter) {
    $sql .= " AND e.employment_type = ?";
    $params[] = $employment_filter;
}

if ($status_filter) {
    $sql .= " AND e.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY e.hire_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workPeriods = $stmt->fetchAll();

// Get departments for filter
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Kerja - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('work-period'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php echo renderTopBar('Masa Kerja'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success no-print">
                        Data pegawai berhasil dihapus!
                    </div>
                <?php endif; ?>
                
                <!-- Print Header (only visible when printing) -->
                
                
                <div class="data-table">
                    <div class="section-header no-print">
                        <h4>Data Masa Kerja Pegawai</h4>
                        <div class="table-controls">
                            <button class="btn-add" onclick="window.print()">Print Report</button>
                            <button class="btn-secondary" onclick="resetFilters()">Reset Filter</button>
                        </div>
                    </div>
                    
                    <!-- Search and Filter Section -->
                    <div class="search-filter-section no-print">
                        <form method="GET" class="search-form">
                            <div class="search-row">
                                <div class="search-group">
                                    <input type="text" name="search" placeholder="Cari NIP, Nama, atau Jabatan..." 
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
                            <p>Ditemukan <?php echo count($workPeriods); ?> data 
                            <?php if ($search): ?>
                                untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                            <?php endif; ?>
                            <?php if ($department_filter): ?>
                                di departemen <strong><?php echo $department_filter; ?></strong>
                            <?php endif; ?>
                            <?php if ($employment_filter): ?>
                                dengan jenis pekerjaan <strong><?php echo $employment_filter == 'permanent' ? 'Tetap' : ($employment_filter == 'contract' ? 'Kontrak' : 'Magang'); ?></strong>
                            <?php endif; ?>
                            <?php if ($status_filter): ?>
                                dengan status <strong><?php echo $status_filter; ?></strong>
                            <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>Jabatan</th>
                                <th>Tanggal Masuk</th>
                                <th>Masa Kerja</th>
                                <th>Status Karyawan</th>
                                <th>Jenis Pekerjaan</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($workPeriods)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; color: #7f8c8d; padding: 40px;">
                                    <h5>Tidak ada data yang ditemukan</h5>
                                    <p>Coba ubah kriteria pencarian atau filter</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($workPeriods as $index => $period): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $period['nip']; ?></td>
                                <td><?php echo $period['name']; ?></td>
                                <td><?php echo $period['department']; ?></td>
                                <td><?php echo $period['position']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($period['hire_date'])); ?></td>
                                <td><?php echo $period['calculated_period']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $period['status']; ?>">
                                        <?php 
                                        $statusLabels = [
                                            'active' => 'Aktif',
                                            'inactive' => 'Tidak Aktif',
                                            'terminated' => 'Diberhentikan',
                                            'resigned' => 'Mengundurkan Diri'
                                        ];
                                        echo $statusLabels[$period['status']] ?? ucfirst($period['status']); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="employment-badge employment-<?php echo $period['employment_type']; ?>">
                                        <?php 
                                        $typeLabels = [
                                            'permanent' => 'Tetap',
                                            'contract' => 'Kontrak',
                                            'intern' => 'Magang'
                                        ];
                                        echo $typeLabels[$period['employment_type']] ?? ucfirst($period['employment_type']); 
                                        ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <a href="employees.php?action=view&id=<?php echo $period['id']; ?>" class="btn-action btn-view">Detail</a>
                                    <a href="employees.php?action=edit&id=<?php echo $period['id']; ?>" class="btn-action btn-edit">Edit</a>
                                    <a href="?delete=<?php echo $period['id']; ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus data pegawai <?php echo $period['name']; ?>? Data ini akan dihapus permanen!')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Print Summary -->
                    
            <div class="footer no-print">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>
    
    <script>
        function resetFilters() {
            window.location.href = 'work-period.php';
        }
    </script>
</body>
</html>