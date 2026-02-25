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

        /* ── TÍTULO ── */
        .page-title {
            text-align: center;
            padding: 2rem 1rem 0.5rem;
        }
        .page-title h1 {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: .04em;
            color: var(--text);
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
            appearance: none;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
        }

        /* Multi-select torneos */
        .filter-group select[multiple] {
            height: 130px;
            padding: .4rem;
        }
        .filter-group select[multiple] option {
            padding: .4rem .6rem;
            border-radius: 5px;
            margin-bottom: 2px;
            cursor: pointer;
        }
        .filter-group select[multiple] option:checked {
            background: var(--tag-bg);
            color: var(--tag-text);
            font-weight: 600;
        }

        .select-hint {
            font-size: .75rem;
            color: var(--muted);
            margin-top: -.2rem;
        }

        .filter-full { grid-column: span 2; }

        /* ── BOTÓN BUSCAR ── */
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
        .btn-buscar:active { transform: translateY(0); }
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
        .results-header h3 {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text);
        }
        .results-header .badge {
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: .8rem;
            font-weight: 600;
            padding: .25rem .7rem;
            border-radius: 99px;
            font-family: 'DM Mono', monospace;
        }

        /* Fechas info */
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

        /* ── TABLA ÁRBITROS ── */
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
        .arbitros-table th:last-child { text-align: center; }

        .arbitros-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .arbitros-table tbody tr:last-child { border-bottom: none; }
        .arbitros-table tbody tr:hover { background: var(--row-hover); }

        .arbitros-table td {
            padding: .9rem 1.2rem;
            font-size: .95rem;
        }

        .arbitro-name {
            font-weight: 600;
            color: var(--text);
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

        /* ── BOTÓN DESCARGAR ── */
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
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 1px 4px rgba(26,86,219,.2);
        }
        .btn-descargar:hover {
            background: var(--primary-h);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(26,86,219,.3);
        }
        .btn-descargar i { font-size: 1rem; }

        /* ── EMPTY STATE ── */
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
        .empty-state p { font-size: .95rem; }

        /* ── LOADING ── */
        .loading-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--muted);
            display: none;
        }
        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .7s linear infinite;
            margin: 0 auto .8rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── TORNEOS TAGS ── */
        .torneo-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .5rem;
        }
        .torneo-tag {
            font-size: .72rem;
            font-weight: 500;
            background: #f0f7ff;
            color: var(--accent);
            border: 1px solid #c3dafe;
            padding: .15rem .55rem;
            border-radius: 99px;
        }

        /* ── RESPONSIVE ── */
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

<!-- PANEL DE FILTROS -->
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

<!-- PANEL DE RESULTADOS -->
<div class="results-panel" id="resultsPanel">

    <div class="loading-state" id="loadingState">
        <div class="spinner"></div>
        <p>Buscando árbitros…</p>
    </div>

    <div id="resultsContent"></div>
</div>

<script>
// Establecer fecha por defecto: inicio de mes actual → hoy
(function() {
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const fmt = d => d.toISOString().split('T')[0];
    document.getElementById('fechaInicio').value = fmt(primerDia);
    document.getElementById('fechaFin').value    = fmt(hoy);
})();

function buscarArbitros() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin    = document.getElementById('fechaFin').value;

    if (!fechaInicio || !fechaFin) {
        alert('Por favor selecciona un rango de fechas');
        return;
    }
    if (fechaFin < fechaInicio) {
        alert('La fecha fin no puede ser anterior a la fecha inicio');
        return;
    }

    // Torneos seleccionados
    const sel = document.getElementById('torneosSelect');
    const torneos = Array.from(sel.selectedOptions).map(o => o.value);

    const panel   = document.getElementById('resultsPanel');
    const loading = document.getElementById('loadingState');
    const content = document.getElementById('resultsContent');

    panel.style.display   = 'block';
    loading.style.display = 'block';
    content.innerHTML     = '';

    const formData = new FormData();
    formData.append('accion', 'buscar_arbitros_programados');
    formData.append('fechaInicio', fechaInicio);
    formData.append('fechaFin', fechaFin);
    torneos.forEach(id => formData.append('torneos[]', id));

    fetch('consultas.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            renderResultados(data, fechaInicio, fechaFin);
        })
        .catch(() => {
            loading.style.display = 'none';
            content.innerHTML = `<div class="empty-state">
                <i class="material-icons">error_outline</i>
                <p>Error de conexión. Intenta de nuevo.</p>
            </div>`;
        });
}

function renderResultados(data, fechaInicio, fechaFin) {
    const content = document.getElementById('resultsContent');

    if (!data.arbitros || data.arbitros.length === 0) {
        content.innerHTML = `<div class="empty-state">
            <i class="material-icons">person_search</i>
            <p>No se encontraron árbitros programados en ese rango de fechas y torneos.</p>
        </div>`;
        return;
    }

    // Fechas formateadas para mostrar
    const fmtDate = s => {
        const [y,m,d] = s.split('-');
        return `${d}/${m}/${y}`;
    };

    // Torneos seleccionados (para pasar al generador)
    const sel = document.getElementById('torneosSelect');
    const torneosSelIds = Array.from(sel.selectedOptions).map(o => o.value);

    let html = `
        <div class="results-header">
            <h3>Árbitros programados</h3>
            <span class="badge">${data.arbitros.length} árbitro${data.arbitros.length !== 1 ? 's' : ''}</span>
        </div>
        <div class="dates-info">
            <i class="material-icons">date_range</i>
            Del <strong>${fmtDate(fechaInicio)}</strong> al <strong>${fmtDate(fechaFin)}</strong>
            ${data.torneos_nombre ? ' · ' + data.torneos_nombre : ''}
        </div>
        <table class="arbitros-table">
            <thead>
                <tr>
                    <th>Árbitro</th>
                    <th>Partidos</th>
                    <th style="text-align:center;">Cuenta de cobro</th>
                </tr>
            </thead>
            <tbody>
    `;

    data.arbitros.forEach(a => {
        const initials = (a.nombre.charAt(0) + a.apellido.charAt(0)).toUpperCase();

        // Construir URL del generador
        const params = new URLSearchParams({
            fechaInicio,
            fechaFin,
            arbitro: a.idArbitro
        });
        torneosSelIds.forEach(id => params.append('torneos[]', id));
        const url = `generar_cuentas_cobro_html.php?${params.toString()}`;

        html += `
            <tr>
                <td>
                    <div class="arbitro-name">
                        <div class="arbitro-avatar">${initials}</div>
                        <div>
                            <div>${a.nombre} ${a.apellido}</div>
                            <div style="font-size:.78rem; color:var(--muted); font-weight:400;">CC ${a.cedula}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="partidos-count">
                        <i class="material-icons" style="font-size:.9rem;">sports_soccer</i>
                        ${a.totalPartidos}
                    </span>
                </td>
                <td style="text-align:center;">
                    <a href="${url}" target="_blank" class="btn-descargar">
                        <i class="material-icons">print</i>
                        Descargar programación
                    </a>
                </td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    content.innerHTML = html;
}
</script>

</body>
</html>
<?php
$conexion->close();
require_once "assets/footer.php";
?>