<?php
require_once 'functions.php';
requireLogin();

// Handle placement assignment
if ($_POST && isset($_POST['assign_placement'])) {
    $employee_id = $_POST['employee_id'];
    $location = $_POST['location'];
    $start_date = $_POST['start_date'];
    $placement_type = $_POST['placement_type'];
    $duration_months = $_POST['duration_months'] ?: NULL;
    $description = $_POST['description'];
    $notes = $_POST['notes'];
    
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO placements (employee_id, location, start_date, placement_type, duration_months, description, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt->execute([$employee_id, $location, $start_date, $placement_type, $duration_months, $description, $notes])) {
        header('Location: placement.php?success=1');
        exit;
    }
}

// Handle edit placement
if ($_POST && isset($_POST['edit_placement'])) {
    $id = $_POST['id'];
    $employee_id = $_POST['employee_id'];
    $location = $_POST['location'];
    $start_date = $_POST['start_date'];
    $placement_type = $_POST['placement_type'];
    $duration_months = $_POST['duration_months'] ?: NULL;
    $description = $_POST['description'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];
    
    global $pdo;
    $stmt = $pdo->prepare("UPDATE placements SET employee_id=?, location=?, start_date=?, placement_type=?, duration_months=?, description=?, notes=?, status=? WHERE id=?");
    if ($stmt->execute([$employee_id, $location, $start_date, $placement_type, $duration_months, $description, $notes, $status, $id])) {
        header('Location: placement.php?success=2');
        exit;
    }
}

// Handle delete placement
if (isset($_GET['delete']) && $_GET['delete']) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM placements WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        header('Location: placement.php?success=3');
        exit;
    }
}

// Create placements table if not exists and add new columns
global $pdo;
$pdo->exec("
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
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )
");

// Add columns if they don't exist
try {
    $pdo->exec("ALTER TABLE placements ADD COLUMN placement_type ENUM('permanent', 'temporary', 'loan', 'rotation', 'project') DEFAULT 'permanent'");
} catch (PDOException $e) {
    // Column might already exist
}

try {
    $pdo->exec("ALTER TABLE placements ADD COLUMN duration_months INT DEFAULT NULL");
} catch (PDOException $e) {
    // Column might already exist
}

try {
    $pdo->exec("ALTER TABLE placements ADD COLUMN notes TEXT");
} catch (PDOException $e) {
    // Column might already exist
}

// Get placement data
$search = $_GET['search'] ?? '';
$location_filter = $_GET['location'] ?? '';
$placement_type_filter = $_GET['placement_type'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$sql = "
    SELECT p.*, e.nip, e.name, e.department, e.position, e.status as employee_status, e.employment_type
    FROM placements p 
    JOIN employees e ON p.employee_id = e.id 
    WHERE 1=1
";

$params = [];

if ($search) {
    $sql .= " AND (e.nip LIKE ? OR e.name LIKE ? OR p.location LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($location_filter) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location_filter%";
}

if ($placement_type_filter) {
    $sql .= " AND p.placement_type = ?";
    $params[] = $placement_type_filter;
}

if ($status_filter) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$placements = $stmt->fetchAll();

// Get unique locations for filter
$locations = $pdo->query("SELECT DISTINCT location FROM placements ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

// Get employees for dropdown
$employees = $pdo->query("SELECT id, nip, name, department, position FROM employees WHERE status = 'active'")->fetchAll();

// Get single placement for edit/view
$placement = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT p.*, e.nip, e.name, e.department, e.position FROM placements p JOIN employees e ON p.employee_id = e.id WHERE p.id = ?");
    $stmt->execute([$_GET['id']]);
    $placement = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penempatan Kerja - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('placement'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php echo renderTopBar('Penempatan Kerja'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        $messages = [
                            1 => 'Penempatan kerja berhasil ditambahkan!',
                            2 => 'Penempatan kerja berhasil diperbarui!',
                            3 => 'Penempatan kerja berhasil dihapus!'
                        ];
                        echo $messages[$_GET['success']];
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Tabel Data Penempatan -->
                <div class="data-table">
                    <div class="section-header">
                        <h4>Data Penempatan Kerja</h4>
                        <div class="table-controls">
                            <button class="btn-add" onclick="openModal('addModal')">Tambah Data</button>
                            <button class="btn-add" onclick="window.print()">Print Report</button>
                        </div>
                    </div>
                    
                    <!-- Search and Filter Section -->
                    <div class="search-filter-section no-print">
                        <form method="GET" class="search-form">
                            <div class="search-row">
                                <div class="search-group">
                                    <input type="text" name="search" placeholder="Cari NIP, Nama, atau Lokasi..." 
                                           value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                                </div>
                                <div class="filter-group">
                                    <select name="location" class="filter-select">
                                        <option value="">Semua Lokasi</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?php echo $loc; ?>" <?php echo $location_filter == $loc ? 'selected' : ''; ?>>
                                                <?php echo $loc; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <select name="placement_type" class="filter-select">
                                        <option value="">Semua Jenis</option>
                                        <option value="permanent" <?php echo $placement_type_filter == 'permanent' ? 'selected' : ''; ?>>Tetap</option>
                                        <option value="temporary" <?php echo $placement_type_filter == 'temporary' ? 'selected' : ''; ?>>Sementara</option>
                                        <option value="loan" <?php echo $placement_type_filter == 'loan' ? 'selected' : ''; ?>>Dipinjamkan</option>
                                        <option value="rotation" <?php echo $placement_type_filter == 'rotation' ? 'selected' : ''; ?>>Rotasi</option>
                                        <option value="project" <?php echo $placement_type_filter == 'project' ? 'selected' : ''; ?>>Proyek</option>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <select name="status" class="filter-select">
                                        <option value="">Semua Status</option>
                                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="search-buttons">
                                    <button type="submit" class="btn-primary">Cari</button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($search || $location_filter || $placement_type_filter || $status_filter): ?>
                        <div class="search-results-info">
                            <p>Ditemukan <?php echo count($placements); ?> data penempatan kerja</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Lokasi Penempatan</th>
                                <th>Jenis Penempatan</th>
                                <th>Durasi</th>
                                <th>Tanggal Mulai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($placements as $index => $placement): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $placement['nip']; ?></td>
                                <td><?php echo $placement['name']; ?></td>
                                <td><?php echo $placement['position']; ?></td>
                                <td><?php echo $placement['location']; ?></td>
                                <td>
                                    <span class="placement-badge placement-<?php echo $placement['placement_type'] ?? 'permanent'; ?>">
                                        <?php 
                                        $placementLabels = [
                                            'permanent' => 'Tetap',
                                            'temporary' => 'Sementara',
                                            'loan' => 'Dipinjamkan',
                                            'rotation' => 'Rotasi',
                                            'project' => 'Proyek'
                                        ];
                                        echo $placementLabels[$placement['placement_type'] ?? 'permanent']; 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($placement['duration_months']): ?>
                                        <?php echo $placement['duration_months']; ?> bulan
                                    <?php else: ?>
                                        <span style="color: #7f8c8d;">Permanen</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($placement['start_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $placement['status'] == 'active' ? 'hadir' : 'izin'; ?>">
                                        <?php echo $placement['status'] == 'active' ? 'Aktif' : 'Selesai'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewPlacement(<?php echo $placement['id']; ?>)" class="btn-action btn-view">Detail</button>
                                    <button onclick="editPlacement(<?php echo $placement['id']; ?>)" class="btn-action btn-edit">Edit</button>
                                    <a href="?delete=<?php echo $placement['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
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

    <!-- Modal Tambah Data -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Tambah Penempatan Kerja</h4>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilih Pegawai</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">-- Pilih Pegawai --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo $emp['nip']; ?> - <?php echo $emp['name']; ?> (<?php echo $emp['department']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lokasi Penempatan</label>
                            <input type="text" name="location" class="form-control" placeholder="Contoh: Jepat, BLORANGSO" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Penempatan</label>
                            <select name="placement_type" class="form-control" required onchange="toggleDuration(this)">
                                <option value="permanent">Penempatan Tetap</option>
                                <option value="temporary">Penempatan Sementara</option>
                                <option value="loan">Dipinjamkan</option>
                                <option value="rotation">Rotasi</option>
                                <option value="project">Penugasan Proyek</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Durasi (Bulan)</label>
                            <input type="number" name="duration_months" class="form-control duration-input" 
                                   placeholder="Kosongkan jika tetap" min="1" max="60">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi Tugas</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi tugas dan tanggung jawab"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan khusus mengenai penempatan"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" name="assign_placement" class="btn-primary">Simpan Penempatan</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Data -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Edit Penempatan Kerja</h4>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <!-- Same form fields as add modal but with edit_ prefix for IDs -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilih Pegawai</label>
                            <select name="employee_id" id="edit_employee_id" class="form-control" required>
                                <option value="">-- Pilih Pegawai --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo $emp['nip']; ?> - <?php echo $emp['name']; ?> (<?php echo $emp['department']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lokasi Penempatan</label>
                            <input type="text" name="location" id="edit_location" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Penempatan</label>
                            <select name="placement_type" id="edit_placement_type" class="form-control" required onchange="toggleDuration(this)">
                                <option value="permanent">Penempatan Tetap</option>
                                <option value="temporary">Penempatan Sementara</option>
                                <option value="loan">Dipinjamkan</option>
                                <option value="rotation">Rotasi</option>
                                <option value="project">Penugasan Proyek</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Durasi (Bulan)</label>
                            <input type="number" name="duration_months" id="edit_duration_months" class="form-control duration-input" min="1" max="60">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control" required>
                                <option value="active">Aktif</option>
                                <option value="completed">Selesai</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Deskripsi Tugas</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Catatan Tambahan</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" name="edit_placement" class="btn-primary">Update Penempatan</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal View Detail -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Detail Penempatan Kerja</h4>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="viewContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('viewModal')">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            if (modalId === 'addModal') {
                toggleDuration(document.querySelector('#addModal select[name="placement_type"]'));
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function toggleDuration(selectElement) {
            const modal = selectElement.closest('.modal');
            const durationInput = modal.querySelector('.duration-input');
            
            if (selectElement.value === 'permanent') {
                durationInput.disabled = true;
                durationInput.value = '';
                durationInput.placeholder = 'Tidak diperlukan untuk penempatan tetap';
            } else {
                durationInput.disabled = false;
                durationInput.placeholder = 'Masukkan durasi dalam bulan';
            }
        }
        
        function editPlacement(id) {
            // Fetch placement data and populate edit form
            fetch(`get_placement.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_employee_id').value = data.employee_id;
                    document.getElementById('edit_location').value = data.location;
                    document.getElementById('edit_placement_type').value = data.placement_type || 'permanent';
                    document.getElementById('edit_duration_months').value = data.duration_months || '';
                    document.getElementById('edit_start_date').value = data.start_date;
                    document.getElementById('edit_status').value = data.status;
                    document.getElementById('edit_description').value = data.description || '';
                    document.getElementById('edit_notes').value = data.notes || '';
                    
                    toggleDuration(document.getElementById('edit_placement_type'));
                    openModal('editModal');
                })
                .catch(error => {
                    alert('Error loading data: ' + error);
                });
        }
        
        function viewPlacement(id) {
            // Fetch placement data and show in view modal
            fetch(`get_placement.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const placementLabels = {
                        'permanent': 'Tetap',
                        'temporary': 'Sementara',
                        'loan': 'Dipinjamkan',
                        'rotation': 'Rotasi',
                        'project': 'Proyek'
                    };
                    
                    const content = `
                        <div class="detail-row">
                            <label>NIP:</label>
                            <span>${data.nip}</span>
                        </div>
                        <div class="detail-row">
                            <label>Nama Pegawai:</label>
                            <span>${data.name}</span>
                        </div>
                        <div class="detail-row">
                            <label>Jabatan:</label>
                            <span>${data.position}</span>
                        </div>
                        <div class="detail-row">
                            <label>Departemen:</label>
                            <span>${data.department}</span>
                        </div>
                        <div class="detail-row">
                            <label>Lokasi Penempatan:</label>
                            <span>${data.location}</span>
                        </div>
                        <div class="detail-row">
                            <label>Jenis Penempatan:</label>
                            <span>${placementLabels[data.placement_type] || data.placement_type}</span>
                        </div>
                        <div class="detail-row">
                            <label>Durasi:</label>
                            <span>${data.duration_months ? data.duration_months + ' bulan' : 'Permanen'}</span>
                        </div>
                        <div class="detail-row">
                            <label>Tanggal Mulai:</label>
                            <span>${new Date(data.start_date).toLocaleDateString('id-ID')}</span>
                        </div>
                        <div class="detail-row">
                            <label>Status:</label>
                            <span>${data.status === 'active' ? 'Aktif' : 'Selesai'}</span>
                        </div>
                        <div class="detail-row">
                            <label>Deskripsi:</label>
                            <span>${data.description || '-'}</span>
                        </div>
                        <div class="detail-row">
                            <label>Catatan:</label>
                            <span>${data.notes || '-'}</span>
                        </div>
                    `;
                    
                    document.getElementById('viewContent').innerHTML = content;
                    openModal('viewModal');
                })
                .catch(error => {
                    alert('Error loading data: ' + error);
                });
        }
        
        function resetFilters() {
            window.location.href = 'placement.php';
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
    </script>
</body>
</html>

 
