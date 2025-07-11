<?php
require_once __DIR__ . '/../functions.php';
requireLogin();
global $pdo;

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "
    SELECT e.nip, e.name, e.department, e.position, e.employment_type, COUNT(a.id) as total_absen
    FROM employees e
    LEFT JOIN attendance a ON a.nip = e.nip
";
$params = [];
$where = [];
if ($date_from) {
    $where[] = "a.date >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = "a.date <= ?";
    $params[] = $date_to;
}
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " GROUP BY e.nip, e.name, e.department, e.position, e.employment_type ORDER BY e.department, e.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penempatan Kerja</title>
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
        <p>Laporan Penempatan Pegawai</p>
        <p>Tanggal Cetak: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="no-print">
        <button onclick="window.print()" class="btn-primary">Print Laporan</button>
        <a href="../laporan.php" class="btn-secondary">Kembali</a>
    </div>

    <?php if (empty($data)): ?>
        <p style="color:red; text-align:center;">Tidak ada data penempatan ditemukan untuk periode ini.</p>
    <?php endif; ?>

    <table>
        <tr>
            <th>No</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Jenis</th>
            <th>Total Absen</th>
        </tr>
        <?php foreach ($data as $i => $row): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['nip']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?: '-' ?></td>
            <td><?= htmlspecialchars($row['department']) ?: '-' ?></td>
            <td><?= htmlspecialchars($row['position']) ?: '-' ?></td>
            <td><?= ucfirst($row['employment_type']) ?: '-' ?></td>
            <td><?= $row['total_absen'] ?: '-' ?></td>
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
