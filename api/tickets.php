<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'listar':
        $stmt = $pdo->query("
            SELECT t.*, 
                   c.nombre as cliente_nombre, c.empresa,
                   a.nombre as agente_nombre
            FROM tickets t
            LEFT JOIN clientes c ON t.id_cliente = c.id
            LEFT JOIN agentes a ON t.id_agente = a.id
            ORDER BY t.created_at DESC
        ");
        echo json_encode($stmt->fetchAll());
        break;

    case 'detalle':
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   c.nombre as cliente_nombre, c.empresa, c.email as cliente_email,
                   a.nombre as agente_nombre
            FROM tickets t
            LEFT JOIN clientes c ON t.id_cliente = c.id
            LEFT JOIN agentes a ON t.id_agente = a.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();

        // Traer comentarios del ticket
        $stmt2 = $pdo->prepare("SELECT * FROM comentarios WHERE id_ticket = ? ORDER BY created_at ASC");
        $stmt2->execute([$id]);
        $ticket['comentarios'] = $stmt2->fetchAll();

        echo json_encode($ticket);
        break;

    case 'crear':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("
            INSERT INTO tickets (titulo, descripcion, prioridad, estado, categoria, id_cliente, id_agente)
            VALUES (?, ?, ?, 'abierto', ?, ?, ?)
        ");
        $stmt->execute([
            $data['titulo'],
            $data['descripcion'],
            $data['prioridad'],
            $data['categoria'],
            $data['id_cliente'],
            $data['id_agente'] ?? null
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'actualizar_estado':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE tickets SET estado = ? WHERE id = ?");
        $stmt->execute([$data['estado'], $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'asignar_agente':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE tickets SET id_agente = ? WHERE id = ?");
        $stmt->execute([$data['id_agente'], $data['id']]);
        echo json_encode(['success' => true]);
        break;

    case 'agregar_comentario':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO comentarios (id_ticket, autor, mensaje, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['id_ticket'], $data['autor'], $data['mensaje'], $data['tipo'] ?? 'publico']);
        echo json_encode(['success' => true]);
        break;

    case 'estadisticas':
        $stats = [];
        $stats['total'] = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
        $stats['abiertos'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'abierto'")->fetchColumn();
        $stats['en_progreso'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'en_progreso'")->fetchColumn();
        $stats['resueltos'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'resuelto'")->fetchColumn();
        $stats['criticos'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE prioridad = 'critica'")->fetchColumn();
        echo json_encode($stats);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>