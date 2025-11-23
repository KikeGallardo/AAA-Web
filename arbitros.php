
<?php
$conexion = new mysqli("db-fde-02.apollopanel.com:3306", "u136076_tCDay64NMd", "AzlYnjAiSFN!d=ZtajgQa=q.", "s136076_Aribatraje");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// ----------------------
// 2. REGISTRAR ÁRBITRO
// ----------------------
if (isset($_POST['registrar'])) {
    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cedula   = $_POST['cedula'];
    $fechaNac = $_POST['fechaNacimiento'];
    $correo   = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $categoria = $_POST['categoriaArbitro'];

    $conexion->query("
        INSERT INTO arbitro (nombre, apellido, cedula, fechaNacimiento, correo, telefono, categoriaArbitro)
        VALUES ('$nombre','$apellido','$cedula','$fechaNac','$correo','$telefono','$categoria')
    ");
}

// ----------------------
// 3. ELIMINAR ÁRBITRO
// ----------------------
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    $conexion->query("DELETE FROM arbitro WHERE idArbitro = $id");
}

// ----------------------
// 4. EDITAR ÁRBITRO
// ----------------------
if (isset($_POST['editar'])) {
    $id       = $_POST['id'];
    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cedula   = $_POST['cedula'];
    $fechaNac = $_POST['fechaNacimiento'];
    $correo   = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $categoria = $_POST['categoriaArbitro'];

    $conexion->query("
        UPDATE arbitro 
        SET nombre='$nombre', apellido='$apellido', cedula='$cedula',
            fechaNacimiento='$fechaNac', correo='$correo',
            telefono='$telefono', categoriaArbitro='$categoria'
        WHERE idArbitro=$id
    ");
}

// ------------------------------------
// PAGINACIÓN
// ------------------------------------
$registrosPorPagina = 5;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max($pagina, 1);
$offset = ($pagina - 1) * $registrosPorPagina;

// ----------------------
// 5. CONSULTAR ÁRBITROS (con búsqueda y límite)
// ----------------------
$busqueda = "";
$where = "";

if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = $conexion->real_escape_string($_GET['buscar']);

    $where = "WHERE nombre LIKE '%$busqueda%' 
              OR apellido LIKE '%$busqueda%'
              OR cedula LIKE '%$busqueda%'
              OR fechaNacimiento LIKE '%$busqueda%'
              OR correo LIKE '%$busqueda%'
              OR telefono LIKE '%$busqueda%'
              OR categoriaArbitro LIKE '%$busqueda%'";
}

$totalQuery = $conexion->query("SELECT COUNT(*) AS total FROM arbitro $where");
$totalRegistros = $totalQuery->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$arbitros = $conexion->query("
    SELECT * FROM arbitro 
    $where 
    ORDER BY idArbitro DESC 
    LIMIT $registrosPorPagina OFFSET $offset
");
?>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbitros</title>
    <link rel="stylesheet" href="assets/css/torneo.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<script>
function editarArbitro(id, nombre, apellido, cedula, fechaNacimiento, correo, telefono, categoria) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_apellido").value = apellido;
    document.getElementById("edit_cedula").value = cedula;
    document.getElementById("edit_fechaNacimiento").value = fechaNacimiento;
    document.getElementById("edit_correo").value = correo;
    document.getElementById("edit_telefono").value = telefono;
    document.getElementById("edit_categoria").value = categoria;
    document.getElementById("modalEditar").style.display = "flex";
}
</script>

<body>

<div class="subtitulo">
    <h1>ARBITROS</h1>
</div>

<!-- FORMULARIO REGISTRO -->
<div class="form-container" style="width:80%; margin:auto; background:#fff; padding:20px; border-radius:15px; 
     box-shadow:0 10px 20px rgba(0,0,0,0.2);">
    <h2 style="text-align:center; margin-bottom:15px;">Registrar Árbitro</h2>

    <form method="POST" class="form-arbitro" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
        
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="text" name="apellido" placeholder="Apellido" required>
        <input type="text" name="cedula" placeholder="Cédula" required>
        <input type="date" name="fechaNacimiento" required>
        <input type="email" name="correo" placeholder="Correo">
        <input type="text" name="telefono" placeholder="Teléfono" required>

        <select name="categoriaArbitro" required>
            <option value="">Seleccione categoría</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
            <option value="ASPIRANTE">ASPIRANTE</option>
            <option value="DEPARTAMENTAL A">DEPARTAMENTAL A</option>
            <option value="DEPARTAMENTAL B">DEPARTAMENTAL B</option>
            <option value="EXPROFESIONAL">EXPROFESIONAL</option>
            <option value="FEMENINA">FEMENINA</option>
            <option value="FEMENINO PROFESIONAL">FEMENINO PROFESIONAL</option>
            <option value="FUTBOL PLAYA">FUTBOL PLAYA</option>
            <option value="FUTSAL DEPARTAMENTAL">FUTSAL DEPARTAMENTAL</option>
            <option value="FUTSAL PROFESIONAL">FUTSAL PROFESIONAL</option>
            <option value="MASTER">MASTER</option>
</select>

        <button type="submit" name="registrar" 
            style="grid-column: span 3; padding:10px; background:#0096C7; color:#fff; border:none; 
                   border-radius:10px; cursor:pointer; font-size:18px;">
            Registrar Árbitro
        </button>
    </form>
</div>

<!-- BÚSQUEDA -->
<div style="width:80%; margin:20px auto; text-align:center;">
    <form method="GET" style="display:flex; justify-content:center; gap:10px;">
        <input type="text" name="buscar" placeholder="Buscar árbitro..." 
               value="<?= isset($_GET['buscar']) ? $_GET['buscar'] : '' ?>"
               style="width:50%; padding:10px; border-radius:10px; border:1px solid #aaa;">
        <button type="submit" 
                style="padding:10px 20px; background:#0096C7; color:#fff; border:none; border-radius:10px;">
            Buscar
        </button>
    </form>
</div>

<table border="1" class="cuerpoTabla">
<thead>
    <tr>
        <th>Opciones</th>
        <th>Nombre</th>
        <th>Apellido</th>
        <th>Cédula</th>
        <th>Fecha Nac.</th>
        <th>Correo</th>
        <th>Teléfono</th>
        <th>Categoría</th>
    </tr>
</thead>

<tbody id="cuerpoTabla">
<?php while ($row = $arbitros->fetch_assoc()) { ?>
<tr>
    <td class="botonesfile">

        <!-- EDITAR -->
        <button onclick="editarArbitro(
            <?= $row['idArbitro'] ?>,
            '<?= $row['nombre'] ?>',
            '<?= $row['apellido'] ?>',
            '<?= $row['cedula'] ?>',
            '<?= $row['fechaNacimiento'] ?>',
            '<?= $row['correo'] ?>',
            '<?= $row['telefono'] ?>',
            '<?= $row['categoriaArbitro'] ?>'
        )" 
        class="verbtn"><i class="material-icons">edit</i></button>

        <!-- ELIMINAR -->
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $row['idArbitro'] ?>">
            <button class="verelbtn" type="submit" name="eliminar">
                <i class="material-icons">delete</i>
            </button>
        </form>
    </td>

    <td><?= $row['nombre'] ?></td>
    <td><?= $row['apellido'] ?></td>
    <td><?= $row['cedula'] ?></td>
    <td><?= $row['fechaNacimiento'] ?></td>
    <td><?= $row['correo'] ?></td>
    <td><?= $row['telefono'] ?></td>
    <td><?= $row['categoriaArbitro'] ?></td>
</tr>
<?php } ?>
</tbody>
</table>

<!-- PAGINACIÓN -->
<div style="width:80%; margin:20px auto; text-align:center;">
<?php if ($pagina > 1): ?>
<a href="?pagina=<?= $pagina - 1 ?>&buscar=<?= $busqueda ?>" 
   style="padding:10px 15px; background:#0096C7; color:white; border-radius:8px; text-decoration:none;">
   ⬅ Anterior
</a>
<?php endif; ?>

<?php
$start = max(1, $pagina - 2);
$end = min($totalPaginas, $pagina + 2);
for ($i = $start; $i <= $end; $i++): ?>
<a href="?pagina=<?= $i ?>&buscar=<?= $busqueda ?>"
   style="padding:10px 15px; margin:5px; 
          background:<?= $i == $pagina ? '#0077B6' : '#0096C7' ?>; 
          color:white; border-radius:8px; text-decoration:none;">
   <?= $i ?>
</a>
<?php endfor; ?>

<?php if ($pagina < $totalPaginas): ?>
<a href="?pagina=<?= $pagina + 1 ?>&buscar=<?= $busqueda ?>"
   style="padding:10px 15px; background:#0096C7; color:white; border-radius:8px;">
   Siguiente ➡
</a>
<?php endif; ?>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
     background:rgba(0,0,0,0.4); justify-content:center; align-items:center;">

    <div style="background:#fff; padding:20px; border-radius:15px; width:400px;">
        <h2 style="text-align:center;">Editar Árbitro</h2>

        <form method="POST">
            <input type="hidden" name="id" id="edit_id">

            <input type="text" id="edit_nombre" name="nombre" style="width:100%; margin:5px 0;">
            <input type="text" id="edit_apellido" name="apellido" style="width:100%; margin:5px 0;">
            <input type="text" id="edit_cedula" name="cedula" style="width:100%; margin:5px 0;">
            <input type="date" id="edit_fechaNacimiento" name="fechaNacimiento" style="width:100%; margin:5px 0;">
            <input type="email" id="edit_correo" name="correo" style="width:100%; margin:5px 0;">
            <input type="text" id="edit_telefono" name="telefono" style="width:100%; margin:5px 0;">

            <select id="edit_categoria" name="categoriaArbitro" style="width:100%; margin:5px 0;">
                <option value="">Seleccione categoría</option>
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="D">D</option>
    <option value="ASPIRANTE">ASPIRANTE</option>
    <option value="DEPARTAMENTAL A">DEPARTAMENTAL A</option>
    <option value="DEPARTAMENTAL B">DEPARTAMENTAL B</option>
    <option value="EXPROFESIONAL">EXPROFESIONAL</option>
    <option value="FEMENINA">FEMENINA</option>
    <option value="FEMENINO PROFESIONAL">FEMENINO PROFESIONAL</option>
    <option value="FUTBOL PLAYA">FUTBOL PLAYA</option>
    <option value="FUTSAL DEPARTAMENTAL">FUTSAL DEPARTAMENTAL</option>
    <option value="FUTSAL PROFESIONAL">FUTSAL PROFESIONAL</option>
    <option value="MASTER">MASTER</option>
            </select>
            <button name="editar" style="width:100%; padding:10px; background:#0096C7; color:#fff; border:none; border-radius:10px;">
                Guardar Cambios
            </button>
        </form>
        <button onclick="document.getElementById('modalEditar').style.display='none'"
            style="margin-top:10px; width:100%; padding:10px; background:#d32f2f; color:#fff; border:none; border-radius:10px;">
            Cancelar
        </button>
    </div>
</div>
</body>
</html>
