<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'listar':
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'crear':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, empresa, telefono) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nombre'], $data['email'], $data['empresa'], $data['telefono']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>