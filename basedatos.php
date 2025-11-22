<?php
$servername = "db-fde-02.apollopanel.com:3306";
$username = "u136076_tCDay64NMd";
$password = "AzlYnjAiSFN!d=ZtajgQa=q.";
$dbname = "s136076_Aribatraje";

// Crear la conexión y configurar el timeout
$conn = new mysqli();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 180);
$conn->real_connect($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    echo "<script>alert(" . json_encode("Conexión fallida: " . $conn->connect_error) . ");</script>";
    die("Conexión fallida: " . $conn->connect_error);
}

// function registrar_log($conn, $usuario, $accion, $descripcion) {
//     $stmt = $conn->prepare("INSERT INTO logs (usuario, accion, descripcion) VALUES (?, ?, ?)");
//     $stmt->bind_param('sss', $usuario, $accion, $descripcion);
//     $stmt->execute();
//     $stmt->close();
// }
?>
