<?php
session_start();

// ✅ CORREGIDO: Sintaxis PHP correcta
if (!isset($_SESSION['cedula'])) {
    echo '<script>alert("No has iniciado sesión");</script>';
    header('Location: login.php');
    exit;
}

include("basedatos.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);

    $stmt = $conn->prepare("SELECT * FROM arbitro 
                           WHERE nombre LIKE ? 
                              OR apellido LIKE ?
                              OR cedula LIKE ?
                              OR correo LIKE ?
                              OR telefono LIKE ?
                              OR categoriaArbitro LIKE ?");
    $searchTerm = "%$query%";
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $arbitros = [];
    while ($row = $result->fetch_assoc()) {
        $arbitros[] = $row;
    }
    $stmt->close();

    echo json_encode($arbitros);
}

$conn->close();
?>