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
    SELECT p.*, e.nip, e.name, e.department, e.position 
    FROM placements p 
    JOIN employees e ON p.employee_id = e.id 
    WHERE p.id = ?
");
$stmt->execute([$_GET['id']]);
$placement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$placement) {
    http_response_code(404);
    echo json_encode(['error' => 'Data tidak ditemukan']);
    exit;
}

// Make sure no extra output
ob_clean();
echo json_encode($placement);
exit;
?>
