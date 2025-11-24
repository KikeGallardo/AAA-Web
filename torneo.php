<?php require_once "assets/header.php"; ?>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestión de Torneos</title>
<link rel="stylesheet" href="assets/css/torneo.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="subtitulo">
        <h1>TORNEO</h1>

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