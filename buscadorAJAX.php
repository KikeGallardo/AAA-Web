<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header('Location: index.html');
    exit;
}

include("basedatos.php");

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);
    $sql = "SELECT id, nomArchivo, fecha, tamano FROM archivos WHERE nomArchivo LIKE '%$query%'";
    $result = $conn->query($sql);

    $archivos = [];
    while ($row = $result->fetch_assoc()) {
        $archivos[] = $row;
    }

    echo json_encode($archivos);
}

$conn->close();
?>