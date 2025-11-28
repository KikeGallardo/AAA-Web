<?php
header("Content-Type: application/json");
include "basedatos.php";

$accion = $_POST['accion'] ?? '';

switch ($accion) {

  case "filtrar_fechas":
    $inicio = $_POST['inicio'] ?? '';
    $fin = $_POST['fin'] ?? '';

    $stmt = $conn->prepare("
      SELECT 
      p.idPartido,
      p.fecha,
      p.hora,
      p.idEquipo1,
      p.idEquipo2,
      p.canchaLugar,
      p.categoriaText,

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
      cp.pagoArbitro4 AS pagoCuarto,

      t.nombreTorneo AS torneo

      FROM partido p
      INNER JOIN equipo e1 ON p.idEquipo1 = e1.idEquipo
      INNER JOIN equipo e2 ON p.idEquipo2 = e2.idEquipo
      INNER JOIN categoriaPagoArbitro cp 
            ON cp.idCategoriaPagoArbitro = p.idCategoriaPagoArbitro

      LEFT JOIN arbitro a1 ON p.idArbitro1 = a1.idArbitro
      LEFT JOIN arbitro a2 ON p.idArbitro2 = a2.idArbitro
      LEFT JOIN arbitro a3 ON p.idArbitro3 = a3.idArbitro
      LEFT JOIN arbitro a4 ON p.idArbitro4 = a4.idArbitro

      INNER JOIN torneo t
            ON t.idTorneo = p.idTorneoPartido

      WHERE p.fecha BETWEEN ? AND ?;
    ");

    $stmt->bind_param("ss", $inicio, $fin);
    $stmt->execute();
    $result = $stmt->get_result();

    $eventos = [];

    while ($row = $result->fetch_assoc()) {

      $start = $row["fecha"] . "T" . $row["hora"];
      $titulo = $row["torneo"];

      $eventos[] = [
        "id" => $row["idPartido"],
        "title" => $titulo,
        "start" => $start,
        "extendedProps" => [
          "cancha" => $row["canchaLugar"],
          "categoria" => $row["categoriaText"],
          "mes" => (new DateTime($row["fecha"]))->format("m")
        ]
      ];
    }

    echo json_encode($eventos);
    break;

  case "obtener_partido":
    $id = $_POST['idPartido'];

    $stmt = $conn->prepare("
        SELECT 
            p.idPartido,
            e1.nombreEquipo AS equipoLocal,
            e2.nombreEquipo AS equipoVisitante,
            p.categoriaText AS categoria,

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
              ON cp.idCategoriaPagoArbitro = p.idCategoriaPagoArbitro
        LEFT JOIN arbitro a1 ON p.idArbitro1 = a1.idArbitro
        LEFT JOIN arbitro a2 ON p.idArbitro2 = a2.idArbitro
        LEFT JOIN arbitro a3 ON p.idArbitro3 = a3.idArbitro
        LEFT JOIN arbitro a4 ON p.idArbitro4 = a4.idArbitro

        WHERE p.idPartido = ?
    ");
        
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode($result);
    break;

  case "obtener_partidos_por_dia":
    $fecha = $_POST['fecha'];

    $stmt = $conn->prepare("
      SELECT 
        p.idPartido,
        p.fecha,
        p.hora,
        p.canchaLugar,
        p.categoriaText,

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
              ON cp.idCategoriaPagoArbitro = p.idCategoriaPagoArbitro
        LEFT JOIN arbitro a1 ON p.idArbitro1 = a1.idArbitro
        LEFT JOIN arbitro a2 ON p.idArbitro2 = a2.idArbitro
        LEFT JOIN arbitro a3 ON p.idArbitro3 = a3.idArbitro
        LEFT JOIN arbitro a4 ON p.idArbitro4 = a4.idArbitro
        WHERE p.fecha = ?
      ");

      $stmt->bind_param("s", $fecha);
      $stmt->execute();

      $result = $stmt->get_result();
      $partidos = [];

      while ($row = $result->fetch_assoc()) {
          $partidos[] = $row;
      }

      echo json_encode($partidos);
      break;

  case "obtener_partidos_por_dia":
    $dia = $_POST['dia'] ?? '';

    $stmt = $conn->prepare("
      SELECT 
      p.idPartido,
      p.fecha,
      p.hora,
      p.idEquipo1,
      p.idEquipo2,
      p.canchaLugar,
      p.categoriaText,

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

      WHERE p.fecha BETWEEN ? AND ?
    ");
      
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result()->fetch_assoc();

      echo json_encode($result);
      break;

  case 'buscar_arbitro':
    $q = isset($_POST['q']) ? trim($_POST['q']) : '';
    $resultados = [];

    try {
        if ($q !== '') {
            $stmt = $conn->prepare("SELECT idArbitro, nombre, apellido, cedula, fechaNacimiento, correo, telefono, categoriaArbitro 
                                  FROM arbitro 
                                  WHERE nombre LIKE ? 
                                      OR apellido LIKE ? 
                                      OR cedula LIKE ? 
                                      OR correo LIKE ? 
                                      OR telefono LIKE ? 
                                      OR categoriaArbitro LIKE ?");
            $like = "%$q%";
            $stmt->bind_param('ssssss', $like, $like, $like, $like, $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                $resultados[] = $row;
            }
        }
        echo json_encode($resultados);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    break;

    case 'eliminar_noti':
    try {
        if (!isset($_POST['idNotificacion'])) {
            echo json_encode(["status" => "error", "msg" => "ID no recibido"]);
            exit;
        }

        $id = intval($_POST['idNotificacion']);

        $stmt = $conn->prepare("DELETE FROM notificaciones WHERE idNotificacion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["status" => "ok"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    }
    break;

    case 'eliminar_todas':

      header("Content-Type: application/json");

      try {
          $conn->query("DELETE FROM notificaciones");

          echo json_encode(["status" => "ok"]);
      }
      catch (Exception $e) {
          echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
      }
      exit;

}
?>