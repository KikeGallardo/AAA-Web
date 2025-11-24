<<<<<<< HEAD
<?php require_once "assets/header.php"; ?>
<html lang="en">
=======
<?php
session_start();
require_once 'config.php';

// Obtener conexión segura
$conexion = getDBConnection();

// ----------------------
// 1) REGISTRAR TORNEO + CATEGORÍAS
// ----------------------
if (isset($_POST['registrarTorneo'])) {
    $nombreTorneo = trim($_POST['nombreTorneo'] ?? '');

    if ($nombreTorneo === '') {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El nombre del torneo es obligatorio'];
    } else {
        $conexion->begin_transaction();
        try {
            // Insertar torneo con prepared statement
            $stmt = $conexion->prepare("INSERT INTO torneo (nombreTorneo) VALUES (?)");
            $stmt->bind_param("s", $nombreTorneo);
            $stmt->execute();
            $idTorneo = $stmt->insert_id;
            $stmt->close();

            // Leer arrays de categorías
            $nombres = $_POST['nombreCategoria'] ?? [];
            $p1 = $_POST['pago1'] ?? [];
            $p2 = $_POST['pago2'] ?? [];
            $p3 = $_POST['pago3'] ?? [];
            $p4 = $_POST['pago4'] ?? [];

            // Preparar statements para categorías
            $insCat = $conexion->prepare(
                "INSERT INTO categoriaPagoArbitro (nombreCategoria, pagoArbitro1, pagoArbitro2, pagoArbitro3, pagoArbitro4) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $insRel = $conexion->prepare(
                "INSERT INTO torneo_categoria (idTorneo, idCategoriaPagoArbitro) VALUES (?, ?)"
            );

            $categoriaCount = 0;
            for ($i = 0; $i < count($nombres); $i++) {
                $nc = trim($nombres[$i]);
                if ($nc === '') continue;

                $pg1 = max(0, (int)($p1[$i] ?? 0));
                $pg2 = max(0, (int)($p2[$i] ?? 0));
                $pg3 = max(0, (int)($p3[$i] ?? 0));
                $pg4 = max(0, (int)($p4[$i] ?? 0));

                $insCat->bind_param("siiii", $nc, $pg1, $pg2, $pg3, $pg4);
                $insCat->execute();
                $idCategoria = $insCat->insert_id;

                $insRel->bind_param("ii", $idTorneo, $idCategoria);
                $insRel->execute();
                
                $categoriaCount++;
            }

            $insCat->close();
            $insRel->close();

            $conexion->commit();
            $_SESSION['mensaje'] = [
                'tipo' => 'success', 
                'texto' => "Torneo '{$nombreTorneo}' registrado con {$categoriaCount} categoría(s)"
            ];
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error al guardar torneo: " . $e->getMessage());
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al guardar el torneo'];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------
// 2) AGREGAR CATEGORÍA A TORNEO
// ----------------------
if (isset($_POST['agregarCategoria'])) {
    $idTorneo = (int)($_POST['idTorneo'] ?? 0);
    $nombre = trim($_POST['nombreCategoria'] ?? '');
    $p1 = max(0, (int)($_POST['pago1'] ?? 0));
    $p2 = max(0, (int)($_POST['pago2'] ?? 0));
    $p3 = max(0, (int)($_POST['pago3'] ?? 0));
    $p4 = max(0, (int)($_POST['pago4'] ?? 0));

    if ($idTorneo <= 0 || $nombre === '') {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare(
            "INSERT INTO categoriaPagoArbitro (nombreCategoria, pagoArbitro1, pagoArbitro2, pagoArbitro3, pagoArbitro4) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("siiii", $nombre, $p1, $p2, $p3, $p4);
        $stmt->execute();
        $idCategoria = $stmt->insert_id;
        $stmt->close();

        $stmt2 = $conexion->prepare("INSERT INTO torneo_categoria (idTorneo, idCategoriaPagoArbitro) VALUES (?, ?)");
        $stmt2->bind_param("ii", $idTorneo, $idCategoria);
        $stmt2->execute();
        $stmt2->close();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Categoría agregada correctamente']);
    } catch (Exception $e) {
        $conexion->rollback();
        error_log("Error al agregar categoría: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al agregar categoría']);
    }
    exit;
}

// ----------------------
// 3) ELIMINAR TORNEO
// ----------------------
if (isset($_POST['eliminarTorneo'])) {
    $id = (int)($_POST['idTorneo'] ?? 0);
    if ($id > 0) {
        $stmt = $conexion->prepare("DELETE FROM torneo WHERE idTorneo = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Torneo eliminado correctamente'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al eliminar torneo'];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------
// 4) EDITAR NOMBRE TORNEO
// ----------------------
if (isset($_POST['editarTorneo'])) {
    $id = (int)($_POST['idTorneo'] ?? 0);
    $nombre = trim($_POST['nombreTorneoEdit'] ?? '');
    if ($id > 0 && $nombre !== '') {
        $stmt = $conexion->prepare("UPDATE torneo SET nombreTorneo = ? WHERE idTorneo = ?");
        $stmt->bind_param("si", $nombre, $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Torneo actualizado correctamente'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al actualizar torneo'];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------
// 5) EDITAR CATEGORÍA
// ----------------------
if (isset($_POST['editarCategoria'])) {
    $idCat = (int)($_POST['idCategoria'] ?? 0);
    $nombreCat = trim($_POST['nombreCategoriaEdit'] ?? '');
    $pg1 = max(0, (int)($_POST['pago1Edit'] ?? 0));
    $pg2 = max(0, (int)($_POST['pago2Edit'] ?? 0));
    $pg3 = max(0, (int)($_POST['pago3Edit'] ?? 0));
    $pg4 = max(0, (int)($_POST['pago4Edit'] ?? 0));

    if ($idCat > 0 && $nombreCat !== '') {
        $stmt = $conexion->prepare(
            "UPDATE categoriaPagoArbitro 
             SET nombreCategoria=?, pagoArbitro1=?, pagoArbitro2=?, pagoArbitro3=?, pagoArbitro4=? 
             WHERE idCategoriaPagoArbitro=?"
        );
        $stmt->bind_param("siiiii", $nombreCat, $pg1, $pg2, $pg3, $pg4, $idCat);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
        $stmt->close();
    }
    exit;
}

// ----------------------
// 6) ELIMINAR CATEGORÍA
// ----------------------
if (isset($_POST['eliminarCategoria'])) {
    $idCat = (int)($_POST['idCategoria'] ?? 0);
    if ($idCat > 0) {
        $conexion->begin_transaction();
        try {
            // Eliminar relaciones
            $stmt1 = $conexion->prepare("DELETE FROM torneo_categoria WHERE idCategoriaPagoArbitro = ?");
            $stmt1->bind_param("i", $idCat);
            $stmt1->execute();
            $stmt1->close();
            
            // Eliminar categoría
            $stmt2 = $conexion->prepare("DELETE FROM categoriaPagoArbitro WHERE idCategoriaPagoArbitro = ?");
            $stmt2->bind_param("i", $idCat);
            $stmt2->execute();
            $stmt2->close();
            
            $conexion->commit();
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada']);
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error al eliminar categoría: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
    }
    exit;
}

// ------------------------------------
// 7) PAGINACIÓN + BÚSQUEDA
// ------------------------------------
$registrosPorPagina = 5;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = "";
$where = "";
$bindTypes = "";
$bindValues = [];

if (isset($_GET['buscar']) && trim($_GET['buscar']) !== '') {
    $busqueda = trim($_GET['buscar']);
    $where = "WHERE t.nombreTorneo LIKE ?";
    $bindTypes = "s";
    $bindValues[] = "%{$busqueda}%";
}

// Total de registros
$totalQuery = $conexion->prepare("SELECT COUNT(*) AS total FROM torneo t $where");
if ($where) {
    $totalQuery->bind_param($bindTypes, ...$bindValues);
}
$totalQuery->execute();
$totalRegistros = (int)$totalQuery->get_result()->fetch_assoc()['total'];
$totalPaginas = max(1, ceil($totalRegistros / $registrosPorPagina));
$totalQuery->close();

// Listado con conteo de categorías
$sql = "
    SELECT t.idTorneo, t.nombreTorneo, 
           COUNT(tc.idCategoriaPagoArbitro) AS categorias
    FROM torneo t
    LEFT JOIN torneo_categoria tc ON tc.idTorneo = t.idTorneo
    $where
    GROUP BY t.idTorneo
    ORDER BY t.idTorneo DESC
    LIMIT ? OFFSET ?
";

$stmt = $conexion->prepare($sql);
if ($where) {
    $bindValues[] = $registrosPorPagina;
    $bindValues[] = $offset;
    $stmt->bind_param($bindTypes . "ii", ...$bindValues);
} else {
    $stmt->bind_param("ii", $registrosPorPagina, $offset);
}
$stmt->execute();
$torneos = $stmt->get_result();
?>
<!doctype html>
<html lang="es">
>>>>>>> f24cdbd064a26f9d9d389b4bb44064245cfce15f
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestión de Torneos</title>
<link rel="stylesheet" href="assets/css/torneo.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<<<<<<< HEAD
    <div class="subtitulo">
        <h1>TORNEO</h1>
=======
<header>
    <nav class="nav_bar_upper">
        <ul class="nav_links">
            <li><a href="dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></a></li>
        </ul>
        <ul class="nav_links">
            <li><a href="calendario.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 2.994v2.25m10.5-2.25v2.25m-14.252 13.5V7.491a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v11.251m-18 0a2.25 2.25 0 0 0 2.25 2.25h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-6.75-6h2.25m-9 2.25h4.5m.002-2.25h.005v.006H12v-.006Zm-.001 4.5h.006v.006h-.006v-.005Zm-2.25.001h.005v.006H9.75v-.006Zm-2.25 0h.005v.005h-.006v-.005Zm6.75-2.247h.005v.005h-.005v-.005Zm0 2.247h.006v.006h-.006v-.006Zm2.25-2.248h.006V15H16.5v-.005Z" /></svg></a></li>
        </ul>
        <ul class="nav_links">
            <li><a href="programar.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg></a></li>
        </ul>
        <ul class="nav_links">
            <li><a href="torneo.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21h7.5M12 17.25v3.75M15.75 4.5h3a.75.75 0 0 1 .75.75v2.25a4.5 4.5 0 0 1-4.5 4.5M8.25 4.5h-3a.75.75 0 0 0-.75.75v2.25a4.5 4.5 0 0 0 4.5 4.5m6.75-7.5a3.75 3.75 0 1 1-7.5 0m7.5 0h-7.5" /></svg></a></li>
        </ul>
        <ul class="nav_links">
            <li><a href="arbitros.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg></a></li>
        </ul>
    </nav>
</header>

<!-- Notificaciones -->
<?php if (isset($_SESSION['mensaje'])): ?>
<div id="toast" class="toast toast-<?= $_SESSION['mensaje']['tipo'] ?>">
    <?= h($_SESSION['mensaje']['texto']) ?>
</div>
<script>
    setTimeout(() => {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
</script>
<?php 
    unset($_SESSION['mensaje']);
endif; 
?>

<div class="subtitulo"><h1>GESTIÓN DE TORNEOS</h1></div>

<!-- FORMULARIO REGISTRO -->
<div class="form-container">
    <h2>Registrar Nuevo Torneo</h2>
    <form method="POST" id="formTorneo" class="form-torneo">
        <input type="text" name="nombreTorneo" placeholder="Nombre del torneo" required maxlength="200">

        <h3>Categorías del Torneo</h3>
        <div id="categoriasContainer">
            <div class="categoria-item">
                <input type="text" name="nombreCategoria[]" placeholder="Nombre de categoría" required maxlength="100">
                <div class="pago-grid">
                    <input type="number" name="pago1[]" placeholder="Pago Árbitro Principal" required min="0" step="0.01">
                    <input type="number" name="pago2[]" placeholder="Pago Asistente 1" min="0" step="0.01">
                    <input type="number" name="pago3[]" placeholder="Pago Asistente 2" min="0" step="0.01">
                    <input type="number" name="pago4[]" placeholder="Pago Cuarto Árbitro" min="0" step="0.01">
                </div>
                <button type="button" class="btn-remove" onclick="removeCategoria(this)">Eliminar</button>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:10px;">
            <button type="button" class="btn-registrar" onclick="addCategoria()">+ Agregar Categoría</button>
            <button type="submit" name="registrarTorneo" class="btn-registrar">Registrar Torneo</button>
        </div>
    </form>
</div>

<!-- BUSCADOR -->
<div class="buscar-container">
    <form method="GET">
        <input type="text" name="buscar" placeholder="Buscar torneo..." value="<?= h($busqueda) ?>">
        <button type="submit">Buscar</button>
    </form>
</div>

<!-- TABLA TORNEOS -->
<table class="cuerpoTabla">
    <thead>
        <tr>
            <th>ID</th>
            <th>Torneo</th>
            <th>Categorías</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($torneos->num_rows === 0): ?>
        <tr><td colspan="4" style="text-align:center; padding:2rem; color:#6b7280;">No se encontraron torneos</td></tr>
    <?php else: ?>
        <?php while ($t = $torneos->fetch_assoc()): ?>
        <tr>
            <td><?= h($t['idTorneo']) ?></td>
            <td><?= h($t['nombreTorneo']) ?></td>
            <td><?= h($t['categorias']) ?></td>
            <td class="botonesfile">
                <button type="button" class="btn-editar" onclick="openEditarTorneo(<?= (int)$t['idTorneo'] ?>, '<?= h(addslashes($t['nombreTorneo'])) ?>')" title="Editar">
                    <i class="material-icons">edit</i>
                </button>
                <button type="button" class="btn-eliminar" onclick="confirmarEliminarTorneo(<?= (int)$t['idTorneo'] ?>, '<?= h(addslashes($t['nombreTorneo'])) ?>')" title="Eliminar">
                    <i class="material-icons">delete</i>
                </button>
                <button type="button" class="btn-categorias" onclick="openCategorias(<?= (int)$t['idTorneo'] ?>)" title="Gestionar Categorías">
                 Categorías
        </button>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
</table>

<!-- PAGINACIÓN -->
<?php if ($totalPaginas > 1): ?>
<div class="paginacion">
    <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina-1 ?>&buscar=<?= urlencode($busqueda) ?>" class="btn-nav">⬅ Anterior</a>
    <?php endif; ?>

    <?php
    $start = max(1, $pagina - 2);
    $end = min($totalPaginas, $pagina + 2);
    for ($i = $start; $i <= $end; $i++): ?>
        <a href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>" 
           class="<?= $i==$pagina ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($pagina < $totalPaginas): ?>
        <a href="?pagina=<?= $pagina+1 ?>&buscar=<?= urlencode($busqueda) ?>" class="btn-nav">Siguiente ➡</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- MODAL EDITAR TORNEO -->
<div id="modalEditarTorneo" class="modal">
    <div class="modal-content" style="width:500px;">
        <button class="close-btn" onclick="closeModal('modalEditarTorneo')">✕</button>
        <h3>Editar Nombre del Torneo</h3>
        <form method="POST" id="formEditarTorneo">
            <input type="hidden" name="idTorneo" id="edit_idTorneo">
            <input type="text" name="nombreTorneoEdit" id="edit_nombreTorneo" 
                   placeholder="Nombre del torneo" required maxlength="200" 
                   style="width:100%; padding:0.75rem; margin:1rem 0; border:1px solid #d1d5db; border-radius:6px;">
            <div style="display:flex; gap:10px;">
                <button type="submit" name="editarTorneo" class="btn-registrar">Guardar Cambios</button>
                <button type="button" class="btn-cancelar" onclick="closeModal('modalEditarTorneo')">Cancelar</button>
            </div>
        </form>
>>>>>>> f24cdbd064a26f9d9d389b4bb44064245cfce15f
    </div>
</div>

<!-- MODAL CATEGORÍAS -->
<div id="modalCategorias" class="modal">
    <div class="modal-content" style="max-width:1000px; width:90%;">
        <button class="close-btn" onclick="closeModal('modalCategorias')">✕</button>
        <h3>Gestión de Categorías</h3>
        
        <div id="categoriasList"></div>

        <hr style="margin:2rem 0; border:none; border-top:1px solid #e5e7eb;">
        
        <h4 style="margin-bottom:1rem;">Agregar Nueva Categoría</h4>
        <form id="formAgregarCategoria" onsubmit="agregarCategoria(event)">
            <input type="hidden" name="idTorneo" id="agregar_idTorneo">
            <div style="margin-bottom:1rem;">
                <input type="text" name="nombreCategoria" id="agregar_nombre" 
                       placeholder="Nombre de la categoría" required maxlength="100"
                       style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
            </div>
            <div class="pago-grid">
                <input type="number" name="pago1" placeholder="Pago Árbitro Principal" required min="0" step="0.01" style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
                <input type="number" name="pago2" placeholder="Pago Asistente 1" min="0" step="0.01" style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
                <input type="number" name="pago3" placeholder="Pago Asistente 2" min="0" step="0.01" style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
                <input type="number" name="pago4" placeholder="Pago Cuarto Árbitro" min="0" step="0.01" style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
            </div>
            <button type="submit" class="btn-registrar" style="margin-top:1rem;">Agregar Categoría</button>
        </form>
    </div>
</div>

<!-- MODAL EDITAR CATEGORÍA -->
<div id="modalEditarCategoria" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <button class="close-btn" onclick="closeModal('modalEditarCategoria')">✕</button>
        <h3>Editar Categoría</h3>
        <form id="formEditarCat" onsubmit="guardarEdicionCategoria(event)">
            <input type="hidden" name="idCategoria" id="editCat_id">
            <div style="margin-bottom:1rem;">
                <input type="text" name="nombreCategoriaEdit" id="editCat_nombre" 
                       placeholder="Nombre de la categoría" required maxlength="100"
                       style="width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;">
            </div>
            <div class="pago-grid">
                <input type="number" name="pago1Edit" id="editCat_p1" placeholder="Pago 1" required min="0" step="0.01">
                <input type="number" name="pago2Edit" id="editCat_p2" placeholder="Pago 2" min="0" step="0.01">
                <input type="number" name="pago3Edit" id="editCat_p3" placeholder="Pago 3" min="0" step="0.01">
                <input type="number" name="pago4Edit" id="editCat_p4" placeholder="Pago 4" min="0" step="0.01">
            </div>
            <div style="display:flex; gap:10px; margin-top:1rem;">
                <button type="submit" class="btn-registrar">Guardar Cambios</button>
                <button type="button" class="btn-cancelar" onclick="closeModal('modalEditarCategoria')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<style>
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    animation: slideIn 0.3s ease-out;
    font-weight: 500;
}
.toast-success { background: #10b981; color: white; }
.toast-error { background: #ef4444; color: white; }
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}
</style>

<script>
// ========== GESTIÓN DE MODALES ==========
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Cerrar modal al hacer clic fuera
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// ========== CATEGORÍAS DINÁMICAS EN FORMULARIO ==========
function addCategoria() {
    const container = document.getElementById("categoriasContainer");
    const html = `
    <div class="categoria-item">
        <input type="text" name="nombreCategoria[]" placeholder="Nombre de categoría" required maxlength="100">
        <div class="pago-grid">
            <input type="number" name="pago1[]" placeholder="Pago Árbitro Principal" required min="0" step="0.01">
            <input type="number" name="pago2[]" placeholder="Pago Asistente 1" min="0" step="0.01">
            <input type="number" name="pago3[]" placeholder="Pago Asistente 2" min="0" step="0.01">
            <input type="number" name="pago4[]" placeholder="Pago Cuarto Árbitro" min="0" step="0.01">
        </div>
        <button type="button" class="btn-remove" onclick="removeCategoria(this)">Eliminar</button>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function removeCategoria(btn) {
    const items = document.querySelectorAll('.categoria-item');
    if (items.length > 1) {
        btn.parentElement.remove();
    } else {
        showToast('Debe haber al menos una categoría', 'error');
    }
}

// ========== EDITAR TORNEO ==========
function openEditarTorneo(id, nombre) {
    document.getElementById('edit_idTorneo').value = id;
    document.getElementById('edit_nombreTorneo').value = nombre.replace(/\\'/g, "'");
    openModal('modalEditarTorneo');
}

// ========== ELIMINAR TORNEO ==========
function confirmarEliminarTorneo(id, nombre) {
    if (confirm(`¿Está seguro de eliminar el torneo "${nombre}"?\n\nEsta acción eliminará también todas sus categorías asociadas.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="idTorneo" value="${id}">
                          <input type="hidden" name="eliminarTorneo" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== GESTIÓN DE CATEGORÍAS ==========
function openCategorias(idTorneo) {
    document.getElementById('agregar_idTorneo').value = idTorneo;
    openModal('modalCategorias');
    cargarCategorias(idTorneo);
}

function cargarCategorias(idTorneo) {
    const container = document.getElementById('categoriasList');
    container.innerHTML = '<p style="text-align:center; padding:2rem;">Cargando categorías...</p>';
    
    fetch('torneo_ajax_cats.php?idTorneo=' + idTorneo)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<p class="error-msg">Error al cargar categorías</p>';
        });
}

function agregarCategoria(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('agregarCategoria', '1');
    
    fetch('torneo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            form.reset();
            const idTorneo = document.getElementById('agregar_idTorneo').value;
            cargarCategorias(idTorneo);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al agregar categoría', 'error');
    });
}

function editarCategoria(id, nombre, p1, p2, p3, p4) {
    document.getElementById('editCat_id').value = id;
    document.getElementById('editCat_nombre').value = nombre.replace(/\\'/g, "'");
    document.getElementById('editCat_p1').value = p1;
    document.getElementById('editCat_p2').value = p2;
    document.getElementById('editCat_p3').value = p3;
    document.getElementById('editCat_p4').value = p4;
    openModal('modalEditarCategoria');
}

function guardarEdicionCategoria(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('editarCategoria', '1');
    
    fetch('torneo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('modalEditarCategoria');
            const idTorneo = document.getElementById('agregar_idTorneo').value;
            cargarCategorias(idTorneo);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al actualizar categoría', 'error');
    });
}

function eliminarCategoria(id) {
    if (!confirm('¿Está seguro de eliminar esta categoría?')) return;
    
    const formData = new FormData();
    formData.append('idCategoria', id);
    formData.append('eliminarCategoria', '1');
    
    fetch('torneo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const idTorneo = document.getElementById('agregar_idTorneo').value;
            cargarCategorias(idTorneo);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error al eliminar categoría', 'error');
    });
}

// ========== SISTEMA DE NOTIFICACIONES ==========
function showToast(message, type = 'success') {
    const existingToast = document.querySelector('.toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========== VALIDACIÓN DE FORMULARIO PRINCIPAL ==========
document.getElementById('formTorneo').addEventListener('submit', function(e) {
    const categorias = document.querySelectorAll('.categoria-item');
    if (categorias.length === 0) {
        e.preventDefault();
        showToast('Debe agregar al menos una categoría', 'error');
        return false;
    }
    
    let hasValidCategoria = false;
    categorias.forEach(cat => {
        const nombre = cat.querySelector('input[name="nombreCategoria[]"]').value.trim();
        if (nombre) hasValidCategoria = true;
    });
    
    if (!hasValidCategoria) {
        e.preventDefault();
        showToast('Debe completar al menos una categoría', 'error');
        return false;
    }
});
</script>

</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>