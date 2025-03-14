<?php
// Este arquivo apenas lista todos os registros do banco para testes
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $results = $pdo->query("SELECT * FROM wells")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
