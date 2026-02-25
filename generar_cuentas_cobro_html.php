<?php
session_start();
require_once 'config.php';

// Acepta tanto POST (formulario viejo) como GET (links nuevos desde impresion.php)
$req = array_merge($_GET, $_POST);

if (!isset($req['fechaInicio']) || !isset($req['fechaFin']) || !isset($req['arbitro'])) {
    die('Error: Faltan parámetros requeridos (fechaInicio, fechaFin, arbitro)');
}

$fechaInicio = $req['fechaInicio'];
$fechaFin    = $req['fechaFin'];
$idArbitro   = (int)$req['arbitro'];

// Torneos: puede venir como array (torneos[]) o como idTorneo simple
$torneosIds = [];
if (!empty($req['torneos'])) {
    $torneosIds = array_map('intval', (array)$req['torneos']);
    $torneosIds = array_filter($torneosIds);
} elseif (!empty($req['torneo'])) {
    $torneosIds = [(int)$req['torneo']];
}

$conexion = getDBConnection();

// Filtro de torneos dinámico
$whereTorneo = '';
$extraTypes  = '';
$extraValues = [];
if (!empty($torneosIds)) {
    $phs         = implode(',', array_fill(0, count($torneosIds), '?'));
    $whereTorneo = "AND t.idTorneo IN ($phs)";
    $extraTypes  = str_repeat('i', count($torneosIds));
    $extraValues = array_values($torneosIds);
}

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
        a1.nombre    AS nombreArbitro1,
        a1.apellido  AS apellidoArbitro1,
        a1.cedula    AS cedulaArbitro1,
        a2.idArbitro AS idArbitro2,
        a2.nombre    AS nombreArbitro2,
        a2.apellido  AS apellidoArbitro2,
        a3.idArbitro AS idArbitro3,
        a3.nombre    AS nombreArbitro3,
        a3.apellido  AS apellidoArbitro3,
        a4.idArbitro AS idArbitro4,
        a4.nombre    AS nombreArbitro4,
        a4.apellido  AS apellidoArbitro4
    FROM partido p
    INNER JOIN equipo e1              ON p.idEquipo1              = e1.idEquipo
    INNER JOIN equipo e2              ON p.idEquipo2              = e2.idEquipo
    INNER JOIN torneo t               ON p.idTorneoPartido        = t.idTorneo
    INNER JOIN categoriaPagoArbitro cp ON p.idCategoriaPagoArbitro = cp.idCategoriaPagoArbitro
    LEFT  JOIN arbitro a1             ON p.idArbitro1             = a1.idArbitro
    LEFT  JOIN arbitro a2             ON p.idArbitro2             = a2.idArbitro
    LEFT  JOIN arbitro a3             ON p.idArbitro3             = a3.idArbitro
    LEFT  JOIN arbitro a4             ON p.idArbitro4             = a4.idArbitro
    WHERE p.fecha BETWEEN ? AND ?
      AND (p.idArbitro1 = ? OR p.idArbitro2 = ? OR p.idArbitro3 = ? OR p.idArbitro4 = ?)
    $whereTorneo
    ORDER BY p.fecha ASC, p.hora ASC
";

$types  = 'ssiiii' . $extraTypes;
$params = array_merge([$fechaInicio, $fechaFin, $idArbitro, $idArbitro, $idArbitro, $idArbitro], $extraValues);

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$partidos = $stmt->get_result();

if ($partidos->num_rows === 0) {
    die('<div style="text-align:center;padding:50px;font-family:Arial;">
            <h2>No se encontraron partidos</h2>
            <p>No hay partidos para este árbitro en el rango de fechas seleccionado.</p>
            <button onclick="window.close()" style="padding:10px 20px;background:#2563eb;color:white;border:none;border-radius:6px;cursor:pointer;">Cerrar</button>
         </div>');
}

// Info del árbitro (incluye categoriaArbitro)
$stmtArbitro = $conexion->prepare("SELECT nombre, apellido, cedula, categoriaArbitro FROM arbitro WHERE idArbitro = ?");
$stmtArbitro->bind_param('i', $idArbitro);
$stmtArbitro->execute();
$arbitroInfo = $stmtArbitro->get_result()->fetch_assoc();

function formatearFecha($fecha) {
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
              7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $ts    = strtotime($fecha);
    return $dias[date('w',$ts)].', '.date('j',$ts).' De '.$meses[(int)date('n',$ts)].' De '.date('Y',$ts);
}

function formatearHora($hora) {
    $ts = strtotime($hora);
    return date('g:i A', $ts);
}

// Preparar partidos
$partidos_data = [];
$totalPagar    = 0;

while ($partido = $partidos->fetch_assoc()) {
    $rol = ''; $tarifa = 0;
    $arbitroPpal = ''; $asistente1 = ''; $asistente2 = ''; $emergente = '';

    $n1 = trim(($partido['nombreArbitro1']??'').' '.($partido['apellidoArbitro1']??''));
    $n2 = $partido['nombreArbitro2'] ? trim($partido['nombreArbitro2'].' '.$partido['apellidoArbitro2']) : '';
    $n3 = $partido['nombreArbitro3'] ? trim($partido['nombreArbitro3'].' '.$partido['apellidoArbitro3']) : '';
    $n4 = $partido['nombreArbitro4'] ? trim($partido['nombreArbitro4'].' '.$partido['apellidoArbitro4']) : '';

    if ($partido['idArbitro1'] == $idArbitro) {
        $rol = 'ARBITRO'; $tarifa = $partido['pagoArbitro1'];
        $arbitroPpal = $n1; $asistente1 = $n2; $asistente2 = $n3; $emergente = $n4;
    } elseif ($partido['idArbitro2'] == $idArbitro) {
        $rol = 'ASISTENTE 1'; $tarifa = $partido['pagoArbitro2'];
        $arbitroPpal = $n1; $asistente1 = $n2; $asistente2 = $n3; $emergente = $n4;
    } elseif ($partido['idArbitro3'] == $idArbitro) {
        $rol = 'ASISTENTE 2'; $tarifa = $partido['pagoArbitro3'];
        $arbitroPpal = $n1; $asistente1 = $n2; $asistente2 = $n3; $emergente = $n4;
    } elseif ($partido['idArbitro4'] == $idArbitro) {
        $rol = 'EMERGENTE'; $tarifa = $partido['pagoArbitro4'];
        $arbitroPpal = $n1; $asistente1 = $n2; $asistente2 = $n3; $emergente = $n4;
    }

    $totalPagar += $tarifa;

    $partidos_data[] = [
        'equipos'      => strtoupper($partido['equipoLocal']).' vs '.strtoupper($partido['equipoVisitante']),
        'hora'         => $partido['hora'],
        'lugar'        => strtoupper($partido['canchaLugar']),
        'fecha'        => formatearFecha($partido['fecha']),
        'torneo'       => strtoupper($partido['nombreTorneo']),
        'categoria'    => strtoupper($partido['categoriaText'] ?? $partido['nombreCategoria']),
        'rol'          => $rol,
        'tarifa'       => $tarifa,
        'arbitroPpal'  => strtoupper($arbitroPpal),
        'asistente1'   => strtoupper($asistente1),
        'asistente2'   => strtoupper($asistente2),
        'emergente'    => strtoupper($emergente),
    ];
}

$totalPartidos  = count($partidos_data);
$nombreCompleto = strtoupper($arbitroInfo['nombre'].' '.$arbitroInfo['apellido']);
$categoria      = strtoupper($arbitroInfo['categoriaArbitro'] ?? '');
$cedula         = $arbitroInfo['cedula'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cuenta de Cobro – <?= h($nombreCompleto) ?></title>
<style>
/* ── IMPRESIÓN ── */
@media print {
    .no-print { display:none !important; }
    body { margin:0; padding:0; background:white; }
    @page { margin:8mm 10mm; size:letter portrait; }
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Times New Roman', Times, serif;
    background: #d4d4d4;
    font-size: 11px;
    color: #000;
}

/* ── TOOLBAR ── */
.toolbar {
    font-family: Arial, sans-serif;
    background: #fff;
    padding: 7px 16px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.resumen { font-size:12px; }
.resumen strong { color:#1a56db; }
.btn {
    padding:5px 13px; border:none; border-radius:4px;
    cursor:pointer; font-weight:bold; font-size:12px; margin-left:5px;
    font-family:Arial,sans-serif;
}
.btn-print { background:#10b981; color:#fff; }
.btn-back  { background:#6b7280; color:#fff; }

/* ── HOJA ── */
.hoja {
    width: 190mm;
    margin: 0 auto 10px;
    background: #fff;
    padding: 5mm 7mm 6mm;
    box-shadow: 0 2px 10px rgba(0,0,0,.2);
}

/* ── ENCABEZADO ── */
.enc {
    display: flex;
    align-items: center;
    gap: 3mm;
    border-bottom: 2.5px solid #000;
    padding-bottom: 2mm;
    margin-bottom: 2mm;
}
.enc img { width:16mm; height:16mm; }
.enc-texto { flex:1; text-align:center; }
.enc-texto h1 { font-size:14px; font-weight:bold; }
.enc-texto h2 { font-size:11px; font-weight:bold; margin-top:1mm; }

/* ── DATOS ÁRBITRO ── */
.arb-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5mm 0;
    border-bottom: 1px solid #444;
    margin-bottom: 2mm;
    font-size: 11px;
}
.arb-row .arb-nombre { font-weight:bold; font-size:12px; }
.arb-row .arb-datos  { display:flex; gap:5mm; }

/* ── CONCEPTO ── */
.concepto {
    font-weight: bold;
    font-size: 10px;
    border-bottom: 2px solid #000;
    padding-bottom: 1.5mm;
    margin-bottom: 3mm;
    letter-spacing: .01em;
}

/* =========================================
   CUADRO DE PARTIDO — replica exacta imagen
   ========================================= */
.cuadro {
    border: 1px solid #666;
    margin-bottom: 3mm;
    page-break-inside: avoid;
}

/* Cada fila horizontal */
.fila {
    display: flex;
    border-bottom: 1px solid #666;
    min-height: 5.5mm;
}
.fila:last-child { border-bottom: none; }

/* Etiqueta izquierda gris */
.lbl {
    background: #f0f0f0;
    font-weight: bold;
    font-size: 10px;
    padding: 0.8mm 2mm;
    display: flex;
    align-items: center;
    white-space: nowrap;
    border-right: 1px solid #666;
    min-width: 19mm;
    flex-shrink: 0;
}

/* Valor */
.val {
    font-size: 10.5px;
    padding: 0.8mm 2mm;
    display: flex;
    align-items: center;
    flex: 1;
}
.val.negrita { font-weight: bold; }

/* Separador vertical entre col-izq y col-der */
.sep { border-right: 1px solid #666; }

/* Fila equipos: label + equipos centrado (span total) */
.fila-equipos .lbl   { min-width: 19mm; }
.fila-equipos .val   { justify-content: center; font-weight: bold; font-size: 11px; }

/* Columnas iguales izq/der */
.col-izq { display:flex; flex:1; border-right:1px solid #666; }
.col-der  { display:flex; flex:1; }
.col-der .lbl { min-width: 22mm; }

/* ── TOTAL ── */
.total-bloque {
    border: 1px solid #000;
    margin-top: 4mm;
    page-break-inside: avoid;
}
.total-fila {
    display: flex;
    border-bottom: 1px solid #000;
    min-height: 8mm;
}
.total-fila:last-child { border-bottom:none; }

.tf-firma {
    flex: 1;
    border-right: 1px solid #000;
    padding: 2mm 3mm;
    font-weight: bold;
    font-size: 10.5px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    min-height: 18mm;
}
.tf-total-lbl {
    width: 22mm;
    border-right: 1px solid #000;
    font-weight: bold;
    font-size: 10.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1mm;
    flex-shrink: 0;
}
.tf-total-val {
    flex: 1;
    font-size: 15px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 1mm 3mm;
}
.tf-obs {
    flex: 1;
    border-right: 1px solid #000;
    padding: 1.5mm 3mm;
    font-size: 10px;
    min-height: 14mm;
    vertical-align: top;
}
.tf-aut {
    width: 40mm;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 10.5px;
}
</style>
</head>
<body>

<!-- TOOLBAR -->
<div class="toolbar no-print">
    <div class="resumen">
        <strong><?= h($nombreCompleto) ?></strong> &nbsp;|&nbsp;
        CC: <?= h($cedula) ?> &nbsp;|&nbsp;
        <strong><?= $totalPartidos ?></strong> partido<?= $totalPartidos!==1?'s':'' ?> &nbsp;|&nbsp;
        Total: <strong>$<?= number_format($totalPagar,0,',','.') ?></strong>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir</button>
        <button onclick="window.close()"  class="btn btn-back">✕ Cerrar</button>
    </div>
</div>

<!-- HOJA -->
<div class="hoja">

    <!-- ENCABEZADO -->
    <div class="enc">
        <?php if (file_exists('assets/img/logo.png')): ?>
        <img src="assets/img/logo.png" alt="Logo AAA">
        <?php endif; ?>
        <div class="enc-texto">
            <h1 style="font-size:24px;">Académia Antioqueña de Árbitros</h1>
            CONCEPTO: SERVICIO DE ARBITRAJE A LA CORPORACION A.A.A &nbsp; NIT:900302408-2
        </div>
    </div>

    <!-- ÁRBITRO -->
    <div class="arb-row">
        <span class="arb-nombre"><?= h($nombreCompleto) ?></span>
    </div>
    <div class="arb-row">
        <span style="font-size:16px;">CAT: <?= h($categoria) ?></span>
        <span class="arb-datos">
            <span style="font-size:18px;">Cédula: <?= h($cedula) ?></span>
        </span>
    </div>

    <!-- CONCEPTO -->
    <div class="concepto">
        
        <h2>CUENTA DE COBRO:</h2>
    </div>

    <!-- ── CUADROS ── -->
    <?php foreach ($partidos_data as $p): ?>
    <div class="cuadro">

        <!-- Equipos -->
        <div class="fila fila-equipos">
            <div class="lbl">Equipos:</div>
            <div class="val"><?= h($p['equipos']) ?></div>
        </div>

        <!-- Hora | Designado -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Hora:</div>
                <div class="val"><?= h(formatearHora($p['hora'])) ?></div>
            </div>
            <div class="col-der">
                <div class="lbl">Designado:</div>
                <div class="val negrita"><?= h($p['rol']) ?></div>
                <div class="col-der">
                <div class="lbl">Tarifa:</div>
                <div class="val negrita">$<?= number_format($p['tarifa'],0,',','.') + 1000000 ?></div>
            </div>
            </div>
        </div>

        <!-- Lugar | Tarifa -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Lugar:</div>
                <div class="val"><?= h($p['lugar']) ?></div>
            </div>
            <div class="col-der">
                <div class="lbl">Árbitro:</div>
                <div class="val"><?= h($p['arbitroPpal']) ?></div>
            </div>
        </div>

        <!-- Fecha | Árbitro principal -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Fecha:</div>
                <div class="val"><?= h($p['fecha']) ?></div>
            </div>
            <div class="col-der">
                <div class="lbl">Asistente 1:</div>
                <div class="val"><?= h($p['asistente1']) ?></div>
            </div>
        </div>

        <!-- Torneo | Asistente 1 -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Torneo:</div>
                <div class="val"><?= h($p['torneo']) ?></div>
            </div>
            <div class="col-der">
                <div class="lbl">Emergente:</div>
                <div class="val"><?= h($p['emergente']) ?></div>
            </div>
        </div>

        <!-- Categoría | Emergente -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Categoría:</div>
                <div class="val"><?= h($p['categoria']) ?></div>
            </div>
            <div class="col-der">
                <div class="lbl">Estado:</div>
                <div class="val">PAGO INMEDIATO</div>
            </div>
        </div>

        <!-- Observaciones | Estado -->
        <div class="fila">
            <div class="col-izq">
                <div class="lbl">Observaciones:</div>
                <div class="val"></div>
            </div>
            
        </div>

    </div><!-- /cuadro -->
    <?php endforeach; ?>

    <!-- TOTAL FINAL -->
    <div class="total-bloque">
        <div class="total-fila">
            <div class="tf-firma">Firma y Cédula de Árbitro</div>
            <div class="tf-total-lbl">TOTAL</div>
            <div class="tf-total-val">$<?= number_format($totalPagar,0,',','.') ?></div>
        </div>
        <div class="total-fila">
            <div class="tf-obs"><strong>Observaciones</strong></div>
            <div class="tf-aut">Autorizada</div>
        </div>
    </div>

</div><!-- /hoja -->

</body>
</html>
<?php
$stmt->close();
$stmtArbitro->close();
$conexion->close();
?>