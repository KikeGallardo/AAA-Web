<?php
header('Content-Type: application/json');
session_start();

// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'No autorizado']);
//     exit;
// }

include('basedatos.php');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            obtenerEventos($conn);
        }
        break;
    
    case 'POST':
        if ($action === 'create') {
            crearEvento($conn);
        }
        break;
    
    case 'PUT':
        if ($action === 'update') {
            actualizarEvento($conn);
        }
        break;
    
    case 'DELETE':
        if ($action === 'delete') {
            eliminarEvento($conn);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

function obtenerEventos($conn) {
    $sql = "SELECT * FROM torneo ORDER BY fecha ASC";
    $result = $conn->query($sql);
    
    $eventos = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $eventos[] = [
                'id' => $row['id'],
                'tournament' => $row['torneo'],
                'teamA' => $row['equipo_a'],
                'teamB' => $row['equipo_b'],
                'datetime' => $row['fecha'],
                'categoria' => $row['categoria'],
                'arbitros1' => $row['arbitro1'],
                'arbitros2' => $row['arbitro2'],
                'arbitros3' => $row['arbitro3'],
                'arbitros4' => $row['arbitro4'],
                'cancha' => $row['cancha']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $eventos]);
}

function crearEvento($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("INSERT INTO torneo (torneo, equipo_a, equipo_b, fecha, categoria, arbitro1, arbitro2, arbitro3, arbitro4, cancha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param('ssssssssssss', 
        $data['tournament'], 
        $data['teamA'], 
        $data['teamB'], 
        $data['datetime'], 
        $data['categoria'],
        $data['arbitros1'],
        $data['arbitros2'],
        $data['arbitros3'],
        $data['arbitros4'],
        $data['cancha']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear evento']);
    }
    
    $stmt->close();
}

function actualizarEvento($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("UPDATE torneo SET torneo=?, equipo_a=?, equipo_b=?, fecha=?, categoria=?, arbitro1=?, arbitro2=?, arbitro3=?, arbitro4=?, cancha=? WHERE id=?");
    
    $stmt->bind_param('ssssssssssssi', 
        $data['teamA'], 
        $data['teamB'], 
        $data['date'], 
        $data['hour'], 
        $data['tournament'], 
        $data['categoria'],
        $data['arbitros1'],
        $data['arbitros2'],
        $data['arbitros3'],
        $data['arbitros4'],
        $data['cancha'],
        $data['color'],
        $data['id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar evento']);
    }
    
    $stmt->close();
}

function eliminarEvento($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'];
    
    $stmt = $conn->prepare("DELETE FROM partidos WHERE id=?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar evento']);
    }
    
    $stmt->close();
}

$conn->close();
?>