<?php require_once "assets/header.php"; ?>
<?php require_once "assets/footer.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendario de Partidos</title>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
  <link  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link  rel="stylesheet" href="assets/css/calendario.css">
  <link  rel="stylesheet" href="assets/css/subtitulos.css">
  <style>
    /* ── Chips del calendario ── */
    .fc-event-custom      { display:flex; flex-direction:column; line-height:1.25; padding:1px 3px; }
    .fc-ev-hora           { font-size:10px; opacity:.85; font-weight:600; }
    .fc-ev-nombre         { font-size:11px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .fc-ev-count          { font-size:10px; background:rgba(255,255,255,.25); border-radius:8px;
                            padding:0 5px; align-self:flex-start; margin-top:1px; }

    /* ── Overlay modal ── */
    #miModal {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.6); z-index:9999;
      align-items:flex-start; justify-content:center;
      padding:20px 10px; overflow-y:auto;
    }
    .modal-content {
      background:#fff; border-radius:12px; width:100%; max-width:1150px;
      box-shadow:0 24px 64px rgba(0,0,0,.35); overflow:hidden;
      animation: modalIn .2s ease;
    }
    @keyframes modalIn { from{transform:translateY(-18px);opacity:0} to{transform:translateY(0);opacity:1} }

    .modal-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 20px; background:#1e293b; color:#fff;
    }
    .modal-header h2 { margin:0; font-size:15px; font-weight:600; }
    #cerrarModal {
      background:none; border:none; color:#fff; font-size:22px;
      cursor:pointer; padding:0 4px; opacity:.75; line-height:1;
    }
    #cerrarModal:hover { opacity:1; }
    #cuerpoModal { padding:14px 18px; max-height:78vh; overflow-y:auto; }

    /* ── Tabla partidos ── */
    .tabla-partidos th,
    .tabla-partidos td  { font-size:12px; vertical-align:middle; white-space:nowrap; }
    .acciones-td        { white-space:nowrap; }
    .btn-acc            { border:none; background:none; cursor:pointer;
                          font-size:15px; padding:2px 4px; border-radius:4px; transition:background .15s; }
    .btn-acc:hover      { background:#f1f5f9; }
    .btn-obs-activa     { filter:none; }

    /* ── Fila de observación ── */
    .fila-obs           { background:#fefce8; }
    .obs-texto          { padding:6px 12px !important; color:#713f12; font-size:12px; }
    .obs-badge          { font-weight:700; margin-right:4px; }

    /* ── Editores inline ── */
    .obs-editor-td,
    .edit-editor-td     { background:#f8fafc; padding:12px 16px !important; border-top:2px solid #2563eb; }

    .edit-section-title { font-size:11px; font-weight:700; color:#64748b;
                          text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }

    .edit-grid          { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
    .edit-label         { display:flex; flex-direction:column; font-size:11px;
                          font-weight:600; color:#374151; gap:3px; min-width:160px; flex:1; }
    .edit-input         { border:1px solid #cbd5e1; border-radius:6px;
                          padding:6px 10px; font-size:12px; background:#fff; }
    .edit-input:focus   { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.15); }
    .edit-select        { cursor:pointer; }

    .obs-textarea       { width:100%; border:1px solid #cbd5e1; border-radius:6px;
                          padding:8px; font-size:12px; resize:vertical; }
    .obs-textarea:focus { outline:none; border-color:#2563eb; }

    .edit-actions       { display:flex; gap:8px; margin-top:10px; }
    .btn-save           { background:#2563eb; color:#fff; border:none; border-radius:6px;
                          padding:6px 16px; cursor:pointer; font-size:12px; font-weight:600; }
    .btn-save:hover     { background:#1d4ed8; }
    .btn-cancel         { background:#e2e8f0; color:#475569; border:none; border-radius:6px;
                          padding:6px 14px; cursor:pointer; font-size:12px; }
    .btn-cancel:hover   { background:#cbd5e1; }
  </style>
</head>
<body>
  <div class="subtitulo"><h1>CALENDARIO DE PARTIDOS</h1></div>
  <main>
    <div id="calendar" class="calendar-container"></div>
  </main>

  <!-- MODAL -->
  <div id="miModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Partidos</h2>
        <button id="cerrarModal" title="Cerrar">✕</button>
      </div>
      <div id="cuerpoModal"></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/calendario.js"></script>
</body>
</html>