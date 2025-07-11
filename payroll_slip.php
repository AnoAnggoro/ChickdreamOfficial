<?php
require_once 'functions.php';
requireLogin();
requirePermission('payroll', 'lihat');

if (!isset($_GET['id'])) {
    die('ID payroll tidak ditemukan');
}

global $pdo;
$stmt = $pdo->prepare("
    SELECT p.*, e.nip, e.name, e.department, e.position, e.hire_date
    FROM payroll p 
    JOIN employees e ON p.employee_id = e.id 
    WHERE p.id = ?
");
$stmt->execute([$_GET['id']]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    die('Data payroll tidak ditemukan');
}

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?php echo $payroll['name']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
        }
        
        .header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .slip-title {
            background: #333;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .employee-info {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            width: 150px;
            font-weight: bold;
        }
        
        .info-value {
            flex: 1;
        }
        
        .salary-details {
            padding: 20px;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .salary-table th,
        .salary-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        .salary-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .net-salary {
            background: #333;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .footer {
            padding: 20px;
            text-align: right;
            border-top: 1px solid #ddd;
        }
        
        .signature {
            margin-top: 50px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px;">Print Slip Gaji</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; margin-left: 10px;">Tutup</button>
    </div>

    <div class="slip-container">
        <div class="header">
            <h1>PT. CHICKDREAM ABADI WONOSOBO</h1>
            <p>Jl. Raya Wonosobo No. 123, Wonosobo, Jawa Tengah</p>
            <p>Telp: 0286-123456 | Email: info@chickdream.com</p>
        </div>
        
        <div class="slip-title">
            SLIP GAJI BULAN <?php echo strtoupper($monthNames[$payroll['period_month']]); ?> <?php echo $payroll['period_year']; ?>
        </div>
        
        <div class="employee-info">
            <div class="info-row">
                <div class="info-label">NIP</div>
                <div class="info-value">: <?php echo $payroll['nip']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Nama</div>
                <div class="info-value">: <?php echo $payroll['name']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Jabatan</div>
                <div class="info-value">: <?php echo $payroll['position']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Departemen</div>
                <div class="info-value">: <?php echo $payroll['department']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Masuk</div>
                <div class="info-value">: <?php echo date('d F Y', strtotime($payroll['hire_date'])); ?></div>
            </div>
        </div>
        
        <div class="salary-details">
            <table class="salary-table">
                <thead>
                    <tr>
                        <th>KETERANGAN</th>
                        <th class="text-right">JUMLAH (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="text-right"><?php echo number_format($payroll['basic_salary'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Tunjangan</td>
                        <td class="text-right"><?php echo number_format($payroll['allowances'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>Lembur</td>
                        <td class="text-right"><?php echo number_format($payroll['overtime'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>GAJI KOTOR</strong></td>
                        <td class="text-right"><strong><?php echo number_format($payroll['gross_salary'], 0, ',', '.'); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Potongan</td>
                        <td class="text-right">(<?php echo number_format($payroll['deductions'], 0, ',', '.'); ?>)</td>
                    </tr>
                    <tr>
                        <td>Pajak</td>
                        <td class="text-right">(<?php echo number_format($payroll['tax'], 0, ',', '.'); ?>)</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>TOTAL POTONGAN</strong></td>
                        <td class="text-right"><strong>(<?php echo number_format($payroll['deductions'] + $payroll['tax'], 0, ',', '.'); ?>)</strong></td>
                    </tr>
                    <tr class="net-salary">
                        <td><strong>GAJI BERSIH</strong></td>
                        <td class="text-right"><strong><?php echo number_format($payroll['net_salary'], 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Wonosobo, <?php echo date('d F Y'); ?></p>
            <div class="signature">
                <p>Hormat kami,</p>
                <br><br><br>
                <p><strong>(_____________________)</strong></p>
                <p>Kepala HRD</p>
            </div>
        </div>
    </div>
</body>
</html>
