<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include("basedatos.php");
    $cedula = $_POST['cedula'];

    $stmt = $conn->prepare("SELECT cedula FROM usuario WHERE cedula = ?");
    $stmt->bind_param('i', $cedula); // Usar 'i' para integer
    $stmt->execute();
    $stmt->store_result();
	
    // Verificar si se encontró algún registro
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($fetched_id);
        $stmt->fetch();
        $stmt->close();
        
        echo "<script>console.log(" . json_encode("Fetched ID: " . $fetched_id) . ");</script>";
        
        $_SESSION['user_id'] = $fetched_id;
        header("Location: dashboard.php");
        exit();
    } else {
        $stmt->close();
        $error = 'Número de identificación no válido.';
        echo "<script>alert(" . json_encode($error) . ");</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-wrap">
	<div class="login-html">
		<input id="tab-1" type="radio" name="tab" class="sign-in" checked><label for="tab-1" class="tab">INICIAR SESIÓN</label>
		<input id="tab-2" type="radio" name="tab" class="sign-up"><label for="tab-2" class="tab"></label>
		<div class="login-form">
			<div class="sign-in-htm">
				<form method="POST" action="login.php">
					<div class="group">
						<label for="cedula" class="label">Número de identificación</label>
						<input id="cedula" type="text" class="input" name="cedula" required>
					</div>
					<div class="group">
						<input type="submit" class="button" value="Ingresar">
					</div>
					<div class="hr"></div>
				</form>
			</div>
		</div>
	</div>
</div>
</body>
</html>
