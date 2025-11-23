<?php
header("Content-Type: application/json");
include "basedatos.php";

$accion = $_POST['accion'] ?? '';

switch ($accion) {

  case "filtrar_fechas":
    $inicio = $_POST['inicio'] ?? '';
    $fin = $_POST['fin'] ?? '';

    // Usamos fecha entre el rango
    $stmt = $conn->prepare("
      SELECT 
        p.idPartido,
        e1.nombreEquipo AS equipoLocal,
        e2.nombreEquipo AS equipoVisitante,
        cp.nombreCategoria AS categoriaPago,

        a1.nombre AS arbitroPrincipal,
        cp.pagoArbitro1 AS pagoPrincipal,

        a2.nombre AS arbitroAsistente1,
        cp.pagoArbitro2 AS pagoAsistente1,

        a3.nombre AS arbitroAsistente2,
        cp.pagoArbitro3 AS pagoAsistente2,

        a4.nombre AS arbitroCuarto,
        cp.pagoArbitro4 AS pagoCuarto

        FROM partido p
        INNER JOIN equipo e1 ON p.idEquipo1 = e1.idEquipo
        INNER JOIN equipo e2 ON p.idEquipo2 = e2.idEquipo

        INNER JOIN categoriaPagoArbitro cp 
            ON cp.nombreCategoria = p.categoriaText

        LEFT JOIN arbitro a1 ON p.idArbitro1 = a1.idArbitro
        LEFT JOIN arbitro a2 ON p.idArbitro2 = a2.idArbitro
        LEFT JOIN arbitro a3 ON p.idArbitro3 = a3.idArbitro
        LEFT JOIN arbitro a4 ON p.idArbitro4 = a4.idArbitro
        WHERE fecha BETWEEN ? AND ?;
    ");

    $stmt->bind_param("ss", $inicio, $fin);
    $stmt->execute();

    $result = $stmt->get_result();

    $eventos = [];

    while ($row = $result->fetch_assoc()) {

      // Construir fecha completa (FullCalendar quiere DATETIME en ISO)
      $start = $row["fecha"] . "T" . $row["hora"];

      // Título del evento (puedes personalizarlo)
      $titulo = "Partido " . $row["idEquipo1"] . " vs " . $row["idEquipo2"];

      $eventos[] = [
        "id" => $row["idPartido"],
        "title" => $titulo,
        "start" => $start,
        "extendedProps" => [
          "cancha" => $row["canchaLugar"],
          "categoria" => $row["categoriaText"]
        ]
      ];
    }

    echo json_encode($eventos);
    break;

  default:
    echo json_encode([]);
}
?>
