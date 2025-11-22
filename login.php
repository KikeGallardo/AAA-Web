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
				<div class="group">
					<label for="user" class="label">Número de identificación</label>
					<input id="user" type="text" class="input">
				</div>
				<div class="group">
					<input type="submit" class="button" value="Ingresar">
				</div>
				<div class="hr"></div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>

<?php
// Autenticación de usuario 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	include("basedatos.php");
	session_start();
	$user_id = $_POST['user'] ?? '';
	
	if (empty($user_id)) {
		echo "gey el q lo lea";
		exit;
	}
	
	$_SESSION['user_id'] = $user_id;
	header('Location: dashboard.php');
	exit;
}
?>