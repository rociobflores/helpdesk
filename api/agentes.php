<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'listar':
        $stmt = $pdo->query("SELECT * FROM agentes ORDER BY nombre ASC");
        echo json_encode($stmt->fetchAll());
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>