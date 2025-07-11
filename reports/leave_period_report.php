<?php
require_once 'functions.php';
requireLogin();
global $pdo;

$sql = "
    SELECT nip, name, department, position, employment_type, grace_period,
           DATEDIFF(grace_period, CURDATE()) as days_remaining
    FROM employees
    WHERE employment_type IN ('contract', 'intern') AND grace_period IS NOT NULL
    ORDER BY grace_period ASC
";
$data = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Masa Tenggang Kerja</title>
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
        <p>Laporan Masa Tenggang Kerja</p>
        <p>Tanggal Cetak: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="no-print">
        <button onclick="window.print()" class="btn-primary">Print Laporan</button>
        <a href="laporan.php" class="btn-secondary">Kembali</a>
    </div>

    <table>
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Jenis</th>
            <th>Batas Tenggang</th>
            <th>Sisa Hari</th>
        </tr>
        <?php foreach ($data as $i => $row): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['nip']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['department']) ?></td>
            <td><?= htmlspecialchars($row['position']) ?></td>
            <td><?= ucfirst($row['employment_type']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['grace_period'])) ?></td>
            <td>
                <?php
                if ($row['days_remaining'] > 0) {
                    echo $row['days_remaining'] . ' hari';
                } else {
                    echo '<span style="color:#e74c3c;font-weight:bold;">Habis</span>';
                }
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="print-summary" style="margin-top:40px;">
        <div class="summary-stats">
            <p><strong>Total Records:</strong> <?php echo count($data); ?> data</p>
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
