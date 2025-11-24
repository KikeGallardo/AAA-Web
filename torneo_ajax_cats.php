<?php
// torneo_ajax_cats.php - Endpoint para cargar categorías de un torneo
session_start();
require_once 'config.php';

// Validar que sea una petición válida
if (!isset($_GET['idTorneo']) || !is_numeric($_GET['idTorneo'])) {
    echo '<p class="error-msg">ID de torneo inválido</p>';
    exit;
}

$idTorneo = (int)$_GET['idTorneo'];
$conexion = getDBConnection();

// Obtener nombre del torneo
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

echo '<div class="torneo-header">';
echo '<h4>' . h($torneo['nombreTorneo']) . '</h4>';
echo '</div>';

// Obtener categorías del torneo
$stmt = $conexion->prepare("
    SELECT c.idCategoriaPagoArbitro, c.nombreCategoria, 
           c.pagoArbitro1, c.pagoArbitro2, c.pagoArbitro3, c.pagoArbitro4
    FROM categoriaPagoArbitro c
    INNER JOIN torneo_categoria tc ON tc.idCategoriaPagoArbitro = c.idCategoriaPagoArbitro
    WHERE tc.idTorneo = ?
    ORDER BY c.nombreCategoria
");
$stmt->bind_param("i", $idTorneo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="no-data">No hay categorías registradas para este torneo.</p>';
} else {
    echo '<div class="categorias-grid">';
    while ($cat = $result->fetch_assoc()) {
        ?>
        <div class="categoria-card" data-id="<?= $cat['idCategoriaPagoArbitro'] ?>">
            <div class="categoria-header">
                <h5><?= h($cat['nombreCategoria']) ?></h5>
                <div class="categoria-actions">
                    <button type="button" 
                            class="btn-icon btn-edit" 
                            onclick="editarCategoria(<?= $cat['idCategoriaPagoArbitro'] ?>, '<?= h(addslashes($cat['nombreCategoria'])) ?>', <?= $cat['pagoArbitro1'] ?>, <?= $cat['pagoArbitro2'] ?>, <?= $cat['pagoArbitro3'] ?>, <?= $cat['pagoArbitro4'] ?>)"
                            title="Editar">
                        <i class="material-icons">edit</i>
                    </button>
                    <button type="button" 
                            class="btn-icon btn-delete" 
                            onclick="eliminarCategoria(<?= $cat['idCategoriaPagoArbitro'] ?>)"
                            title="Eliminar">
                        <i class="material-icons">delete</i>
                    </button>
                </div>
            </div>
            <div class="categoria-pagos">
                <div class="pago-item">
                    <span class="pago-label">Árbitro Principal:</span>
                    <span class="pago-valor">$<?= number_format($cat['pagoArbitro1'], 2) ?></span>
                </div>
                <div class="pago-item">
                    <span class="pago-label">Asistente 1:</span>
                    <span class="pago-valor">$<?= number_format($cat['pagoArbitro2'], 2) ?></span>
                </div>
                <div class="pago-item">
                    <span class="pago-label">Asistente 2:</span>
                    <span class="pago-valor">$<?= number_format($cat['pagoArbitro3'], 2) ?></span>
                </div>
                <div class="pago-item">
                    <span class="pago-label">Cuarto Árbitro:</span>
                    <span class="pago-valor">$<?= number_format($cat['pagoArbitro4'], 2) ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

$stmt->close();
$conexion->close();
?>