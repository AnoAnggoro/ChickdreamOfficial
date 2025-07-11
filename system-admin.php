<?php
require_once 'functions.php';
requireLogin();
requirePermission('system_admin', 'lihat');

// Handle system admin actions
if ($_POST) {
    if (isset($_POST['backup_database'])) {
        // Create database backup
        $backupFile = createDatabaseBackup();
        if ($backupFile) {
            header('Location: system-admin.php?success=backup&file=' . urlencode($backupFile));
            exit;
        } else {
            header('Location: system-admin.php?error=backup_failed');
            exit;
        }
    }
    
    if (isset($_POST['clear_logs'])) {
        // Clear system logs
        clearSystemLogs();
        header('Location: system-admin.php?success=logs_cleared');
        exit;
    }
    
    if (isset($_POST['optimize_database'])) {
        // Optimize database
        optimizeDatabase();
        header('Location: system-admin.php?success=database_optimized');
        exit;
    }
}

function createDatabaseBackup() {
    $backupDir = 'backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    
    $filename = 'chickdream_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backupDir . $filename;
    
    // Database connection details
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'chickdream_hr';
    
    $command = "mysqldump --host=$host --user=$username --password=$password $database > $filepath";
    
    if (function_exists('exec')) {
        exec($command, $output, $return_code);
        if ($return_code === 0) {
            return $filename;
        }
    }
    
    // Fallback: PHP-based backup
    try {
        global $pdo;
        $backup = "-- PT. CHICKDREAM Database Backup\n";
        $backup .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
        
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $backup .= "-- Table: $table\n";
            $backup .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get CREATE TABLE statement
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $backup .= $createTable['Create Table'] . ";\n\n";
            
            // Get table data
            $data = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($data)) {
                $backup .= "INSERT INTO `$table` VALUES\n";
                $values = [];
                
                foreach ($data as $row) {
                    $escapedValues = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    $values[] = '(' . implode(', ', $escapedValues) . ')';
                }
                
                $backup .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        file_put_contents($filepath, $backup);
        return $filename;
    } catch (Exception $e) {
        return false;
    }
}

function clearSystemLogs() {
    // This would clear application logs, not database records
    $logFiles = ['logs/system.log', 'logs/error.log', 'logs/access.log'];
    
    foreach ($logFiles as $logFile) {
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
    }
}

function optimizeDatabase() {
    global $pdo;
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $pdo->exec("OPTIMIZE TABLE `$table`");
    }
}

// Get system information
function getSystemInfo() {
    global $pdo;
    
    $info = [];
    
    // Database info
    $info['database_size'] = $pdo->query("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
        FROM information_schema.tables 
        WHERE table_schema = 'chickdream_hr'
    ")->fetchColumn();
    
    // Table counts
    $info['total_employees'] = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
    $info['total_attendance'] = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
    $info['total_placements'] = $pdo->query("SELECT COUNT(*) FROM placements")->fetchColumn();
    $info['total_payrolls'] = $pdo->query("SELECT COUNT(*) FROM payroll")->fetchColumn();
    $info['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // System info
    $info['php_version'] = phpversion();
    $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $info['max_execution_time'] = ini_get('max_execution_time');
    $info['memory_limit'] = ini_get('memory_limit');
    $info['upload_max_filesize'] = ini_get('upload_max_filesize');
    
    return $info;
}

$systemInfo = getSystemInfo();

// Get backup files
$backupFiles = [];
if (is_dir('backups/')) {
    $backupFiles = array_filter(scandir('backups/'), function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });
    rsort($backupFiles); // Sort newest first
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php echo renderSidebar('system-admin'); ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <h4>System Administration</h4>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <span><?php echo $_SESSION['user_name']; ?></span>
                        <small>Super Admin</small>
                    </div>
                </div>
            </div>
            
            <div class="content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        $messages = [
                            'backup' => 'Backup database berhasil dibuat: ' . ($_GET['file'] ?? ''),
                            'logs_cleared' => 'Log sistem berhasil dibersihkan!',
                            'database_optimized' => 'Database berhasil dioptimasi!'
                        ];
                        echo $messages[$_GET['success']] ?? 'Operasi berhasil!';
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        $errors = [
                            'backup_failed' => 'Backup database gagal! Periksa konfigurasi sistem.'
                        ];
                        echo $errors[$_GET['error']] ?? 'Terjadi kesalahan!';
                        ?>
                    </div>
                <?php endif; ?>

                <!-- System Information -->
                <div class="stats-section">
                    <h4 class="section-title">Informasi Sistem</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number blue"><?php echo $systemInfo['database_size']; ?> MB</div>
                            <div class="stat-label">Ukuran Database</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number green"><?php echo $systemInfo['total_employees']; ?></div>
                            <div class="stat-label">Total Pegawai</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number orange"><?php echo $systemInfo['total_attendance']; ?></div>
                            <div class="stat-label">Records Absensi</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number purple"><?php echo $systemInfo['total_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                </div>

                <!-- System Details -->
                <div class="form-container">
                    <div class="form-header">
                        <h4>🖥️ Detail Sistem</h4>
                    </div>
                    
                    <div class="form-row-compact">
                        <div class="form-group">
                            <label>Versi PHP</label>
                            <input type="text" class="form-control" value="<?php echo $systemInfo['php_version']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Server Software</label>
                            <input type="text" class="form-control" value="<?php echo $systemInfo['server_software']; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row-compact">
                        <div class="form-group">
                            <label>Memory Limit</label>
                            <input type="text" class="form-control" value="<?php echo $systemInfo['memory_limit']; ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Max Execution Time</label>
                            <input type="text" class="form-control" value="<?php echo $systemInfo['max_execution_time']; ?> seconds" readonly>
                        </div>
                    </div>
                </div>

                <!-- Database Management -->
                <div class="form-container">
                    <div class="form-header">
                        <h4>🗄️ Manajemen Database</h4>
                    </div>
                    
                    <div class="form-actions">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="backup_database" class="btn-primary">
                                📦 Backup Database
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="optimize_database" class="btn-add" 
                                    onclick="return confirm('Yakin ingin mengoptimasi database?')">
                                ⚡ Optimasi Database
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_logs" class="btn-secondary"
                                    onclick="return confirm('Yakin ingin menghapus semua log?')">
                                🗑️ Bersihkan Log
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Backup Files -->
                <div class="data-table">
                    <div class="section-header">
                        <h4>📁 File Backup</h4>
                    </div>
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($backupFiles)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <h5>Belum ada file backup</h5>
                                    <p>Buat backup pertama dengan tombol "Backup Database"</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($backupFiles as $index => $file): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $file; ?></td>
                                <td>
                                    <?php 
                                    $size = filesize('backups/' . $file);
                                    echo round($size / 1024 / 1024, 2) . ' MB';
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', filemtime('backups/' . $file)); ?></td>
                                <td>
                                    <a href="backups/<?php echo $file; ?>" class="btn-action btn-view" download>Download</a>
                                    <a href="?delete_backup=<?php echo $file; ?>" class="btn-action btn-delete" 
                                       onclick="return confirm('Yakin ingin menghapus backup ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- System Health -->
                <div class="form-container">
                    <div class="form-header">
                        <h4>💊 System Health Check</h4>
                    </div>
                    
                    <div class="health-checks">
                        <div class="health-item">
                            <span class="health-status <?php echo is_writable('backups/') ? 'success' : 'error'; ?>">
                                <?php echo is_writable('backups/') ? '✅' : '❌'; ?>
                            </span>
                            <span>Backup Directory Writable</span>
                        </div>
                        
                        <div class="health-item">
                            <span class="health-status <?php echo extension_loaded('pdo_mysql') ? 'success' : 'error'; ?>">
                                <?php echo extension_loaded('pdo_mysql') ? '✅' : '❌'; ?>
                            </span>
                            <span>MySQL PDO Extension</span>
                        </div>
                        
                        <div class="health-item">
                            <span class="health-status <?php echo function_exists('exec') ? 'success' : 'warning'; ?>">
                                <?php echo function_exists('exec') ? '✅' : '⚠️'; ?>
                            </span>
                            <span>Exec Function (for mysqldump)</span>
                        </div>
                        
                        <div class="health-item">
                            <span class="health-status success">✅</span>
                            <span>Database Connection</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>

    <style>
        .health-checks {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .health-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--surface-hover);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--border-color);
        }
        
        .health-status.success {
            color: var(--success-color);
        }
        
        .health-status.warning {
            color: var(--warning-color);
        }
        
        .health-status.error {
            color: var(--error-color);
        }
    </style>
</body>
</html>
