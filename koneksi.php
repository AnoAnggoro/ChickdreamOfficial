<?php
$host = 'sql100.infinityfree.com';
$username = 'if0_39416576';
$password = 'AeVFst3kNKOyD';
$database = 'if0_39416576_chickdream_hr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>