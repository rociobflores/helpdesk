<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$accion = $_GET['action'] ?? '';
$usuario_actual = $_SESSION['usuario'];

switch ($accion) {

    case 'perfil':
        $stmt = $pdo->prepare("SELECT id, nombre, email, rol, avatar FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_actual['id']]);
        echo json_encode($stmt->fetch());
        break;

    case 'actualizar_perfil':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
        $stmt->execute([$data['nombre'], $data['email'], $usuario_actual['id']]);
        $_SESSION['usuario']['nombre'] = $data['nombre'];
        $_SESSION['usuario']['email'] = $data['email'];
        echo json_encode(['success' => true]);
        break;

    case 'cambiar_password':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_actual['id']]);
        $row = $stmt->fetch();
        if (!password_verify($data['password_actual'], $row['password'])) {
            echo json_encode(['error' => 'Contraseña actual incorrecta']);
            break;
        }
        $nuevo_hash = password_hash($data['password_nuevo'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->execute([$nuevo_hash, $usuario_actual['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'listar':
        if ($usuario_actual['rol'] !== 'admin') {
            echo json_encode(['error' => 'Sin permisos']);
            break;
        }
        $stmt = $pdo->query("SELECT id, nombre, email, rol, avatar, created_at FROM usuarios ORDER BY nombre ASC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'crear':
        if ($usuario_actual['rol'] !== 'admin') {
            echo json_encode(['error' => 'Sin permisos']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nombre'], $data['email'], $hash, $data['rol']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'eliminar':
        if ($usuario_actual['rol'] !== 'admin') {
            echo json_encode(['error' => 'Sin permisos']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data['id'] == $usuario_actual['id']) {
            echo json_encode(['error' => 'No podés eliminarte a vos mismo']);
            break;
        }
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>