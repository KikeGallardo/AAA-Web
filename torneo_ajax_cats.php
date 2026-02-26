<?php
// torneo_ajax_cats.php - Endpoint para cargar categorías de un torneo
session_start();
require_once 'config.php';

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

// Obtener categorías directamente por idTorneo (sin tabla pivot)
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

$tipoPagoLabel = ['inmediato' => 'Pago inmediato', 'quincenal' => 'Pago quincenal'];

if ($result->num_rows === 0) {
    echo '<p class="no-data">No hay categorías registradas para este torneo.</p>';
} else {
    echo '<div class="categorias-grid">';
    while ($cat = $result->fetch_assoc()) {
        $tp = $tipoPagoLabel[$cat['tipopago']] ?? ucfirst($cat['tipopago']);
        ?>
        <div class="categoria-card" data-id="<?= $cat['idCategoriaPagoArbitro'] ?>">
            <div class="categoria-header">
                <h5><?= h($cat['nombreCategoria']) ?></h5>
                <div class="categoria-actions">
                    <button type="button" 
                            class="btn-icon btn-edit" 
                            onclick="editarCategoria(
                                <?= $cat['idCategoriaPagoArbitro'] ?>, 
                                '<?= h(addslashes($cat['nombreCategoria'])) ?>', 
                                <?= (float)$cat['pagoArbitro1'] ?>, 
                                <?= (float)$cat['pagoArbitro2'] ?>, 
                                <?= (float)$cat['pagoArbitro3'] ?>, 
                                <?= (float)$cat['pagoArbitro4'] ?>,
                                '<?= h($cat['tipopago']) ?>'
                            )"
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
                <div class="pago-item pago-tipo">
                    <span class="pago-label">Tipo de pago:</span>
                    <span class="pago-badge pago-badge-<?= h($cat['tipopago']) ?>"><?= h($tp) ?></span>
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