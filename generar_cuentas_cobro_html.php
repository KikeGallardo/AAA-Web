<?php
session_start();
require_once 'config.php';

// Validar que se recibieron los filtros
if (!isset($_POST['fechaInicio']) || !isset($_POST['fechaFin']) || !isset($_POST['arbitro'])) {
    die('Error: Faltan parámetros requeridos (fechaInicio, fechaFin, arbitro)');
}

$fechaInicio = $_POST['fechaInicio'];
$fechaFin = $_POST['fechaFin'];
$idArbitro = (int)$_POST['arbitro'];
$idTorneo = isset($_POST['torneo']) && $_POST['torneo'] !== '' ? (int)$_POST['torneo'] : null;

$conexion = getDBConnection();

// Construir query
$sql = "
    SELECT 
        p.idPartido,
        p.fecha,
        p.hora,
        p.canchaLugar,
        p.categoriaText,
        
        e1.nombreEquipo AS equipoLocal,
        e2.nombreEquipo AS equipoVisitante,
        
        t.nombreTorneo,
        
        cp.nombreCategoria,
        cp.pagoArbitro1,
        cp.pagoArbitro2,
        cp.pagoArbitro3,
        cp.pagoArbitro4,
        
        a1.idArbitro AS idArbitro1,
        a1.nombre AS nombreArbitro1,
        a1.apellido AS apellidoArbitro1,
        a1.cedula AS cedulaArbitro1,
        
        a2.idArbitro AS idArbitro2,
        a2.nombre AS nombreArbitro2,
        a2.apellido AS apellidoArbitro2,
        
        a3.idArbitro AS idArbitro3,
        a3.nombre AS nombreArbitro3,
        a3.apellido AS apellidoArbitro3,
        
        a4.idArbitro AS idArbitro4,
        a4.nombre AS nombreArbitro4,
        a4.apellido AS apellidoArbitro4
        
    FROM partido p
    INNER JOIN equipo e1 ON p.idEquipo1 = e1.idEquipo
    INNER JOIN equipo e2 ON p.idEquipo2 = e2.idEquipo
    INNER JOIN torneo t ON p.idTorneoPartido = t.idTorneo
    INNER JOIN categoriaPagoArbitro cp ON p.idCategoriaPagoArbitro = cp.idCategoriaPagoArbitro
    LEFT JOIN arbitro a1 ON p.idArbitro1 = a1.idArbitro
    LEFT JOIN arbitro a2 ON p.idArbitro2 = a2.idArbitro
    LEFT JOIN arbitro a3 ON p.idArbitro3 = a3.idArbitro
    LEFT JOIN arbitro a4 ON p.idArbitro4 = a4.idArbitro
    
    WHERE p.fecha BETWEEN ? AND ?
      AND (p.idArbitro1 = ? OR p.idArbitro2 = ? OR p.idArbitro3 = ? OR p.idArbitro4 = ?)
";

$params = [$fechaInicio, $fechaFin, $idArbitro, $idArbitro, $idArbitro, $idArbitro];
$types = 'ssiiii';

if ($idTorneo) {
    $sql .= " AND t.idTorneo = ?";
    $params[] = $idTorneo;
    $types .= 'i';
}

$sql .= " ORDER BY p.fecha ASC, p.hora ASC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$partidos = $stmt->get_result();

if ($partidos->num_rows === 0) {
    die('<div style="text-align:center; padding:50px; font-family:Arial;">
            <h2>No se encontraron partidos</h2>
            <p>No hay partidos para este árbitro en el rango de fechas seleccionado.</p>
            <button onclick="window.close()" style="padding:10px 20px; background:#2563eb; color:white; border:none; border-radius:6px; cursor:pointer;">Cerrar</button>
         </div>');
}

// Obtener info del árbitro
$stmtArbitro = $conexion->prepare("SELECT nombre, apellido, cedula FROM arbitro WHERE idArbitro = ?");
$stmtArbitro->bind_param('i', $idArbitro);
$stmtArbitro->execute();
$arbitroInfo = $stmtArbitro->get_result()->fetch_assoc();

// Función para formatear fecha
function formatearFecha($fecha) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    $timestamp = strtotime($fecha);
    $dia = $dias[date('w', $timestamp)];
    $numero = date('j', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)];
    $ano = date('Y', $timestamp);
    
    return "$dia, $numero De $mes De $ano";
}

// Preparar datos de partidos
$partidos_data = [];
$totalPagar = 0;

while ($partido = $partidos->fetch_assoc()) {
    $rol = '';
    $tarifa = 0;
    $asistente1 = '';
    $asistente2 = '';
    $emergente = '';
    
    if ($partido['idArbitro1'] == $idArbitro) {
        $rol = 'ARBITRO';
        $tarifa = $partido['pagoArbitro1'];
        $asistente1 = $partido['nombreArbitro2'] ? trim($partido['nombreArbitro2'] . ' ' . $partido['apellidoArbitro2']) : '';
        $asistente2 = $partido['nombreArbitro3'] ? trim($partido['nombreArbitro3'] . ' ' . $partido['apellidoArbitro3']) : '';
        $emergente = $partido['nombreArbitro4'] ? trim($partido['nombreArbitro4'] . ' ' . $partido['apellidoArbitro4']) : '';
    } elseif ($partido['idArbitro2'] == $idArbitro) {
        $rol = 'ASISTENTE 1';
        $tarifa = $partido['pagoArbitro2'];
        $asistente1 = $partido['nombreArbitro1'] ? trim($partido['nombreArbitro1'] . ' ' . $partido['apellidoArbitro1']) : '';
        $asistente2 = $partido['nombreArbitro3'] ? trim($partido['nombreArbitro3'] . ' ' . $partido['apellidoArbitro3']) : '';
        $emergente = $partido['nombreArbitro4'] ? trim($partido['nombreArbitro4'] . ' ' . $partido['apellidoArbitro4']) : '';
    } elseif ($partido['idArbitro3'] == $idArbitro) {
        $rol = 'ASISTENTE 2';
        $tarifa = $partido['pagoArbitro3'];
        $asistente1 = $partido['nombreArbitro1'] ? trim($partido['nombreArbitro1'] . ' ' . $partido['apellidoArbitro1']) : '';
        $asistente2 = $partido['nombreArbitro2'] ? trim($partido['nombreArbitro2'] . ' ' . $partido['apellidoArbitro2']) : '';
        $emergente = $partido['nombreArbitro4'] ? trim($partido['nombreArbitro4'] . ' ' . $partido['apellidoArbitro4']) : '';
    } elseif ($partido['idArbitro4'] == $idArbitro) {
        $rol = 'EMERGENTE';
        $tarifa = $partido['pagoArbitro4'];
        $asistente1 = $partido['nombreArbitro1'] ? trim($partido['nombreArbitro1'] . ' ' . $partido['apellidoArbitro1']) : '';
        $asistente2 = $partido['nombreArbitro2'] ? trim($partido['nombreArbitro2'] . ' ' . $partido['apellidoArbitro2']) : '';
        $emergente = $partido['nombreArbitro3'] ? trim($partido['nombreArbitro3'] . ' ' . $partido['apellidoArbitro3']) : '';
    }
    
    $totalPagar += $tarifa;
    
    $partidos_data[] = [
        'fecha' => formatearFecha($partido['fecha']),
        'hora' => $partido['hora'],
        'equipos' => $partido['equipoLocal'] . ' vs ' . $partido['equipoVisitante'],
        'lugar' => $partido['canchaLugar'],
        'torneo' => $partido['nombreTorneo'],
        'categoria' => $partido['categoriaText'] ?? $partido['nombreCategoria'],
        'rol' => $rol,
        'tarifa' => $tarifa,
        'asistente1' => $asistente1,
        'asistente2' => $asistente2,
        'emergente' => $emergente
    ];
}

$totalPartidos = count($partidos_data);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta de Cobro - <?= h($arbitroInfo['nombre'] . ' ' . $arbitroInfo['apellido']) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            @page { margin: 15mm; size: letter portrait; }
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .toolbar {
            background: white;
            padding: 12px 20px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .btn-print { background: #10b981; color: white; }
        .btn-back { background: #6b7280; color: white; }
        
        .resumen {
            background: #f0f9ff;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .documento {
            background: white;
            width: 190mm;
            margin: 0 auto 20px;
            padding: 10mm;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            align-items: center;
            padding-bottom: 5mm;
            gap: 8mm;
            border-bottom: 2px solid #000;
            margin-bottom: 5mm;
        }
        
        .logo {
            width: 35mm;
            height: 35mm;
        }
        
        .titulo {
            flex: 1;
            text-align: center;
        }
        
        .titulo h1 {
            font-size: 18px;
            margin-bottom: 3mm;
        }
        
        .titulo h2 {
            font-size: 15px;
            font-weight: bold;
        }
        
        .info-arbitro {
            padding: 3mm 0;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            border-bottom: 1px solid #000;
            margin-bottom: 3mm;
        }
        
        .concepto {
            padding: 3mm 0;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 2px solid #000;
            margin-bottom: 5mm;
        }
        
        /* TABLA DE PARTIDOS */
        .partido-item {
            border: 1px solid #000;
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }
        
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tabla-datos td {
            border: 1px solid #000;
            padding: 2mm;
            font-size: 11px;
        }
        
        .tabla-datos td:first-child {
            font-weight: bold;
            width: 80px;
            background: #f5f5f5;
        }
        
        .tabla-datos td:nth-child(3) {
            font-weight: bold;
            width: 80px;
            background: #f5f5f5;
        }
        
        /* FOOTER FINAL (una sola vez) */
        .footer-final {
            margin-top: 10mm;
            page-break-inside: avoid;
        }
        
        .footer-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5mm;
        }
        
        .footer-tabla td {
            border: 1px solid #000;
            padding: 5mm;
            font-size: 12px;
        }
        
        .total-cell {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
        }
        
        .observaciones-cell {
            height: 40mm;
            vertical-align: top;
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <div class="resumen">
        <strong><?= h($arbitroInfo['nombre'] . ' ' . $arbitroInfo['apellido']) ?></strong> | 
        CC: <?= h($arbitroInfo['cedula']) ?> | 
        <strong><?= $totalPartidos ?></strong> partidos | 
        Total: <strong>$<?= number_format($totalPagar, 0, ',', '.') ?></strong>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir</button>
        <button onclick="window.close()" class="btn btn-back">✕ Cerrar</button>
    </div>
</div>

<div class="documento">
    <!-- HEADER (una sola vez) -->
    <div class="header">
        <?php if (file_exists('assets/img/logo.png')): ?>
        <img src="assets/img/logo.png" alt="Logo AAA" class="logo">
        <?php endif; ?>
        <div class="titulo">
            <h1>Académia Antioqueña de Árbitros</h1>
            <h2>CUENTA DE COBRO:</h2>
        </div>
    </div>
    
    <!-- INFO ÁRBITRO (una sola vez) -->
    <div class="info-arbitro">
        <div><strong><?= strtoupper($arbitroInfo['nombre'] . ' ' . $arbitroInfo['apellido']) ?></strong></div>
        <div>Cédula: <?= h($arbitroInfo['cedula']) ?> &nbsp;&nbsp; C</div>
    </div>
    
    <!-- CONCEPTO (una sola vez) -->
    <div class="concepto">
        CONCEPTO: SERVICIO DE ARBITRAJE A LA CORPORACION A.A.A NIT:900302408-2
    </div>
    
    <!-- TODOS LOS PARTIDOS -->
    <?php foreach ($partidos_data as $index => $partido): ?>
    <div class="partido-item">
        <table class="tabla-datos">
            <tr>
                <td>Partido <?= $index + 1 ?>:</td>
                <td colspan="3" style="font-weight:bold; background:#e3f2fd;">
                    <?= strtoupper($partido['equipos']) ?> - $<?= number_format($partido['tarifa'], 0, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td>Hora:</td>
                <td><?= $partido['hora'] ?></td>
                <td>Designado:</td>
                <td><?= $partido['rol'] ?></td>
            </tr>
            <tr>
                <td>Lugar:</td>
                <td><?= strtoupper($partido['lugar']) ?></td>
                <td>Tarifa:</td>
                <td><strong>$<?= number_format($partido['tarifa'], 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td>Fecha:</td>
                <td colspan="3"><?= $partido['fecha'] ?></td>
            </tr>
            <tr>
                <td>Torneo:</td>
                <td colspan="3"><?= strtoupper($partido['torneo']) ?> - <?= strtoupper($partido['categoria']) ?></td>
            </tr>
            <tr>
                <td>Asistente 1:</td>
                <td colspan="3"><?= strtoupper($partido['asistente1']) ?></td>
            </tr>
            <tr>
                <td>Asistente 2:</td>
                <td colspan="3"><?= strtoupper($partido['asistente2']) ?></td>
            </tr>
            <?php if ($partido['emergente']): ?>
            <tr>
                <td>Emergente:</td>
                <td colspan="3"><?= strtoupper($partido['emergente']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endforeach; ?>
    
    <!-- FOOTER FINAL (UNA SOLA VEZ) -->
    <div class="footer-final">
        <table class="footer-tabla">
            <tr>
                <td style="width:50%; text-align:center; font-weight:bold; vertical-align:middle; height:25mm;">
                    Firma y Cédula de Árbitro
                </td>
                <td style="width:20%; text-align:center; font-weight:bold; vertical-align:middle;">
                    TOTAL
                </td>
                <td class="total-cell">
                    $<?= number_format($totalPagar, 0, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td class="observaciones-cell">
                    <strong>Observaciones</strong>
                </td>
                <td colspan="2" style="text-align:center; vertical-align:middle;">
                    <strong>Autorizada</strong>
                </td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
<?php
$stmt->close();
$stmtArbitro->close();
$conexion->close();
?>