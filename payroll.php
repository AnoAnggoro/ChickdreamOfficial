<?php
require_once 'functions.php';
requireLogin();
requirePermission('payroll', 'lihat');

// Handle payroll actions
if ($_POST) {
    if (isset($_POST['generate_payroll'])) {
        $period_month = $_POST['period_month'];
        $period_year = $_POST['period_year'];
        
        global $pdo;
        
        // Get all active employees
        $employees = $pdo->query("SELECT * FROM employees WHERE status = 'active'")->fetchAll();
        
        foreach ($employees as $emp) {
            // Check if payroll already exists
            $stmt = $pdo->prepare("SELECT id FROM payroll WHERE employee_id = ? AND period_month = ? AND period_year = ?");
            $stmt->execute([$emp['id'], $period_month, $period_year]);
            
            if (!$stmt->fetch()) {
                // Calculate payroll
                $basic_salary = $emp['monthly_salary'] ?: $emp['salary'];
                $allowances = $basic_salary * 0.1; // 10% allowance
                $overtime = 0; // Will be calculated later
                $deductions = $basic_salary * 0.02; // 2% deduction
                $tax = ($basic_salary + $allowances) * 0.05; // 5% tax
                $gross_salary = $basic_salary + $allowances + $overtime;
                $net_salary = $gross_salary - $deductions - $tax;
                
                $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, period_month, period_year, basic_salary, allowances, overtime, deductions, tax, gross_salary, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$emp['id'], $period_month, $period_year, $basic_salary, $allowances, $overtime, $deductions, $tax, $gross_salary, $net_salary]);
            }
        }
        
        header('Location: payroll.php?success=1');
        exit;
    }
    
    if (isset($_POST['approve_payroll'])) {
        $payroll_id = $_POST['payroll_id'];
        
        global $pdo;
        $stmt = $pdo->prepare("UPDATE payroll SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt->execute([$_SESSION['user_id'], $payroll_id])) {
            header('Location: payroll.php?success=2');
            exit;
        }
    }
    
    if (isset($_POST['edit_payroll'])) {
        $id = $_POST['id'];
        $basic_salary = $_POST['basic_salary'];
        $allowances = $_POST['allowances'];
        $overtime = $_POST['overtime'];
        $deductions = $_POST['deductions'];
        $tax = $_POST['tax'];
        $gross_salary = $basic_salary + $allowances + $overtime;
        $net_salary = $gross_salary - $deductions - $tax;
        
        global $pdo;
        $stmt = $pdo->prepare("UPDATE payroll SET basic_salary=?, allowances=?, overtime=?, deductions=?, tax=?, gross_salary=?, net_salary=? WHERE id=?");
        if ($stmt->execute([$basic_salary, $allowances, $overtime, $deductions, $tax, $gross_salary, $net_salary, $id])) {
            header('Location: payroll.php?success=3');
            exit;
        }
    }
}

// Get payroll data with filters
$search = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? date('n');
$year_filter = $_GET['year'] ?? date('Y');
$status_filter = $_GET['status'] ?? '';

global $pdo;
$sql = "
    SELECT p.*, e.nip, e.name, e.department, e.position, 
           u.name as approved_by_name
    FROM payroll p 
    JOIN employees e ON p.employee_id = e.id 
    LEFT JOIN users u ON p.approved_by = u.id
    WHERE p.period_month = ? AND p.period_year = ?
";

$params = [$month_filter, $year_filter];

if ($search) {
    $sql .= " AND (e.nip LIKE ? OR e.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam]);
}

if ($status_filter) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY e.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payrolls = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php echo renderSidebar('payroll'); ?>
        
        <div class="main-content">
            <?php echo renderTopBar('Payroll'); ?>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        $messages = [
                            1 => 'Payroll berhasil digenerate!',
                            2 => 'Payroll berhasil disetujui!',
                            3 => 'Payroll berhasil diperbarui!'
                        ];
                        echo $messages[$_GET['success']];
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Payroll Controls -->
                <div class="welcome-section">
                    <h4>Payroll Management</h4>
                    <p>Generate dan kelola gaji pegawai untuk periode <?php echo date('F Y', mktime(0, 0, 0, $month_filter, 1, $year_filter)); ?></p>
                    
                    <div style="margin-top: 20px;">
                        <?php if (hasPermission('payroll', 'tambah')): ?>
                            <button class="btn-add" onclick="openModal('generateModal')">Generate Payroll</button>
                        <?php endif; ?>
                        <button class="btn-secondary" onclick="openModal('filterModal')">Filter Periode</button>
                        <button class="btn-secondary" onclick="window.print()">Print Report</button>
                    </div>
                </div>

                <!-- Payroll Table -->
                <div class="data-table">
                    <div class="section-header">
                        <h4>Data Payroll - <?php echo date('F Y', mktime(0, 0, 0, $month_filter, 1, $year_filter)); ?></h4>
                        <div class="table-controls">
                            <input type="text" placeholder="Cari pegawai..." id="searchInput">
                        </div>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Gaji Pokok</th>
                                <th>Tunjangan</th>
                                <th>Lembur</th>
                                <th>Potongan</th>
                                <th>Pajak</th>
                                <th>Gaji Bersih</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payrolls)): ?>
                            <tr>
                                <td colspan="12" style="text-align: center; padding: 40px;">
                                    <h5>Belum ada data payroll</h5>
                                    <p>Generate payroll untuk periode ini</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($payrolls as $index => $payroll): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $payroll['nip']; ?></td>
                                <td><?php echo $payroll['name']; ?></td>
                                <td><?php echo $payroll['position']; ?></td>
                                <td>Rp <?php echo number_format($payroll['basic_salary'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($payroll['allowances'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($payroll['overtime'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($payroll['deductions'], 0, ',', '.'); ?></td>
                                <td>Rp <?php echo number_format($payroll['tax'], 0, ',', '.'); ?></td>
                                <td><strong>Rp <?php echo number_format($payroll['net_salary'], 0, ',', '.'); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php 
                                        echo $payroll['status'] == 'approved' ? 'hadir' : 
                                            ($payroll['status'] == 'paid' ? 'izin' : 'alpha'); 
                                    ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewPayroll(<?php echo $payroll['id']; ?>)" class="btn-action btn-view">Detail</button>
                                    <?php if (hasPermission('payroll', 'edit') && $payroll['status'] == 'draft'): ?>
                                        <button onclick="editPayroll(<?php echo $payroll['id']; ?>)" class="btn-action btn-edit">Edit</button>
                                    <?php endif; ?>
                                    <?php if (hasPermission('payroll', 'approve') && $payroll['status'] == 'draft'): ?>
                                        <button onclick="approvePayroll(<?php echo $payroll['id']; ?>)" class="btn-action btn-view">Approve</button>
                                    <?php endif; ?>
                                    <a href="payroll_slip.php?id=<?php echo $payroll['id']; ?>" target="_blank" class="btn-action btn-edit">Slip</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>

    <!-- Generate Payroll Modal -->
    <div id="generateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Generate Payroll</h4>
                <span class="close" onclick="closeModal('generateModal')">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bulan</label>
                            <select name="period_month" class="form-control" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tahun</label>
                            <select name="period_year" class="form-control" required>
                                <?php for ($year = date('Y') - 1; $year <= date('Y') + 1; $year++): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="alert" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0;">
                        <strong>Informasi:</strong><br>
                        - Payroll akan digenerate untuk semua pegawai aktif<br>
                        - Gaji pokok diambil dari data pegawai<br>
                        - Tunjangan otomatis 10% dari gaji pokok<br>
                        - Potongan otomatis 2% dari gaji pokok<br>
                        - Pajak otomatis 5% dari (gaji pokok + tunjangan)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="generate_payroll" class="btn-primary">Generate Payroll</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('generateModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Filter Periode</h4>
                <span class="close" onclick="closeModal('filterModal')">&times;</span>
            </div>
            <form method="GET">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bulan</label>
                            <select name="month" class="form-control">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $month_filter ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tahun</label>
                            <select name="year" class="form-control">
                                <?php for ($year = date('Y') - 2; $year <= date('Y') + 1; $year++): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $year_filter ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Filter</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('filterModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Edit Payroll</h4>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Gaji Pokok</label>
                            <input type="number" name="basic_salary" id="edit_basic_salary" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tunjangan</label>
                            <input type="number" name="allowances" id="edit_allowances" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lembur</label>
                            <input type="number" name="overtime" id="edit_overtime" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Potongan</label>
                            <input type="number" name="deductions" id="edit_deductions" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pajak</label>
                            <input type="number" name="tax" id="edit_tax" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_payroll" class="btn-primary">Update Payroll</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Detail Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Detail Payroll</h4>
                <span class="close" onclick="closeModal('viewModal')">&times;</span>
            </div>
            <div class="modal-body payroll-detail">
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
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editPayroll(id) {
            fetch(`get_payroll.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_basic_salary').value = data.basic_salary;
                    document.getElementById('edit_allowances').value = data.allowances;
                    document.getElementById('edit_overtime').value = data.overtime;
                    document.getElementById('edit_deductions').value = data.deductions;
                    document.getElementById('edit_tax').value = data.tax;
                    
                    openModal('editModal');
                })
                .catch(error => alert('Error: ' + error));
        }
        
        function viewPayroll(id) {
            fetch(`get_payroll.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const content = `
                        <div class="detail-section">
                            <h6>📋 Informasi Pegawai</h6>
                            <div class="detail-row">
                                <label>NIP:</label>
                                <span>${data.nip}</span>
                            </div>
                            <div class="detail-row">
                                <label>Nama:</label>
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
                        </div>
                        <div class="detail-section">
                            <h6>💰 Komponen Gaji</h6>
                            <div class="detail-row">
                                <label>Gaji Pokok:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(data.basic_salary)}</span>
                            </div>
                            <div class="detail-row">
                                <label>Tunjangan:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(data.allowances)}</span>
                            </div>
                            <div class="detail-row">
                                <label>Lembur:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(data.overtime)}</span>
                            </div>
                            <div class="detail-row">
                                <label>Gaji Kotor:</label>
                                <span><strong>Rp ${new Intl.NumberFormat('id-ID').format(data.gross_salary)}</strong></span>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h6>📉 Potongan</h6>
                            <div class="detail-row">
                                <label>Potongan:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(data.deductions)}</span>
                            </div>
                            <div class="detail-row">
                                <label>Pajak:</label>
                                <span>Rp ${new Intl.NumberFormat('id-ID').format(data.tax)}</span>
                            </div>
                            <div class="detail-row">
                                <label>Total Potongan:</label>
                                <span><strong>Rp ${new Intl.NumberFormat('id-ID').format(parseFloat(data.deductions) + parseFloat(data.tax))}</strong></span>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h6>💵 Gaji Bersih</h6>
                            <div class="detail-row">
                                <label>Gaji Bersih:</label>
                                <span style="font-size: 1.2em; color: #27ae60;"><strong>Rp ${new Intl.NumberFormat('id-ID').format(data.net_salary)}</strong></span>
                            </div>
                        </div>
                        <div class="detail-actions">
                            <button onclick="window.open('payroll_slip.php?id=${data.id}', '_blank')" class="btn-primary">
                                📄 Download Slip Gaji
                            </button>
                        </div>
                    `;
                    
                    document.getElementById('viewContent').innerHTML = content;
                    openModal('viewModal');
                })
                .catch(error => alert('Error: ' + error));
        }
        
        function approvePayroll(id) {
            if (confirm('Yakin ingin menyetujui payroll ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="payroll_id" value="${id}">
                    <input type="hidden" name="approve_payroll" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Simple search
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
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
