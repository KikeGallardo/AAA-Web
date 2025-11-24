
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

    if ($conexion->query("
        INSERT INTO arbitro (nombre, apellido, cedula, fechaNacimiento, correo, telefono, categoriaArbitro)
        VALUES ('$nombre','$apellido','$cedula','$fechaNac','$correo','$telefono','$categoria')
    ")) {
        echo "<script>alert('Árbitro registrado correctamente.');</script>";
    } else {
        echo "<script>alert('Error al registrar árbitro: ".$conexion->error."');</script>";
    }
}

// ----------------------
// 3. ELIMINAR ÁRBITRO
// ----------------------
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];

    if ($conexion->query("DELETE FROM arbitro WHERE idArbitro = $id")) {
        echo "<script>alert('Árbitro eliminado correctamente');</script>";
    } else {
        echo "<script>alert('Error al eliminar: ".$conexion->error."');</script>";
    }
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

    if ($conexion->query("
        UPDATE arbitro 
        SET nombre='$nombre', apellido='$apellido', cedula='$cedula',
            fechaNacimiento='$fechaNac', correo='$correo',
            telefono='$telefono', categoriaArbitro='$categoria'
        WHERE idArbitro=$id
    ")) {
        echo "<script>alert('Datos actualizados correctamente');</script>";
    } else {
        echo "<script>alert('Error al actualizar: ".$conexion->error."');</script>";
    }
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
<?php require_once "assets/header.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arbitros</title>
    <link rel="stylesheet" href="assets/css/arbitros.css">
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
<div class="form-container">
    <h2>Registrar árbitro</h2>
    <form method="POST" class="form-arbitro">
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

        <button type="submit" name="registrar" class="btn-registrar">
            Registrar árbitro
        </button>
    </form>
</div>

<!-- BÚSQUEDA -->
<div class="buscar-container">
    <form method="GET" class="busqueda-form">
        <input type="text" name="buscar" placeholder="Buscar árbitro..."
               value="<?= isset($_GET['buscar']) ? $_GET['buscar'] : '' ?>">
        <button type="submit">Buscar</button>
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
    <button 
        onclick="editarArbitro(
            <?= $row['idArbitro'] ?>,
            '<?= $row['nombre'] ?>',
            '<?= $row['apellido'] ?>',
            '<?= $row['cedula'] ?>',
            '<?= $row['fechaNacimiento'] ?>',
            '<?= $row['correo'] ?>',
            '<?= $row['telefono'] ?>',
            '<?= $row['categoriaArbitro'] ?>'
        )" 
        class="btn-editar"
    >
        <span class="material-icons-outlined">✏️</span>
    </button>

    <form method="POST" class="form-eliminar">
        <input type="hidden" name="id" value="<?= $row['idArbitro'] ?>">
        <button class="btn-eliminar" type="submit" name="eliminar">
            <span class="material-icons-outlined">🗑️</span>
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
<div class="paginacion">
<?php if ($pagina > 1): ?>
<a href="?pagina=<?= $pagina - 1 ?>&buscar=<?= $busqueda ?>" class="btn-nav">⬅ Anterior</a>
<?php endif; ?>

<?php
$start = max(1, $pagina - 2);
$end = min($totalPaginas, $pagina + 2);
for ($i = $start; $i <= $end; $i++): ?>
<a href="?pagina=<?= $i ?>&buscar=<?= $busqueda ?>" 
   class="btn-page <?= $i == $pagina ? 'active' : '' ?>">
   <?= $i ?>
</a>
<?php endfor; ?>

<?php if ($pagina < $totalPaginas): ?>
<a href="?pagina=<?= $pagina + 1 ?>&buscar=<?= $busqueda ?>" class="btn-nav">Siguiente ➡</a>
<?php endif; ?>
</div>

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <h2>Editar Árbitro</h2>

        <form method="POST">
            <input type="hidden" name="id" id="edit_id">

            <input type="text" id="edit_nombre" name="nombre">
            <input type="text" id="edit_apellido" name="apellido">
            <input type="text" id="edit_cedula" name="cedula">
            <input type="date" id="edit_fechaNacimiento" name="fechaNacimiento">
            <input type="email" id="edit_correo" name="correo">
            <input type="text" id="edit_telefono" name="telefono">

            <select id="edit_categoria" name="categoriaArbitro">
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

            <button name="editar" class="btn-guardar">Guardar Cambios</button>
        </form>

        <button onclick="document.getElementById('modalEditar').style.display='none'"
            class="btn-cancelar">
            Cancelar
        </button>
    </div>
</div>
<script>
// VALIDAR FORMULARIO DE REGISTRO
document.querySelector(".form-arbitro").addEventListener("submit", function(e) {
    let nombre = document.querySelector("input[name='nombre']").value.trim();
    let apellido = document.querySelector("input[name='apellido']").value.trim();
    let cedula = document.querySelector("input[name='cedula']").value.trim();
    let telefono = document.querySelector("input[name='telefono']").value.trim();
    let categoria = document.querySelector("select[name='categoriaArbitro']").value;

    if (nombre.length < 2) {
        alert("El nombre debe tener mínimo 2 caracteres.");
        e.preventDefault();
        return;
    }

    if (apellido.length < 2) {
        alert("El apellido debe tener mínimo 2 caracteres.");
        e.preventDefault();
        return;
    }

    if (!/^[0-9A-Za-z\-]+$/.test(cedula)) {
        alert("La cédula contiene caracteres inválidos.");
        e.preventDefault();
        return;
    }

    if (!/^[0-9]{6,9}$/.test(telefono)) {
        alert("El teléfono debe contener solo números (6 a 9 dígitos).");
        e.preventDefault();
        return;
    }

    if (categoria === "") {
        alert("Debe seleccionar una categoría.");
        e.preventDefault();
        return;
    }
});
</script>
<script>
document.querySelectorAll(".form-eliminar").forEach(form => {
    form.addEventListener("submit", function(e){
        if (!confirm("¿Seguro que desea eliminar este árbitro?")) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>

