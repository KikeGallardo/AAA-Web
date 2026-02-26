<?php
session_start();
require_once 'config.php';

$conexion = getDBConnection();

// Obtener lista de torneos
$torneos = $conexion->query("SELECT idTorneo, nombreTorneo FROM torneo ORDER BY nombreTorneo");

require_once "assets/header.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Cuentas de Cobro</title>
    <link rel="stylesheet" href="assets/css/subtitulos.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #f0f2f5;
            --surface:   #ffffff;
            --border:    #e2e6ea;
            --primary:   #1a56db;
            --primary-h: #1648c0;
            --danger:    #e02424;
            --success:   #057a55;
            --text:      #111928;
            --muted:     #6b7280;
            --accent:    #0096C7;
            --row-hover: #f0f7ff;
            --tag-bg:    #e8f0fe;
            --tag-text:  #1a56db;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .page-title {
            text-align: center;
            padding: 2rem 1rem 0.5rem;
        }
        .page-title h1 {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: .04em;
        }
        .page-title p {
            color: var(--muted);
            font-size: .9rem;
            margin-top: .3rem;
        }

        /* ── PANEL FILTROS ── */
        .filters-panel {
            max-width: 860px;
            margin: 1.5rem auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.8rem 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .filters-panel h2 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 1.4rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .filters-panel h2 i { font-size: 1.1rem; color: var(--accent); }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }
        .filter-group label {
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .filter-group input,
        .filter-group select {
            padding: .7rem .9rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .95rem;
            color: var(--text);
            background: var(--surface);
            transition: border-color .2s, box-shadow .2s;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
        }
        .filter-group select[multiple] {
            height: 130px;
            padding: .4rem;
        }
        .filter-group select[multiple] option {
            padding: .4rem .6rem;
            border-radius: 5px;
            margin-bottom: 2px;
        }
        .filter-group select[multiple] option:checked {
            background: var(--tag-bg);
            color: var(--tag-text);
            font-weight: 600;
        }
        .select-hint {
            font-size: .75rem;
            color: var(--muted);
        }
        .filter-full { grid-column: span 2; }

        .btn-buscar {
            width: 100%;
            margin-top: 1.2rem;
            padding: .85rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 2px 8px rgba(26,86,219,.25);
        }
        .btn-buscar:hover {
            background: var(--primary-h);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(26,86,219,.35);
        }
        .btn-buscar i { font-size: 1.2rem; }

        /* ── RESULTADOS ── */
        .results-panel {
            max-width: 860px;
            margin: 0 auto 3rem;
            display: none;
        }
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0 .8rem;
        }
        .results-header h3 { font-size: 1.05rem; font-weight: 600; }
        .results-header .badge {
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: .8rem;
            font-weight: 600;
            padding: .25rem .7rem;
            border-radius: 99px;
            font-family: 'DM Mono', monospace;
        }
        .dates-info {
            font-size: .85rem;
            color: var(--muted);
            margin-bottom: 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .6rem 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .dates-info i { font-size: 1rem; color: var(--accent); }

        /* ── TABLA ── */
        .arbitros-table {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
            border-collapse: collapse;
        }
        .arbitros-table thead tr {
            background: #f8f9fb;
            border-bottom: 2px solid var(--border);
        }
        .arbitros-table th {
            padding: .9rem 1.2rem;
            text-align: left;
            font-size: .78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em;
        }
        .arbitros-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .arbitros-table tbody tr:last-child { border-bottom: none; }
        .arbitros-table tbody tr:hover { background: var(--row-hover); }
        .arbitros-table td { padding: .9rem 1.2rem; font-size: .95rem; }

        .arbitro-name {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .arbitro-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            color: white;
            font-size: .78rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-family: 'DM Mono', monospace;
        }
        .partidos-count {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: .8rem;
            font-weight: 600;
            padding: .2rem .6rem;
            border-radius: 99px;
            font-family: 'DM Mono', monospace;
        }

        /* ── CONTADOR ── */
        .contador-wrap {
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .contador-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
            font-size: .85rem;
            font-weight: 700;
            padding: .3rem .8rem;
            border-radius: 99px;
            font-family: 'DM Mono', monospace;
            min-width: 70px;
            justify-content: center;
        }
        .btn-ajustar {
            background: none;
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            padding: .3rem .45rem;
            color: var(--muted);
            font-size: .85rem;
            line-height: 1;
            transition: all .15s;
        }
        .btn-ajustar:hover {
            background: var(--tag-bg);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-descargar {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .15s;
            box-shadow: 0 1px 4px rgba(26,86,219,.2);
        }
        .btn-descargar:hover {
            background: var(--primary-h);
            transform: translateY(-1px);
        }
        .btn-descargar i { font-size: 1rem; }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: .8rem;
            color: var(--border);
        }
        .loading-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--muted);
            display: none;
        }
        .spinner {
            width: 36px; height: 36px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .7s linear infinite;
            margin: 0 auto .8rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── MODAL AJUSTE ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white;
            border-radius: 14px;
            padding: 2rem;
            width: 380px;
            max-width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .modal-box h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: .4rem;
        }
        .modal-box p {
            font-size: .85rem;
            color: var(--muted);
            margin-bottom: 1.2rem;
        }
        .modal-box input[type=number] {
            width: 100%;
            padding: .75rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 1.1rem;
            font-family: 'DM Mono', monospace;
            text-align: center;
            margin-bottom: 1.2rem;
        }
        .modal-box input[type=number]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
        }
        .modal-btns {
            display: flex;
            gap: .75rem;
        }
        .modal-btns button {
            flex: 1;
            padding: .75rem;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .15s;
        }
        .modal-btns button:hover { opacity: .88; }
        .btn-confirmar { background: var(--primary); color: white; }
        .btn-cancelar-modal { background: #e5e7eb; color: #374151; }

        @media (max-width: 680px) {
            .filters-panel { margin: 1rem; padding: 1.2rem; }
            .filter-grid { grid-template-columns: 1fr; }
            .filter-full { grid-column: span 1; }
            .results-panel { margin: 0 1rem 2rem; }
        }
    </style>
</head>
<body>

<div class="page-title">
    <h1>Cuentas de Cobro</h1>
    <p>Selecciona un rango de fechas y los torneos para generar las cuentas de árbitros</p>
</div>

<!-- FILTROS -->
<div class="filters-panel">
    <h2><i class="material-icons">tune</i> Filtros de búsqueda</h2>
    <div class="filter-grid">
        <div class="filter-group">
            <label>Fecha inicio</label>
            <input type="date" id="fechaInicio" required>
        </div>
        <div class="filter-group">
            <label>Fecha fin</label>
            <input type="date" id="fechaFin" required>
        </div>
        <div class="filter-group filter-full">
            <label>Torneos</label>
            <select id="torneosSelect" multiple>
                <?php while ($t = $torneos->fetch_assoc()): ?>
                    <option value="<?= $t['idTorneo'] ?>">
                        <?= h($t['nombreTorneo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="select-hint">Mantén Ctrl (o Cmd) para seleccionar varios · Sin selección = todos los torneos</span>
        </div>
    </div>
    <button class="btn-buscar" onclick="buscarArbitros()">
        <i class="material-icons">search</i>
        Buscar árbitros programados
    </button>
</div>

<!-- RESULTADOS -->
<div class="results-panel" id="resultsPanel">
    <div class="loading-state" id="loadingState">
        <div class="spinner"></div>
        <p>Buscando árbitros…</p>
    </div>
    <div id="resultsContent"></div>
</div>

<!-- MODAL AJUSTE CONTADOR -->
<div class="modal-overlay" id="modalAjuste">
    <div class="modal-box">
        <h3>Ajustar contador</h3>
        <p id="modalAjusteDesc">Árbitro: —</p>
        <input type="number" id="modalAjusteValor" min="0" placeholder="Nuevo valor">
        <div class="modal-btns">
            <button class="btn-cancelar-modal" onclick="cerrarModalAjuste()">Cancelar</button>
            <button class="btn-confirmar"      onclick="confirmarAjuste()">Guardar</button>
        </div>
    </div>
</div>

<script>
// Fechas por defecto: primer día del mes → hoy
(function () {
    const hoy  = new Date();
    const ini  = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const fmt  = d => d.toISOString().split('T')[0];
    document.getElementById('fechaInicio').value = fmt(ini);
    document.getElementById('fechaFin').value    = fmt(hoy);
})();

// Mapa idArbitro → { nombre, total } para actualizaciones en vivo
const estado = {};

// ── Buscar árbitros ───────────────────────────────────────────
async function buscarArbitros() {
    const fi = document.getElementById('fechaInicio').value;
    const ff = document.getElementById('fechaFin').value;

    if (!fi || !ff) { alert('Por favor selecciona un rango de fechas'); return; }
    if (ff < fi)    { alert('La fecha fin no puede ser anterior a la fecha inicio'); return; }

    const sel     = document.getElementById('torneosSelect');
    const torneos = Array.from(sel.selectedOptions).map(o => o.value);

    const panel   = document.getElementById('resultsPanel');
    const loading = document.getElementById('loadingState');
    const content = document.getElementById('resultsContent');

    panel.style.display   = 'block';
    loading.style.display = 'block';
    content.innerHTML     = '';

    const fd = new FormData();
    fd.append('accion', 'buscar_arbitros_programados');
    fd.append('fechaInicio', fi);
    fd.append('fechaFin', ff);
    torneos.forEach(id => fd.append('torneos[]', id));

    try {
        const res  = await fetch('consultas.php', { method: 'POST', body: fd });
        const data = await res.json();

        // Cargar contadores de todos los árbitros en paralelo
        if (data.arbitros && data.arbitros.length) {
            await Promise.all(data.arbitros.map(a => cargarContador(a.idArbitro)));
        }

        loading.style.display = 'none';
        renderResultados(data, fi, ff);
    } catch (e) {
        loading.style.display = 'none';
        content.innerHTML = `<div class="empty-state">
            <i class="material-icons">error_outline</i>
            <p>Error de conexión. Intenta de nuevo.</p>
        </div>`;
    }
}

// ── Cargar contador individual ────────────────────────────────
async function cargarContador(idArbitro) {
    const fd = new FormData();
    fd.append('accion', 'obtener_contador');
    fd.append('idArbitro', idArbitro);
    const res  = await fetch('consultas.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!estado[idArbitro]) estado[idArbitro] = {};
    estado[idArbitro].total = data.total ?? 0;
}

// ── Renderizar tabla de resultados ────────────────────────────
function renderResultados(data, fi, ff) {
    const content = document.getElementById('resultsContent');

    if (!data.arbitros || !data.arbitros.length) {
        content.innerHTML = `<div class="empty-state">
            <i class="material-icons">person_search</i>
            <p>No se encontraron árbitros programados en ese rango de fechas y torneos.</p>
        </div>`;
        return;
    }

    const fmtDate = s => { const [y,m,d] = s.split('-'); return `${d}/${m}/${y}`; };

    const sel         = document.getElementById('torneosSelect');
    const torneosIds  = Array.from(sel.selectedOptions).map(o => o.value);

    let html = `
        <div class="results-header">
            <h3>Árbitros programados</h3>
            <span class="badge">${data.arbitros.length} árbitro${data.arbitros.length !== 1 ? 's' : ''}</span>
        </div>
        <div class="dates-info">
            <i class="material-icons">date_range</i>
            Del <strong>${fmtDate(fi)}</strong> al <strong>${fmtDate(ff)}</strong>
            ${data.torneos_nombre ? ' · ' + data.torneos_nombre : ''}
        </div>
        <table class="arbitros-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>Partidos</th>
                    <th>Impresiones</th>
                    <th style="text-align:center;">Cuenta de cobro</th>
                </tr>
            </thead>
            <tbody>`;

    data.arbitros.forEach(a => {
        const initials = (a.nombre.charAt(0) + a.apellido.charAt(0)).toUpperCase();
        const total    = estado[a.idArbitro]?.total ?? 0;

        // Guardar nombre para el modal
        estado[a.idArbitro] = { ...estado[a.idArbitro], nombre: `${a.nombre} ${a.apellido}` };

        const params = new URLSearchParams({ fechaInicio: fi, fechaFin: ff, arbitro: a.idArbitro });
        torneosIds.forEach(id => params.append('torneos[]', id));
        const url = `generar_cuentas_cobro_html.php?${params.toString()}`;

        html += `
            <tr>
                <td>
                    <div class="arbitro-name">
                        <div class="arbitro-avatar">${initials}</div>
                        <div>
                            <div>${a.nombre} ${a.apellido}</div>
                            <div style="font-size:.78rem;color:var(--muted);font-weight:400;">CC ${a.cedula}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="partidos-count">
                        <i class="material-icons" style="font-size:.9rem;">sports_soccer</i>
                        ${a.totalPartidos}
                    </span>
                </td>
                <td>
                    <div class="contador-wrap">
                        <span class="contador-badge" id="ctr_${a.idArbitro}">
                            🖨️ ${total}
                        </span>
                        <button class="btn-ajustar"
                                onclick="abrirModalAjuste(${a.idArbitro})"
                                title="Ajustar contador">
                            ✏️
                        </button>
                    </div>
                </td>
                <td style="text-align:center;">
                    <a href="${url}" target="_blank" class="btn-descargar"
                       onclick="onDescargar(event, ${a.idArbitro}, '${fi}', '${ff}', '${url}')">
                        <i class="material-icons">print</i>
                        Descargar programación
                    </a>
                </td>
            </tr>`;
    });

    html += `</tbody></table>`;
    content.innerHTML = html;
}

// ── Al hacer clic en Descargar: abrir en nueva pestaña
//    El incremento lo hace generar_cuentas_cobro_html.php al cargarse,
//    pero aquí actualizamos el badge en la misma página también.
function onDescargar(e, idArbitro, fi, ff, url) {
    // Dejamos que el href abra la pestaña normalmente.
    // Después de un pequeño delay recargamos el contador en este panel.
    setTimeout(() => actualizarBadge(idArbitro), 1500);
    fd = new FormData();
    fd.append('accion', 'ajustar_contador');
    fd.append('idArbitro', idArbitro);
    fd.append('nuevo', (estado[idArbitro]?.total ?? 0) + 1);
    fetch('consultas.php', { method: 'POST', body: fd });

}

async function actualizarBadge(idArbitro) {
    await cargarContador(idArbitro);
    const el = document.getElementById('ctr_' + idArbitro);
    if (el) el.textContent = '🖨️ ' + (estado[idArbitro]?.total ?? 0);
}

// ── Modal de ajuste ───────────────────────────────────────────
let _ajusteIdArbitro = null;

function abrirModalAjuste(idArbitro) {
    _ajusteIdArbitro = idArbitro;
    const nombre = estado[idArbitro]?.nombre ?? `Árbitro #${idArbitro}`;
    const total  = estado[idArbitro]?.total  ?? 0;

    document.getElementById('modalAjusteDesc').textContent  = `${nombre} — actual: ${total}`;
    document.getElementById('modalAjusteValor').value       = total;
    document.getElementById('modalAjuste').classList.add('show');
    document.getElementById('modalAjusteValor').focus();
}

function cerrarModalAjuste() {
    document.getElementById('modalAjuste').classList.remove('show');
    _ajusteIdArbitro = null;
}

async function confirmarAjuste() {
    const nuevo = parseInt(document.getElementById('modalAjusteValor').value, 10);
    if (isNaN(nuevo) || nuevo < 0) {
        alert('Ingresa un valor válido (0 o mayor).');
        return;
    }

    const fd = new FormData();
    fd.append('accion',    'ajustar_contador');
    fd.append('idArbitro', _ajusteIdArbitro);
    fd.append('nuevo',     nuevo);

    const res  = await fetch('consultas.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'ok') {
        estado[_ajusteIdArbitro].total = nuevo;
        const el = document.getElementById('ctr_' + _ajusteIdArbitro);
        if (el) el.textContent = '🖨️ ' + nuevo;
        cerrarModalAjuste();
    } else {
        alert('Error al ajustar: ' + (data.msg ?? 'desconocido'));
    }
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalAjuste').addEventListener('click', function (e) {
    if (e.target === this) cerrarModalAjuste();
});
</script>

</body>
</html>
<?php
$conexion->close();
require_once "assets/footer.php";
?>