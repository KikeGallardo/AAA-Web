<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario programaciones</title>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.19/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/calendario.css">
</head>
<body>
  <header>
          <nav class="nav_bar_upper">
              <ul class="nav_links">
                  <li><a href="dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></a></li>
              </ul>
              <ul class="nav_links">
                  <li><a href="programar.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">  <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg></a></li>
              </ul>
              <ul class="nav_links">
                  <li><a href="torneo.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" /></svg></a></li>
              </ul>
              <ul class="nav_links">
                  <li><a href="arbitros.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg></a></li>
              </ul>
          </nav>
      </header>
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
            <h2 id="modalTitle">Información</h2>
            <table border="1" class="cuerpoTabla">
              <tbody id="cuerpoTabla">
                <?php
                  $conexion = new mysqli("db-fde-02.apollopanel.com:3306", "u136076_tCDay64NMd", "AzlYnjAiSFN!d=ZtajgQa=q.", "s136076_Aribatraje");
                  if ($conexion->connect_error) {
                  die("Error de conexión: " . $conexion->connect_error);
                  }

                  // ------------------------------------
                  // PAGINACIÓN
                  // ------------------------------------
                  $registrosPorPagina = 10;
                  $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                  $pagina = max($pagina, 1);
                  $offset = ($pagina - 1) * $registrosPorPagina;

                  // ----------------------
                  // 5. CONSULTAR ÁRBITROS (con búsqueda y límite)
                  // ----------------------
                  $busqueda = "";
                  $where = "";

                  $where = "WHERE nombre LIKE '%$busqueda%' 
                  OR apellido LIKE '%$busqueda%'
                  OR cedula LIKE '%$busqueda%'
                  OR fechaNacimiento LIKE '%$busqueda%'
                  OR correo LIKE '%$busqueda%'
                  OR telefono LIKE '%$busqueda%'
                  OR categoriaArbitro LIKE '%$busqueda%'";
                  

                  $arbitros = $conexion->query("
                  SELECT * FROM arbitro 
                  $where 
                  ORDER BY idArbitro DESC 
                  LIMIT $registrosPorPagina OFFSET $offset
                  ");
                while ($row = $arbitros->fetch_assoc()) { ?>
                <tr>
                  <td><?= $row['nombre'] ?></td>
                  <td><?= $row['apellido'] ?></td>
                  <td><?= $row['cedula'] ?></td>
                  <td><?= $row['fechaNacimiento'] ?></td>
                  <td><?= $row['correo'] ?></td>
                  <td><?= $row['telefono'] ?></td>
                  <td><?= $row['categoriaArbitro'] ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
            <p id="anoModal"></p>
            <h3 id="mesModal"></h3>
          </div>
        </div>
      </body>
    </html>

