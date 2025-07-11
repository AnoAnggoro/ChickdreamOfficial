<?php
require_once 'functions.php';
requireLogin();

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        header('Location: leave-period.php?success=1');
        exit;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$employment_filter = $_GET['employment'] ?? '';
$status_filter = $_GET['status'] ?? '';
$grace_status_filter = $_GET['grace_status'] ?? '';

// Build query with filters
global $pdo;
$sql = "
    SELECT e.id, e.nip, e.name, e.department, e.position, e.grace_period, e.employment_type, e.status,
           CASE 
               WHEN e.grace_period IS NOT NULL THEN DATEDIFF(e.grace_period, CURDATE())
               ELSE NULL
           END as days_remaining,
           CASE
               WHEN e.employment_type = 'contract' THEN 'Kontrak'
               WHEN e.employment_type = 'intern' THEN 'Magang'
               ELSE 'Tetap'
           END as employment_label
    FROM employees e 
    WHERE e.employment_type IN ('contract', 'intern')
    AND e.grace_period IS NOT NULL
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

if ($grace_status_filter) {
    switch ($grace_status_filter) {
        case 'expired':
            $sql .= " AND DATEDIFF(e.grace_period, CURDATE()) <= 0";
            break;
        case 'critical':
            $sql .= " AND DATEDIFF(e.grace_period, CURDATE()) BETWEEN 1 AND 30";
            break;
        case 'warning':
            $sql .= " AND DATEDIFF(e.grace_period, CURDATE()) BETWEEN 31 AND 60";
            break;
        case 'safe':
            $sql .= " AND DATEDIFF(e.grace_period, CURDATE()) > 60";
            break;
    }
}

$sql .= " ORDER BY e.grace_period ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leavePeriods = $stmt->fetchAll();

// Get departments for filter
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE employment_type IN ('contract', 'intern') AND department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Tenggang Kerja - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('leave-period'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php echo renderTopBar('Masa Tenggang Kerja'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success no-print">
                        Data pegawai berhasil dihapus!
                    </div>
                <?php endif; ?>
                
                <!-- Print Header -->
                
                
                <!-- Info Section -->
                <div class="welcome-section no-print">
                    <h4>Informasi Masa Tenggang Kerja</h4>
                    <p>Masa tenggang kerja hanya berlaku untuk pegawai <strong>Kontrak</strong> (1 tahun) dan <strong>Magang</strong> (6 bulan). Pegawai tetap tidak memiliki masa tenggang kerja.</p>
                </div>

                <div class="data-table">
                    <div class="section-header no-print">
                        <h4>Data Masa Tenggang Kerja (Kontrak & Magang)</h4>
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
                                        <option value="">Kontrak & Magang</option>
                                        <option value="contract" <?php echo $employment_filter == 'contract' ? 'selected' : ''; ?>>Kontrak</option>
                                        <option value="intern" <?php echo $employment_filter == 'intern' ? 'selected' : ''; ?>>Magang</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <select name="grace_status" class="filter-select">
                                        <option value="">Semua Status Tenggang</option>
                                        <option value="expired" <?php echo $grace_status_filter == 'expired' ? 'selected' : ''; ?>>Habis</option>
                                        <option value="critical" <?php echo $grace_status_filter == 'critical' ? 'selected' : ''; ?>>Kritis (≤30 hari)</option>
                                        <option value="warning" <?php echo $grace_status_filter == 'warning' ? 'selected' : ''; ?>>Perhatian (31-60 hari)</option>
                                        <option value="safe" <?php echo $grace_status_filter == 'safe' ? 'selected' : ''; ?>>Aman (>60 hari)</option>
                                    </select>
                                </div>
                                <div class="search-buttons">
                                    <button type="submit" class="btn-primary">Cari</button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($search || $department_filter || $employment_filter || $grace_status_filter): ?>
                        <div class="search-results-info">
                            <p>Ditemukan <?php echo count($leavePeriods); ?> data masa tenggang kerja</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($leavePeriods)): ?>
                        <div style="padding: 40px; text-align: center; color: #7f8c8d;">
                            <h5>Tidak ada data masa tenggang kerja</h5>
                            <p>Tidak ada data yang sesuai dengan kriteria pencarian atau filter yang dipilih.</p>
                        </div>
                    <?php else: ?>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Departemen</th>
                                <th>Jabatan</th>
                                <th>Status Karyawan</th>
                                <th>Jenis Pekerjaan</th>
                                <th>Batas Tenggang</th>
                                <th>Sisa Hari</th>
                                <th>Status Tenggang</th>
                                <th class="no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leavePeriods as $index => $period): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $period['nip']; ?></td>
                                <td><?php echo $period['name']; ?></td>
                                <td><?php echo $period['department']; ?></td>
                                <td><?php echo $period['position']; ?></td>
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
                                        <?php echo $period['employment_label']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($period['grace_period'])); ?></td>
                                <td>
                                    <?php if ($period['days_remaining'] > 0): ?>
                                        <strong><?php echo $period['days_remaining']; ?> hari</strong>
                                    <?php else: ?>
                                        <span style="color: #e74c3c; font-weight: bold;">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($period['days_remaining'] > 60): ?>
                                        <span class="status-badge status-hadir">Aman</span>
                                    <?php elseif ($period['days_remaining'] > 30): ?>
                                        <span class="status-badge status-izin">Perhatian</span>
                                    <?php elseif ($period['days_remaining'] > 0): ?>
                                        <span class="status-badge status-alpha">Kritis</span>
                                    <?php else: ?>
                                        <span class="status-badge status-terminated">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="no-print">
                                    <a href="employees.php?action=view&id=<?php echo $period['id']; ?>" class="btn-action btn-view">Detail</a>
                                    <a href="employees.php?action=edit&id=<?php echo $period['id']; ?>" class="btn-action btn-edit">Edit</a>
                                    <a href="?delete=<?php echo $period['id']; ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus data pegawai <?php echo $period['name']; ?>? Data ini akan dihapus permanen!')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Print Summary -->
                    
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer no-print">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>
    
    <script>
        function resetFilters() {
            window.location.href = 'leave-period.php';
        }
    </script>
</body>
</html>
    </div>
    
    <script>
        function resetFilters() {
            window.location.href = 'leave-period.php';
        }
    </script>
</body>
</html>
                 