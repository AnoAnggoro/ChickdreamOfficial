<?php
require_once '../functions.php';
requireLogin();
requirePermission('reports', 'lihat');

global $pdo;
$stmt = $pdo->query("
    SELECT a.*, e.name, e.department 
    FROM attendance a 
    LEFT JOIN employees e ON a.nip = e.nip 
    WHERE MONTH(a.date) = MONTH(CURDATE()) 
    AND YEAR(a.date) = YEAR(CURDATE())
    ORDER BY a.date DESC, e.name
");
$attendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Absensi</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .print-header { text-align: center; margin-top: 20px; }
        .print-header h2 { margin-bottom: 0; }
        .print-header p { margin: 2px 0; }
        .no-print { margin: 20px 0; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #888; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h2>PT. CHICKDREAM ABADI WONOSOBO</h2>
        <p>Laporan Absensi Bulan <?php echo date('F Y'); ?></p>
        <p>Tanggal Cetak: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="no-print">
        <button onclick="window.print()" class="btn-primary">Print Laporan</button>
        <a href="../laporan.php" class="btn-secondary">Kembali</a>
    </div>

    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Departemen</th>
            <th>Jam Masuk</th>
            <th>Jam Keluar</th>
            <th>Status</th>
        </tr>
        <?php foreach ($attendance as $index => $record): ?>
        <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
            <td><?php echo htmlspecialchars($record['nip']); ?></td>
            <td><?php echo htmlspecialchars($record['name']); ?></td>
            <td><?php echo htmlspecialchars($record['department']); ?></td>
            <td><?php echo $record['check_in'] ?: '-'; ?></td>
            <td><?php echo $record['check_out'] ?: '-'; ?></td>
            <td><?php echo ucfirst($record['status']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="print-summary" style="margin-top:40px;">
        <div class="summary-stats">
            <p><strong>Total Records:</strong> <?php echo count($attendance); ?> data</p>
        </div>
        <div class="print-signature" style="margin-top:40px;">
            <p>Wonosobo, <?php echo date('d F Y'); ?></p>
            <br><br><br>
            <p>(_____________________)</p>
            <p>Kepala HRD</p>
        </div>
    </div>
</body>
</html>
