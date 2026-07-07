<?php
session_start();
require_once 'config.php';

if (!isset($_GET['idTorneo']) || !is_numeric($_GET['idTorneo'])) {
    echo '<p class="error-msg">ID de torneo inválido</p>';
    exit;
}

$idTorneo = (int)$_GET['idTorneo'];
$conexion = getDBConnection();

$stmtTorneo = $conexion->prepare("SELECT nombreTorneo FROM torneo WHERE idTorneo = ?");
$stmtTorneo->bind_param("i", $idTorneo);
$stmtTorneo->execute();
$resultTorneo = $stmtTorneo->get_result();

if ($resultTorneo->num_rows === 0) {
    echo '<p class="error-msg">Torneo no encontrado</p>';
    $stmtTorneo->close();
    $conexion->close();
    exit;
}

$torneo = $resultTorneo->fetch_assoc();
$stmtTorneo->close();

$stmt = $conexion->prepare("
    SELECT idCategoriaPagoArbitro, nombreCategoria, tipopago,
           pagoArbitro1, pagoArbitro2, pagoArbitro3, pagoArbitro4
    FROM categoriaPagoArbitro
    WHERE idTorneo = ?
    ORDER BY nombreCategoria
");
$stmt->bind_param("i", $idTorneo);
$stmt->execute();
$result = $stmt->get_result();
$categorias = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conexion->close();
?>

<div class="torneo-header">
    <h4><?= h($torneo['nombreTorneo']) ?></h4>
</div>

<?php if (empty($categorias)): ?>
    <p class="no-data">No hay categorías registradas para este torneo.</p>
<?php else: ?>
<style>
.cats-edit-table { width:100%; border-collapse:collapse; font-size:.88rem; margin-bottom:1rem; }
.cats-edit-table th { background:#f3f4f6; padding:.6rem .7rem; text-align:left; font-size:.75rem; font-weight:600; color:#6b7280; text-transform:uppercase; border-bottom:2px solid #e5e7eb; }
.cats-edit-table td { padding:.45rem .5rem; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.cats-edit-table tr:hover td { background:#fafafa; }
.cats-edit-table input[type=text],
.cats-edit-table input[type=number] {
    width:100%; padding:.4rem .5rem; border:1.5px solid #e5e7eb; border-radius:6px;
    font-size:.88rem; font-family:inherit; box-sizing:border-box;
    transition:border-color .2s;
}
.cats-edit-table input:focus { outline:none; border-color:#1a56db; }
.cats-edit-table select { width:100%; padding:.4rem .5rem; border:1.5px solid #e5e7eb; border-radius:6px; font-size:.88rem; font-family:inherit; }
.cats-edit-table select:focus { outline:none; border-color:#1a56db; }
.btn-guardar-masivo {
    width:100%; padding:.8rem; background:#057a55; color:#fff; border:none;
    border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;
    font-family:inherit; margin-top:.5rem;
    transition:background .2s;
}
.btn-guardar-masivo:hover { background:#046647; }
</style>

<table class="cats-edit-table" id="tablaCategorias">
    <thead>
        <tr>
            <th style="min-width:160px">Categoría</th>
            <th>Central</th>
            <th>Asist. 1</th>
            <th>Asist. 2</th>
            <th>4° Árb.</th>
            <th>Tipo pago</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categorias as $cat): ?>
        <tr data-id="<?= $cat['idCategoriaPagoArbitro'] ?>">
            <td><input type="text" class="cat-nombre" value="<?= h($cat['nombreCategoria']) ?>"></td>
            <td><input type="number" class="cat-p1" value="<?= (int)$cat['pagoArbitro1'] ?>" min="0"></td>
            <td><input type="number" class="cat-p2" value="<?= (int)$cat['pagoArbitro2'] ?>" min="0"></td>
            <td><input type="number" class="cat-p3" value="<?= (int)$cat['pagoArbitro3'] ?>" min="0"></td>
            <td><input type="number" class="cat-p4" value="<?= (int)$cat['pagoArbitro4'] ?>" min="0"></td>
            <td>
                <select class="cat-tipo">
                    <option value="INMEDIATO" <?= $cat['tipopago'] === 'INMEDIATO' ? 'selected' : '' ?>>Inmediato</option>
                    <option value="QUINCENAL" <?= $cat['tipopago'] === 'QUINCENAL' ? 'selected' : '' ?>>Quincenal</option>
                </select>
            </td>
            <td>
                <button type="button" class="btn-icon btn-delete"
                        onclick="eliminarCategoria(<?= $cat['idCategoriaPagoArbitro'] ?>)"
                        title="Eliminar">
                    <i class="material-icons">delete</i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button type="button" class="btn-guardar-masivo" onclick="guardarTodasCategorias(<?= $idTorneo ?>)">
    Guardar cambios
</button>
<?php endif; ?>
