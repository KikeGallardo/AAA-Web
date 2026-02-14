<?php
session_start();
require_once 'config.php';

$conexion = getDBConnection();

// Obtener lista de torneos para el filtro
$torneos = $conexion->query("SELECT idTorneo, nombreTorneo FROM torneo ORDER BY nombreTorneo");

// Obtener lista de árbitros para el filtro
$arbitros = $conexion->query("SELECT idArbitro, nombre, apellido FROM arbitro ORDER BY nombre, apellido");

require_once "assets/header.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Cuentas de Cobro</title>
    <link rel="stylesheet" href="assets/css/impresion.css">
    <link rel="stylesheet" href="assets/css/subtitulos.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="subtitulo">
    <h1>IMPRESIÓN DE CUENTAS DE COBRO</h1>
</div>

<!-- FORMULARIO DE FILTROS Y GENERACIÓN -->
<div class="form-generar-container">
    <h3>Seleccionar Criterios</h3>
    <form method="POST" action="generar_cuentas_cobro_html.php" target="_blank" class="form-generar">
        
        <!-- Rango de fechas (OBLIGATORIO) -->
        <div class="filtro-grupo obligatorio">
            <label>Fecha Inicio: <span class="requerido">*</span></label>
            <input type="date" name="fechaInicio" required>
        </div>
        
        <div class="filtro-grupo obligatorio">
            <label>Fecha Fin: <span class="requerido">*</span></label>
            <input type="date" name="fechaFin" required>
        </div>
        
        <!-- Árbitro (OBLIGATORIO) -->
        <div class="filtro-grupo obligatorio">
            <label>Árbitro: <span class="requerido">*</span></label>
            <select name="arbitro" required>
                <option value="">Seleccionar árbitro</option>
                <?php 
                $arbitros->data_seek(0); // Resetear puntero
                while ($a = $arbitros->fetch_assoc()): 
                ?>
                    <option value="<?= $a['idArbitro'] ?>">
                        <?= h($a['nombre'] . ' ' . $a['apellido']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Torneo (OPCIONAL) -->
        <div class="filtro-grupo">
            <label>Torneo (opcional):</label>
            <select name="torneo">
                <option value="">Todos los torneos</option>
                <?php 
                $torneos->data_seek(0); // Resetear puntero
                while ($t = $torneos->fetch_assoc()): 
                ?>
                    <option value="<?= $t['idTorneo'] ?>">
                        <?= h($t['nombreTorneo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Botón generar -->
        <div class="btn-container">
            <button type="submit" class="btn-generar">
                <i class="material-icons">print</i> 
                Generar Cuentas de Cobro
            </button>
        </div>
    </form>
    
    <div class="info-mensaje">
        <i class="material-icons">info</i>
        <div>
            <strong>Cómo funciona:</strong>
            <ul>
                <li>Selecciona un rango de fechas</li>
                <li>Selecciona el árbitro</li>
                <li>Opcionalmente filtra por torneo</li>
                <li>Se generarán las cuentas de cobro de todos los partidos donde ese árbitro participó (en cualquier rol)</li>
                <li>Las cuentas estarán optimizadas para imprimir 4 por página</li>
            </ul>
        </div>
    </div>
</div>

<!-- VISTA PREVIA (opcional) -->
<div class="preview-container" id="previewContainer" style="display:none;">
    <h3>Vista Previa</h3>
    <div id="previewContent"></div>
</div>

<script>
// Validación adicional del formulario
document.querySelector('.form-generar').addEventListener('submit', function(e) {
    const fechaInicio = new Date(this.fechaInicio.value);
    const fechaFin = new Date(this.fechaFin.value);
    
    if (fechaFin < fechaInicio) {
        e.preventDefault();
        alert('La fecha fin no puede ser anterior a la fecha inicio');
        return false;
    }
    
    const arbitro = this.arbitro.value;
    if (!arbitro) {
        e.preventDefault();
        alert('Debes seleccionar un árbitro');
        return false;
    }
});

// Mostrar info de fechas seleccionadas
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.addEventListener('change', function() {
        const inicio = document.querySelector('input[name="fechaInicio"]').value;
        const fin = document.querySelector('input[name="fechaFin"]').value;
        
        if (inicio && fin) {
            const dias = Math.ceil((new Date(fin) - new Date(inicio)) / (1000 * 60 * 60 * 24)) + 1;
            console.log(`Rango seleccionado: ${dias} días`);
        }
    });
});
</script>

</body>
</html>
<?php
$conexion->close();
require_once "assets/footer.php";
?>