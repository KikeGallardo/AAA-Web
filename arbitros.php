<?php
session_start();
require_once 'config.php';

// Obtener conexión segura
$conexion = getDBConnection();

// ----------------------
// 1) REGISTRAR ÁRBITRO
// ----------------------
if (isset($_POST['registrar'])) {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula   = trim($_POST['cedula'] ?? '');
    $fechaNac = $_POST['fechaNacimiento'] ?? '';
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $categoria = $_POST['categoriaArbitro'] ?? '';

    if ($nombre === '' || $apellido === '') {
        $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'El nombre y apellido son obligatorios'];
    } else {
        $cedula    = $cedula   !== '' ? $cedula   : null;
        $fechaNac  = $fechaNac !== '' ? $fechaNac : null;
        $correo    = $correo   !== '' ? $correo   : null;
        $telefono  = $telefono !== '' ? $telefono : null;
        $categoria = $categoria !== '' ? $categoria : null;
        $stmt = $conexion->prepare(
            "INSERT INTO arbitro (nombre, apellido, cedula, fechaNacimiento, correo, telefono, categoriaArbitro)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss", $nombre, $apellido, $cedula, $fechaNac, $correo, $telefono, $categoria);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Árbitro registrado correctamente'];
        } else {
            error_log("Error al registrar árbitro: " . $stmt->error);
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al registrar árbitro'];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------
// 2) ELIMINAR ÁRBITRO
// ----------------------
if (isset($_POST['eliminar'])) {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id > 0) {
        $stmt = $conexion->prepare("DELETE FROM arbitro WHERE idArbitro = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Árbitro eliminado correctamente'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al eliminar árbitro'];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------
// 3) EDITAR ÁRBITRO
// ----------------------
if (isset($_POST['editar'])) {
    $id       = (int)($_POST['id'] ?? 0);
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula   = trim($_POST['cedula'] ?? '');
    $fechaNac = $_POST['fechaNacimiento'] ?? '';
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $categoria = $_POST['categoriaArbitro'] ?? '';

    $cedula    = $cedula   !== '' ? $cedula   : null;
    $fechaNac  = $fechaNac !== '' ? $fechaNac : null;
    $correo    = $correo   !== '' ? $correo   : null;
    $telefono  = $telefono !== '' ? $telefono : null;
    $categoria = $categoria !== '' ? $categoria : null;

    if ($id > 0 && $nombre !== '' && $apellido !== '') {
        $stmt = $conexion->prepare(
            "UPDATE arbitro 
             SET nombre=?, apellido=?, cedula=?, fechaNacimiento=?, correo=?, telefono=?, categoriaArbitro=?
             WHERE idArbitro=?"
        );
        $stmt->bind_param("sssssssi", $nombre, $apellido, $cedula, $fechaNac, $correo, $telefono, $categoria, $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Datos actualizados correctamente'];
        } else {
            $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al actualizar'];
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ------------------------------------
// 4) PAGINACIÓN + BÚSQUEDA
// ------------------------------------
$registrosPorPagina = 10;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $registrosPorPagina;

$busqueda = "";
$where = "";
$bindTypes = "";
$bindValues = [];

if (isset($_GET['buscar']) && trim($_GET['buscar']) !== '') {
    $busqueda = trim($_GET['buscar']);
    $searchTerm = "%{$busqueda}%";
    $where = "WHERE nombre LIKE ? OR apellido LIKE ? OR cedula LIKE ? OR correo LIKE ? OR telefono LIKE ? OR categoriaArbitro LIKE ?";
    $bindTypes = "ssssss";
    $bindValues = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Total de registros
$totalQuery = $conexion->prepare("SELECT COUNT(*) AS total FROM arbitro $where");
if ($where) {
    $totalQuery->bind_param($bindTypes, ...$bindValues);
}
$totalQuery->execute();
$totalRegistros = (int)$totalQuery->get_result()->fetch_assoc()['total'];
$totalPaginas = max(1, ceil($totalRegistros / $registrosPorPagina));
$totalQuery->close();

// Listado de árbitros
$sql = "SELECT * FROM arbitro $where ORDER BY nombre ASC, apellido ASC LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql);

if ($where) {
    $bindValues[] = $registrosPorPagina;
    $bindValues[] = $offset;
    $stmt->bind_param($bindTypes . "ii", ...$bindValues);
} else {
    $stmt->bind_param("ii", $registrosPorPagina, $offset);
}

$stmt->execute();
$arbitros = $stmt->get_result();

// ------------------------------------
// 5) CARGAR CATEGORÍAS DESDE BD
// ------------------------------------
$resCats = $conexion->query("SELECT nombre FROM categoriaArbitro ORDER BY nombre ASC");
$categoriasDB = [];
while ($cat = $resCats->fetch_assoc()) {
    $categoriasDB[] = $cat['nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Árbitros</title>
    <link rel="stylesheet" href="assets/css/arbitros.css">
    <link rel="stylesheet" href="assets/css/subtitulos.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

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
require_once "assets/header.php";
require_once "assets/footer.php";
?>

<div class="subtitulo"><h1>GESTIÓN DE ÁRBITROS</h1></div>

<!-- FORMULARIO REGISTRO -->
<div class="form-container">
    <h2>Registrar Nuevo Árbitro</h2>
    <form method="POST" class="form-arbitro" id="formRegistro">
        <input type="text" name="nombre" placeholder="Nombre" required maxlength="100">
        <input type="text" name="apellido" placeholder="Apellido" required maxlength="100">
        <input type="text" name="cedula" placeholder="Cédula (opcional)" maxlength="20">
        <input type="date" name="fechaNacimiento">
        <input type="email" name="correo" placeholder="Correo electrónico (opcional)" maxlength="100">
        <input type="text" name="telefono" placeholder="Teléfono (opcional)" maxlength="15">
        <select name="categoriaArbitro">
            <option value="">Seleccione categoría</option>
            <?php foreach ($categoriasDB as $cat): ?>
            <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="registrar" class="btn-registrar">Registrar Árbitro</button>
    </form>
</div>

<!-- BUSCADOR -->
<div class="buscar-container">
    <form method="GET">
        <input type="text" id="buscador" name="buscar" placeholder="Buscar árbitro..." value="<?= h($busqueda) ?>">
        <button type="submit">Buscar</button>
    </form>
</div>

<!-- TABLA ÁRBITROS -->
<table class="cuerpoTabla">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Cédula</th>
            <th>Fecha Nac.</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Categoría</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($arbitros->num_rows === 0): ?>
        <tr><td colspan="8" style="text-align:center; padding:2rem; color:#6b7280;">No se encontraron árbitros</td></tr>
    <?php else: ?>
        <?php while ($row = $arbitros->fetch_assoc()): ?>
        <tr>
            <td><?= h($row['nombre']) ?></td>
            <td><?= h($row['apellido']) ?></td>
            <td><?= h($row['cedula']) ?></td>
            <td><?= h($row['fechaNacimiento']) ?></td>
            <td><?= h($row['correo']) ?></td>
            <td><?= h($row['telefono']) ?></td>
            <td><?= h($row['categoriaArbitro']) ?></td>
            <td class="botonesfile">
                <button type="button" class="btn-editar" 
                        onclick="editarArbitro(<?= $row['idArbitro'] ?>, '<?= h(addslashes($row['nombre'])) ?>', '<?= h(addslashes($row['apellido'])) ?>', '<?= h(addslashes($row['cedula'])) ?>', '<?= h($row['fechaNacimiento']) ?>', '<?= h(addslashes($row['correo'])) ?>', '<?= h(addslashes($row['telefono'])) ?>', '<?= h(addslashes($row['categoriaArbitro'])) ?>')"
                        title="Editar">
                    <i class="material-icons">edit</i>
                </button>
                <button type="button" class="btn-eliminar" 
                        onclick="confirmarEliminar(<?= $row['idArbitro'] ?>, '<?= h(addslashes($row['nombre'])) ?> <?= h(addslashes($row['apellido'])) ?>')"
                        title="Eliminar">
                    <i class="material-icons">delete</i>
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

<!-- MODAL EDITAR -->
<div id="modalEditar" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <button class="close-btn" onclick="closeModal('modalEditar')">✕</button>
        <h3>Editar Árbitro</h3>
        <form method="POST" id="formEditar">
            <input type="hidden" name="id" id="edit_id">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <input type="text" id="edit_nombre" name="nombre" placeholder="Nombre" required>
                <input type="text" id="edit_apellido" name="apellido" placeholder="Apellido" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <input type="text" id="edit_cedula" name="cedula" placeholder="Cédula (opcional)">
                <input type="date" id="edit_fechaNacimiento" name="fechaNacimiento">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <input type="email" id="edit_correo" name="correo" placeholder="Correo (opcional)">
                <input type="text" id="edit_telefono" name="telefono" placeholder="Teléfono (opcional)">
            </div>
            <select id="edit_categoria" name="categoriaArbitro" style="width:100%; margin-bottom:1rem;">
                <option value="">Seleccione categoría</option>
                <?php foreach ($categoriasDB as $cat): ?>
                <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="editar" class="btn-registrar">Guardar Cambios</button>
                <button type="button" class="btn-cancelar" onclick="closeModal('modalEditar')">Cancelar</button>
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

// Cerrar con clic fuera
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// ========== EDITAR ÁRBITRO ==========
function editarArbitro(id, nombre, apellido, cedula, fecha, correo, telefono, categoria) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre.replace(/\\'/g, "'");
    document.getElementById('edit_apellido').value = apellido.replace(/\\'/g, "'");
    document.getElementById('edit_cedula').value = cedula.replace(/\\'/g, "'");
    document.getElementById('edit_fechaNacimiento').value = fecha;
    document.getElementById('edit_correo').value = correo.replace(/\\'/g, "'");
    document.getElementById('edit_telefono').value = telefono.replace(/\\'/g, "'");
    document.getElementById('edit_categoria').value = categoria.replace(/\\'/g, "'");
    openModal('modalEditar');
}

// ========== ELIMINAR ÁRBITRO ==========
function confirmarEliminar(id, nombre) {
    if (confirm(`¿Está seguro de eliminar al árbitro "${nombre}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="id" value="${id}">
                          <input type="hidden" name="eliminar" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========== VALIDACIÓN FORMULARIO ==========
document.getElementById('formRegistro').addEventListener('submit', function(e) {
    const nombre = this.nombre.value.trim();
    const apellido = this.apellido.value.trim();
    const cedula = this.cedula.value.trim();
    const telefono = this.telefono.value.trim();

    if (nombre.length < 2) {
        e.preventDefault();
        showToast('El nombre debe tener mínimo 2 caracteres', 'error');
        return;
    }

    if (apellido.length < 2) {
        e.preventDefault();
        showToast('El apellido debe tener mínimo 2 caracteres', 'error');
        return;
    }

    if (cedula !== '' && !/^[0-9A-Za-z\-]+$/.test(cedula)) {
        e.preventDefault();
        showToast('La cédula contiene caracteres inválidos', 'error');
        return;
    }

    if (telefono !== '' && !/^[0-9]{6,15}$/.test(telefono)) {
        e.preventDefault();
        showToast('El teléfono debe contener solo números (6-15 dígitos)', 'error');
        return;
    }
});

// ========== NOTIFICACIONES ==========
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
</script>


<!-- ========== BOTÓN FLOTANTE CATEGORÍAS ========== -->
<button class="btn-flotante-cats" onclick="openModal('modalCategorias')" title="Gestionar categorías de árbitros">
    <i class="material-icons">label</i>
</button>

<!-- ========== MODAL GESTIÓN DE CATEGORÍAS ========== -->
<div id="modalCategorias" class="modal">
    <div class="modal-content" style="max-width:480px;">
        <button class="close-btn" onclick="closeModal('modalCategorias')">✕</button>
        <h3>Categorías de Árbitros</h3>

        <!-- Lista de categorías actuales -->
        <div id="listaCategorias" style="margin-bottom:1.5rem; max-height:320px; overflow-y:auto;">
            <?php foreach ($categoriasDB as $cat): ?>
            <div class="cat-item" id="cat-<?= h($cat) ?>">
                <span class="cat-nombre"><?= h($cat) ?></span>
                <button type="button" class="btn-eliminar-cat"
                        onclick="eliminarCategoria('<?= h(addslashes($cat)) ?>')"
                        title="Eliminar">
                    <i class="material-icons">delete</i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulario agregar -->
        <div style="display:flex; gap:0.5rem;">
            <input type="text" id="nuevaCategoria" placeholder="Nueva categoría..." maxlength="100"
                   style="flex:1; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px; font-size:1rem; text-transform:uppercase;">
            <button type="button" class="btn-registrar" onclick="agregarCategoria()">
                <i class="material-icons" style="vertical-align:middle;">add</i> Agregar
            </button>
        </div>
        <p id="msgCategoria" style="margin-top:0.75rem; font-size:0.85rem; min-height:1.2rem;"></p>
    </div>
</div>

<style>
/* ── Botón flotante ── */
.btn-flotante-cats {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #2563eb;
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(37,99,235,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 900;
    transition: background 0.2s, transform 0.2s;
}
.btn-flotante-cats:hover {
    background: #1e40af;
    transform: scale(1.1);
}
.btn-flotante-cats .material-icons { font-size: 1.6rem; }

/* ── Lista de categorías en modal ── */
.cat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 0.75rem;
    border-radius: 6px;
    margin-bottom: 0.4rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    transition: background 0.15s;
}
.cat-item:hover { background: #f3f4f6; }
.cat-nombre { font-weight: 500; font-size: 0.95rem; }
.btn-eliminar-cat {
    background: none;
    border: none;
    cursor: pointer;
    color: #ef4444;
    display: flex;
    align-items: center;
    padding: 0.2rem;
    border-radius: 4px;
    transition: background 0.15s;
}
.btn-eliminar-cat:hover { background: #fee2e2; }
.btn-eliminar-cat .material-icons { font-size: 1.1rem; }
</style>

<script>
// ========== GESTIÓN DE CATEGORÍAS (AJAX) ==========

function mostrarMsgCat(texto, tipo) {
    const el = document.getElementById('msgCategoria');
    el.textContent = texto;
    el.style.color = tipo === 'error' ? '#ef4444' : '#10b981';
    setTimeout(() => { el.textContent = ''; }, 3000);
}

function recargarSelects(categorias) {
    const opcionesHTML = '<option value="">Seleccione categoría</option>'
        + categorias.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
    document.querySelector('select[name="categoriaArbitro"]').innerHTML = opcionesHTML;
    document.getElementById('edit_categoria').innerHTML = opcionesHTML;
}

async function agregarCategoria() {
    const input = document.getElementById('nuevaCategoria');
    const nombre = input.value.trim().toUpperCase();
    if (!nombre) { mostrarMsgCat('Escribe un nombre para la categoría.', 'error'); return; }

    const fd = new FormData();
    fd.append('accion', 'agregar_categoria_arbitro');
    fd.append('nombre', nombre);

    const res  = await fetch('consultas.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'ok') {
        // Agregar al DOM sin recargar
        const lista = document.getElementById('listaCategorias');
        const div = document.createElement('div');
        div.className = 'cat-item';
        div.id = 'cat-' + nombre;
        div.innerHTML = `
            <span class="cat-nombre">${escapeHtml(nombre)}</span>
            <button type="button" class="btn-eliminar-cat"
                    onclick="eliminarCategoria('${nombre.replace(/'/g, "\'")}')"
                    title="Eliminar">
                <i class="material-icons">delete</i>
            </button>`;
        lista.appendChild(div);
        recargarSelects(data.categorias);
        input.value = '';
        mostrarMsgCat('Categoría agregada correctamente.', 'ok');
    } else {
        mostrarMsgCat(data.msg || 'Error al agregar.', 'error');
    }
}

async function eliminarCategoria(nombre) {
    if (!confirm(`¿Eliminar la categoría "${nombre}"?`)) return;

    const fd = new FormData();
    fd.append('accion', 'eliminar_categoria_arbitro');
    fd.append('nombre', nombre);

    const res  = await fetch('consultas.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'ok') {
        const el = document.getElementById('cat-' + nombre);
        if (el) el.remove();
        recargarSelects(data.categorias);
        mostrarMsgCat('Categoría eliminada.', 'ok');
    } else {
        mostrarMsgCat(data.msg || 'Error al eliminar.', 'error');
    }
}

// forzar mayúsculas en el input
document.addEventListener('DOMContentLoaded', function() {
    const inp = document.getElementById('nuevaCategoria');
    if (inp) inp.addEventListener('input', () => { inp.value = inp.value.toUpperCase(); });
});
</script>

<script src="assets/js/arbitros.js"></script>
</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>