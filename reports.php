<?php
require_once 'functions.php';
requireLogin();
requirePermission('reports', 'lihat');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php echo renderSidebar('reports'); ?>
        
        <div class="main-content">
            <?php echo renderTopBar('Laporan'); ?>
            
            <div class="content">
                <div class="welcome-section">
                    <h4>📊 Sistem Laporan</h4>
                    <p>Pilih jenis laporan yang ingin Anda generate dan unduh</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number blue">📋</div>
                        <div class="stat-label">Laporan Pegawai</div>
                        <div style="margin-top: 15px;">
                            <a href="reports/employee_report.php" class="btn-primary">Generate</a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number green">✅</div>
                        <div class="stat-label">Laporan Absensi</div>
                        <div style="margin-top: 15px;">
                            <a href="reports/attendance_report.php" class="btn-primary">Generate</a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number orange">💰</div>
                        <div class="stat-label">Laporan Payroll</div>
                        <div style="margin-top: 15px;">
                            <a href="reports/payroll_report.php" class="btn-primary">Generate</a>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number purple">📍</div>
                        <div class="stat-label">Laporan Penempatan</div>
                        <div style="margin-top: 15px;">
                            <a href="reports/placement_report.php" class="btn-primary">Generate</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>
</body>
</html>
        