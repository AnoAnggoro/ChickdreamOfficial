<?php
// Ensure no output before JSON
ob_start();

require_once 'functions.php';
requireLogin();

// Clean any previous output
ob_clean();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak ditemukan']);
    exit;
}

global $pdo;
$stmt = $pdo->prepare("
    SELECT a.*, e.name, e.department, e.position 
    FROM attendance a 
    LEFT JOIN employees e ON a.nip = e.nip 
    WHERE a.id = ?
");
$stmt->execute([$_GET['id']]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attendance) {
    http_response_code(404);
    echo json_encode(['error' => 'Data tidak ditemukan']);
    exit;
}

// Make sure no extra output
ob_clean();
echo json_encode($attendance);
exit;
?>
