<?php
require_once 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan - PT. CHICKDREAM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <?php echo renderSidebar('laporan'); ?>
        <div class="main-content">
            <?php echo renderTopBar('Laporan'); ?>
            <div class="content">
                <div class="welcome-section">
                    <h3>📊 Sistem Laporan</h3>
                    <p>Pilih jenis laporan yang ingin Anda generate dan unduh</p>
                </div>
                <div class="report-grid">
                    <div class="report-card">
                        <div class="report-icon">📁</div>
                        <div class="report-title">Laporan Pegawai</div>
                        <button class="btn-primary" onclick="window.open('employee_report.php','_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon">📝</div>
                        <div class="report-title">Laporan Absensi</div>
                        <button class="btn-primary" onclick="window.open('attendance_report.php','_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon">💰</div>
                        <div class="report-title">Laporan Payroll</div>
                        <button class="btn-primary" onclick="window.open('payroll_report.php','_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon">📍</div>
                        <div class="report-title">Laporan Penempatan</div>
                        <button class="btn-primary" onclick="window.open('placement_report.php','_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon">⏳</div>
                        <div class="report-title">Laporan Masa Kerja</div>
                        <button class="btn-primary" onclick="window.open('work_period_report.php','_blank')">Generate</button>
                    </div>
                    <div class="report-card">
                        <div class="report-icon">🕒</div>
                        <div class="report-title">Laporan Masa Tenggang</div>
                        <button class="btn-primary" onclick="window.open('leave_period_report.php','_blank')">Generate</button>
                    </div>
                </div>
            </div>
            <div class="footer">
                PT. CHICKDREAM MULYA JADI WONOSOBO
            </div>
        </div>
    </div>
    <style>
        .report-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 2rem;
        }
        .report-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 2rem 1.5rem;
            flex: 1 1 220px;
            max-width: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .report-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .report-title {
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .btn-primary {
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1.5rem;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary:hover {
            background: #4f46e5;
        }
    </style>
</body>
</html>
