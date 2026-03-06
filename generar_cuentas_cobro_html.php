<?php
session_start();
require_once 'config.php';

$req = array_merge($_GET, $_POST);

if (!isset($req['fechaInicio']) || !isset($req['fechaFin']) || !isset($req['arbitro'])) {
    die('Error: Faltan parámetros requeridos (fechaInicio, fechaFin, arbitro)');
}

$fechaInicio = $req['fechaInicio'];
$fechaFin    = $req['fechaFin'];
$idArbitro   = (int)$req['arbitro'];

$torneosIds = [];
if (!empty($req['torneos'])) {
    $torneosIds = array_map('intval', (array)$req['torneos']);
    $torneosIds = array_filter($torneosIds);
} elseif (!empty($req['torneo'])) {
    $torneosIds = [(int)$req['torneo']];
}

$conexion = getDBConnection();

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
        p.observaciones,
        e1.nombreEquipo AS equipoLocal,
        e2.nombreEquipo AS equipoVisitante,
        t.nombreTorneo,
        cp.nombreCategoria,
        cp.pagoArbitro1,
        cp.pagoArbitro2,
        cp.pagoArbitro3,
        cp.pagoArbitro4,
        cp.tipopago,
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
    INNER JOIN equipo e1               ON p.idEquipo1              = e1.idEquipo
    INNER JOIN equipo e2               ON p.idEquipo2              = e2.idEquipo
    INNER JOIN torneo t                ON p.idTorneoPartido        = t.idTorneo
    INNER JOIN categoriaPagoArbitro cp ON p.idCategoriaPagoArbitro = cp.idCategoriaPagoArbitro
    LEFT  JOIN arbitro a1              ON p.idArbitro1             = a1.idArbitro
    LEFT  JOIN arbitro a2              ON p.idArbitro2             = a2.idArbitro
    LEFT  JOIN arbitro a3              ON p.idArbitro3             = a3.idArbitro
    LEFT  JOIN arbitro a4              ON p.idArbitro4             = a4.idArbitro
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

// ── Info del árbitro ──
// categoriaArbitro es un varchar con texto (ej: "PRINCIPAL"), no una FK.
// NO hacer JOIN con categoriaPagoArbitro. El tipopago viene de cada partido.
$stmtArbitro = $conexion->prepare(
    "SELECT nombre, apellido, cedula, categoriaArbitro
     FROM arbitro
     WHERE idArbitro = ?"
);
$stmtArbitro->bind_param('i', $idArbitro);
$stmtArbitro->execute();
$arbitroInfo = $stmtArbitro->get_result()->fetch_assoc() ?? [];

$stmtContador = $conexion->prepare("
    SELECT COALESCE(totalImpresiones, 0) AS totalImpresiones
    FROM contador_impresion
    WHERE idArbitro = ?
");
$stmtContador->bind_param('i', $idArbitro);
$stmtContador->execute();
$contadorInfo     = $stmtContador->get_result()->fetch_assoc();
$totalImpresiones = $contadorInfo['totalImpresiones'] ?? 0;

function formatearFecha($fecha) {
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
              7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $ts    = strtotime($fecha);
    return $dias[date('w',$ts)].', '.date('j',$ts).' De '.$meses[(int)date('n',$ts)].' De '.date('Y',$ts);
}

function formatearHora($hora) {
    return date('g:i A', strtotime($hora));
}

$tipoPagoLabels = ['inmediato' => 'Pago Inmediato', 'quincenal' => 'Pago Quincenal'];

// ── Preparar partidos ──
$partidos_data = [];
$totalPagar    = 0;

while ($partido = $partidos->fetch_assoc()) {
    $rol = ''; $tarifa = 0;

    $n1 = trim(($partido['nombreArbitro1'] ?? '').' '.($partido['apellidoArbitro1'] ?? ''));
    $n2 = !empty($partido['nombreArbitro2']) ? trim($partido['nombreArbitro2'].' '.$partido['apellidoArbitro2']) : '';
    $n3 = !empty($partido['nombreArbitro3']) ? trim($partido['nombreArbitro3'].' '.$partido['apellidoArbitro3']) : '';
    $n4 = !empty($partido['nombreArbitro4']) ? trim($partido['nombreArbitro4'].' '.$partido['apellidoArbitro4']) : '';

    if ($partido['idArbitro1'] == $idArbitro) {
        $rol = 'ÁRBITRO'; $tarifa = $partido['pagoArbitro1'];
    } elseif ($partido['idArbitro2'] == $idArbitro) {
        $rol = 'ASISTENTE 1'; $tarifa = $partido['pagoArbitro2'];
    } elseif ($partido['idArbitro3'] == $idArbitro) {
        $rol = 'ASISTENTE 2'; $tarifa = $partido['pagoArbitro3'];
    } elseif ($partido['idArbitro4'] == $idArbitro) {
        $rol = 'EMERGENTE'; $tarifa = $partido['pagoArbitro4'];
    }

    $totalPagar += $tarifa;

    // tipopago viene directo del partido (cp.tipopago)
    $tipoPagoTexto = $tipoPagoLabels[$partido['tipopago']] ?? ucfirst($partido['tipopago'] ?? '');

    $partidos_data[] = [
        'equipos'      => strtoupper($partido['equipoLocal']).' vs '.strtoupper($partido['equipoVisitante']),
        'hora'         => $partido['hora'],
        'lugar'        => strtoupper($partido['canchaLugar']),
        'fecha'        => formatearFecha($partido['fecha']),
        'torneo'       => strtoupper($partido['nombreTorneo']),
        'categoria'    => strtoupper($partido['categoriaText'] ?: $partido['nombreCategoria']),
        'observaciones'=> $partido['observaciones'],
        'rol'          => $rol,
        'tarifa'       => $tarifa,
        'tipopago'     => $tipoPagoTexto,
        'arbitroPpal'  => strtoupper($n1),
        'asistente1'   => strtoupper($n2),
        'asistente2'   => strtoupper($n3),
        'emergente'    => strtoupper($n4),
    ];
}

$totalPartidos  = count($partidos_data);
$nombreCompleto = strtoupper(($arbitroInfo['nombre'] ?? '').' '.($arbitroInfo['apellido'] ?? ''));
$categoria      = strtoupper($arbitroInfo['categoriaArbitro'] ?? '');
$cedula         = $arbitroInfo['cedula'] ?? '';
$observaciones   = $partidos_data[0]['observaciones'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cuenta de Cobro – <?= h($nombreCompleto) ?></title>
<style>
@media print {
    .no-print { display:none !important; }
    body { margin:0; padding:0; background:white; }
    @page { size: letter portrait; margin: 14mm 10mm 0mm 10mm; }
    .hoja { 
        margin:0 !important; 
        padding:4mm 7mm 12mm !important; 
        box-shadow:none !important; 
        width:100% !important;
        page-break-after: always;
        display:flex;
        flex-direction:column;
        min-height: 257mm;
    }
    .hoja:last-child { page-break-after: auto; }
    .hoja-contenido { flex:1; }
    .footer-hoja { margin-top:auto; }
    
}

* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Times New Roman',Times,serif; background:#d4d4d4; font-size:15px; color:#000; }

.toolbar {
    font-family:Arial,sans-serif; background:#fff; padding:7px 16px;
    margin-bottom:8px; box-shadow:0 1px 4px rgba(0,0,0,.2);
    display:flex; justify-content:space-between; align-items:center;
}
.resumen { font-size:15px; }
.resumen strong { color:#1a56db; }
.btn { padding:5px 13px; border:none; border-radius:4px; cursor:pointer; font-weight:bold; font-size:15px; margin-left:5px; font-family:Arial,sans-serif; }
.btn-print { background:#10b981; color:#fff; }
.btn-back  { background:#6b7280; color:#fff; }

.hoja { 
    width:190mm; margin:0 auto 10px; background:#fff; padding:4mm 7mm 4mm; 
    box-shadow:0 2px 10px rgba(0,0,0,.2);
    display:flex; flex-direction:column;
}
.hoja-contenido { flex:1; }
.footer-hoja { margin-top:auto; }

.enc { display:flex; align-items:center; gap:3mm; border-bottom:2.5px solid #000; padding-bottom:1.5mm; margin-bottom:1.5mm; }
.enc img { width:14mm; height:14mm; }
.enc-texto { flex:1; text-align:center; }
.enc-texto h1 { font-size:14px; font-weight:bold; }
.enc-texto h2 { font-size:1px; font-weight:bold; margin-top:0.5mm; }

.arb-row { display:flex; justify-content:space-between; align-items:center; padding:0.8mm 0; border-bottom:1px solid #444; margin-bottom:1mm; font-size:11px; }
.arb-row .arb-nombre { font-weight:bold; font-size:12px; }
.arb-row .arb-datos  { display:flex; gap:5mm; }

.concepto { font-weight:bold; font-size:10px; border-bottom:2.5px solid #000; padding-bottom:1mm; margin-bottom:2mm; letter-spacing:.01em; }

.cuadro { border:1px solid #666; margin-bottom:2mm; page-break-inside:avoid; }
.fila { display:flex; border-bottom:1px solid #666; min-height:4.5mm; }
.fila:last-child { border-bottom:none; }
.lbl { background:#f0f0f0; font-weight:bold; font-size:9.5px; padding:0.5mm 2mm; display:flex; align-items:center; white-space:nowrap; border-right:1px solid #666; min-width:19mm; flex-shrink:0; }
.val { font-size:9.5px; padding:0.5mm 2mm; display:flex; align-items:center; flex:1; }
.val.negrita { font-weight:bold; }
.fila-equipos .lbl { min-width:19mm; }
.fila-equipos .val { justify-content:center; font-weight:bold; font-size:10px; }
.col-izq { display:flex; flex:1; border-right:1px solid #666; }
.col-der  { display:flex; flex:1; }
.col-der .lbl { min-width:22mm; }
.fila > .col-izq:only-child { border-right:none; }

.total-bloque { border:1px solid #000; margin-top:2mm; page-break-inside:avoid; }
.total-fila { display:flex; border-bottom:1px solid #000; min-height:5mm; }
.total-fila:last-child { border-bottom:none; }
.tf-firma { flex:1; border-right:1px solid #000; padding:1.5mm 28mm; font-weight:bold; font-size:10px; display:flex; align-items:flex-end; justify-content:center; min-height:16mm;}
.tf-total-lbl { width:22mm; border-right:1px solid #000; font-weight:bold; font-size:10px; display:flex; align-items:center; justify-content:center; padding:1mm; flex-shrink:0; }
.tf-total-val { flex:1; font-size:14px; font-weight:bold; display:flex; align-items:center; justify-content:flex-end; padding:1mm 3mm; }
.tf-obs { flex:1; border-right:1px solid #000; padding:1mm 3mm; font-size:10px;  vertical-align:top; min-height:16mm;}
.tf-aut { width:40mm; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:10px; }
</style>
</head>
<body>

<!-- TOOLBAR -->
<!-- <div class="toolbar no-print">
    <div class="resumen">
        <strong><?= h($nombreCompleto) ?></strong> &nbsp;|&nbsp;
        CC: <?= h($cedula) ?> &nbsp;|&nbsp;
        <strong><?= $totalPartidos ?></strong> partido<?= $totalPartidos !== 1 ? 's' : '' ?> &nbsp;|&nbsp;
        Total: <strong>$<?= number_format($totalPagar, 0, ',', '.') ?></strong>
    </div>
    <div>
        <button onclick="paginarYImprimir()" class="btn btn-print">🖨️ Imprimir</button>
        <button onclick="window.close()" class="btn btn-back">✕ Cerrar</button>
    </div>
</div> -->

<script>
const PARTIDOS = <?= json_encode(array_map(function($p) {
    return [
        'equipos'      => $p['equipos'],
        'hora'         => formatearHora($p['hora']),
        'lugar'        => $p['lugar'],
        'fecha'        => $p['fecha'],
        'torneo'       => $p['torneo'],
        'categoria'    => $p['categoria'],
        'observaciones'=> $p['observaciones'] ?? '',
        'rol'          => $p['rol'],
        'tarifa'       => $p['tarifa'],
        'tipopago'     => $p['tipopago'],
        'arbitroPpal'  => $p['arbitroPpal'],
        'asistente1'   => $p['asistente1'],
        'asistente2'   => $p['asistente2'],
        'emergente'    => $p['emergente'],
    ];
}, $partidos_data), JSON_UNESCAPED_UNICODE) ?>;

const TOTAL_PAGAR = <?= $totalPagar ?>;
const NOMBRE      = <?= json_encode($nombreCompleto, JSON_UNESCAPED_UNICODE) ?>;
const CEDULA_ARB  = <?= json_encode($cedula) ?>;
const CATEGORIA   = <?= json_encode($categoria, JSON_UNESCAPED_UNICODE) ?>;
const CUENTA_NUM  = <?= json_encode((string)$totalImpresiones) ?>;
const LOGO_EXISTS = <?= file_exists('assets/img/logo.png') ? 'true' : 'false' ?>;

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
}
function fmt(n) {
    return '$' + Number(n).toLocaleString('es-CO', {minimumFractionDigits:0, maximumFractionDigits:0});
}

function crearCuadroHTML(p) {
    // Construir lista de miembros del equipo SIN el protagonista, en orden
    const todosMiembros = [
        { lbl: 'Árbitro:',     val: p.arbitroPpal, rol: 'ÁRBITRO'     },
        { lbl: 'Asistente 1:', val: p.asistente1,  rol: 'ASISTENTE 1' },
        { lbl: 'Asistente 2:', val: p.asistente2,  rol: 'ASISTENTE 2' },
        { lbl: 'Emergente:',   val: p.emergente,   rol: 'EMERGENTE'   },
        { lbl: 'Estado de pago:',    val: esc(p.tipopago) },
    ];
    // Excluimos al protagonista y entradas sin valor
    const miembros = todosMiembros.filter(m => m.rol !== p.rol && m.val);

    // Filas izquierdas fijas en orden
    const filasIzq = [
        { lbl: 'Lugar:',     val: esc(p.lugar) },
        { lbl: 'Fecha:',     val: esc(p.fecha) },
        { lbl: 'Torneo:',    val: esc(p.torneo) },
        { lbl: 'Categoría:', val: esc(p.categoria) },
    ];

    // Emparejar cada fila izquierda con el miembro disponible (sin huecos)
    let filasHTML = '';
    for (let i = 0; i < filasIzq.length; i++) {
        const izq = filasIzq[i];
        const der = miembros[i];
        const colDer = der
            ? `<div class="col-der"><div class="lbl">${der.lbl}</div><div class="val">${esc(der.val)}</div></div>`
            : '';
        const sinBorde = der ? '' : ' style="border-right:none;"';
        filasHTML += `<div class="fila">
            <div class="col-izq"${sinBorde}><div class="lbl">${izq.lbl}</div><div class="val">${izq.val}</div></div>
            ${colDer}
        </div>`;
    }

    return `<div class="cuadro">
        <div class="fila fila-equipos"><div class="lbl">Equipos:</div><div class="val">${esc(p.equipos)}</div></div>
        <div class="fila">
            <div class="col-izq"><div class="lbl">Hora:</div><div class="val">${esc(p.hora)}</div></div>
            <div class="col-der"><div class="lbl">Designado:</div><div class="val negrita">${esc(p.rol)}</div><div class="lbl">Tarifa:</div><div class="val negrita">${fmt(p.tarifa)}</div></div>
        </div>
        ${filasHTML}
        <div class="fila">
            <div class="col-izq" style="border-right:none;"><div class="lbl" style="font-size:12px;">Observaciones:</div><div class="val" style="font-size:10px;">${esc(p.observaciones)}</div></div>
        </div>
    </div>`;
}
function crearEncabezadoHTML(numPag, totalPags) {
    const logo = LOGO_EXISTS ? `<img src="assets/img/logo.png" alt="Logo AAA">` : '';
    return `
    <div class="enc">
        ${logo}
        <div class="enc-texto">
            <div style="text-align:right;font-size:10px;margin-top:2mm;">Página ${numPag} de ${totalPags}</div>
            <h1 style="font-size:24px;">Academia Antioqueña de Árbitros</h1>
            CONCEPTO: SERVICIO DE ARBITRAJE A LA CORPORACION A.A.A &nbsp; NIT:900302408-2
        </div>
    </div>
    <div class="arb-row">
        <span class="arb-nombre">${esc(NOMBRE)}</span>
        <span class="arb-datos"><span style="font-size:14px;">Cédula: ${esc(CEDULA_ARB)}</span></span>
        </div>
        <div class="arb-row">
            <span style="font-size:14px;">Categoría: ${esc(CATEGORIA)}</span>
            <div style="text-align:left;"><h3>CUENTA DE COBRO: ${esc(CUENTA_NUM)}</h3></div>
        </div>
    </div>
    `;
}

function crearFooterHTML(acumulado, esFinal) {
    const label  = esFinal ? 'TOTAL' : 'SUBTOTAL';
    const monto  = esFinal ? TOTAL_PAGAR : acumulado;
    const continua = !esFinal ? ' &nbsp;·&nbsp; <em style="font-weight:normal;"></em>' : '';
    return `
    <div class="total-bloque footer-hoja" style="margin-top:2mm; display: flex; flex-direction: column;">
        <div class="total-fila">
            <div class="tf-firma" style="font-size:15px;">Firma y Cédula de Árbitro</div>
            <div class="tf-total-lbl">${label}</div>
            <div class="tf-total-val">${fmt(monto)}</div>
        </div>
        <div class="total-fila">
            <div class="tf-obs" style="font-size:15px;"><strong>Observaciones</strong></div>
            <div class="tf-aut" style="font-size:15px;">Autorizada</div>
        </div>
    </div>`;
}

// Crea un div de medición oculto con ancho real de hoja
function crearSonda() {
    const s = document.createElement('div');
    s.className = 'hoja';
    // Anulamos min-height para que mida solo el contenido real
    s.setAttribute('style', 'position:absolute!important;top:-9999px!important;left:0!important;visibility:hidden!important;min-height:0!important;height:auto!important;');
    s.style.position   = 'absolute';
    s.style.top        = '-9999px';
    s.style.left       = '0';
    s.style.visibility = 'hidden';
    s.style.minHeight  = '0';
    s.style.height     = 'auto';
    document.body.appendChild(s);
    return s;
}

function medirEnSonda(sonda, html) {
    sonda.innerHTML = html;
    return sonda.offsetHeight;
}

function paginar() {
    const sonda = crearSonda();

    // Ancho real de la hoja en px (lo da el navegador con su zoom actual)
    const anchoPx = sonda.offsetWidth;

    // Calculamos cuántos px equivale 1mm en este contexto real
    const mmPx = anchoPx / 190; // la hoja mide 190mm de ancho

    // Página carta: 279mm alto - 20mm márgenes @page - 8mm padding hoja = 251mm disponibles
    const ALTO_PAGINA_PX = 251 * mmPx;

    // Altura encabezado + footer
    const altoFijo = medirEnSonda(sonda, crearEncabezadoHTML(1, 1) + crearFooterHTML(0, true));

    // Espacio real para cuadros (5mm de seguridad)
    const espacio = ALTO_PAGINA_PX - altoFijo - (-50 * mmPx);

    // Altura de cada cuadro
    const alturas = PARTIDOS.map(p => {
        return medirEnSonda(sonda, crearCuadroHTML(p)) + (1 * mmPx); // +2mm margin-bottom
    });

    document.body.removeChild(sonda);

    // Distribuir en páginas
    const paginas = [];
    let pag = [], usado = 0;
    for (let i = 0; i < PARTIDOS.length; i++) {
        if (usado + alturas[i] > espacio && pag.length > 0) {
            paginas.push(pag); pag = []; usado = 0;
        }
        pag.push(i);
        usado += alturas[i];
    }
    if (pag.length) paginas.push(pag);
    return paginas;
}

function renderPaginas(paginas) {
    const contenedor = document.getElementById('contenedor-hojas');
    contenedor.innerHTML = '';
    let acumulado = 0;
    const total = paginas.length;

    paginas.forEach((indices, idx) => {
        const esFinal = idx === total - 1;
        const grupo   = indices.map(i => PARTIDOS[i]);
        acumulado    += grupo.reduce((s, p) => s + Number(p.tarifa), 0);

        const hoja = document.createElement('div');
        hoja.className = 'hoja';
        hoja.innerHTML =
            crearEncabezadoHTML(idx + 1, total) +
            `<div class="hoja-contenido">${grupo.map(crearCuadroHTML).join('')}</div>` +
            crearFooterHTML(acumulado, esFinal)
            ;
        contenedor.appendChild(hoja);
    });
}

function paginarYImprimir() {
    renderPaginas(paginar());
    setTimeout(() => window.print(), 250);
}

window.addEventListener('DOMContentLoaded', () => renderPaginas(paginar()));
</script>

<div id="contenedor-hojas"></div>


</body>
</html>
<?php
$stmt->close();
$stmtArbitro->close();
$stmtContador->close();
$conexion->close();
?>