<?php
session_start();

if (!isset($_SESSION['cedula'])) {
    <script>alert("No has iniciado sesión;")</script>
    header('Location: login.php');
    exit;
}

include("basedatos.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);
    $sql = "WHERE nombre LIKE '%$busqueda%' 
              OR apellido LIKE '%$busqueda%'
              OR cedula LIKE '%$busqueda%'
              OR fechaNacimiento LIKE '%$busqueda%'
              OR correo LIKE '%$busqueda%'
              OR telefono LIKE '%$busqueda%'
              OR categoriaArbitro LIKE '%$busqueda%";
    $result = $conn->query($sql);

    $arbitros = [];
    while ($row = $result->fetch_assoc()) {
        $arbitros[] = $row;
    }

    echo json_encode($arbitros);
}

$conn->close();
?>