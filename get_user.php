<?php
// Ensure no output before JSON
ob_start();

require_once 'functions.php';
requireLogin();
requirePermission('user_management', 'lihat');

// Clean any previous output
ob_clean();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak ditemukan']);
    exit;
}

global $pdo;
$stmt = $pdo->prepare("SELECT id, email, name, role, status FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit;
}

// Get user permissions
$permStmt = $pdo->prepare("SELECT module, permission FROM user_permissions WHERE user_id = ?");
$permStmt->execute([$_GET['id']]);
$permissions = $permStmt->fetchAll(PDO::FETCH_ASSOC);

$user['permissions'] = $permissions;

// Make sure no extra output
ob_clean();
echo json_encode($user);
exit;
?>
