<?php require_once "assets/header.php"; ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario programaciones</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.19/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/calendario.css">
    <link rel="stylesheet" href="assets/css/subtitulos.css">
</head>
<body>
  <div class="subtitulo"><h1>CALENDARIO DE PARTIDOS</h1></div>
      <main>
        <div id='calendar' class=calendar-container></div>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.19/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/calendario.js"></script>
        <script src='fullcalendar/lang/es.js'></script>
        
      </div>
        <div id="miModal" class="modal" style="display:none;">
          <div class="modal-content">
            <span id="cerrarModal" style="cursor:pointer;">X</span>
            <h2 id="modalTitle">Información del partido</h2>
            <table class="table">
              <tbody id="cuerpoTabla"></tbody>
            </table>
          </div>
        </div>

        </div>
      </body>
    </html>

