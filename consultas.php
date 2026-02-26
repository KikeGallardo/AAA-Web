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
      t.idTorneo,

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
        "id"    => $row["idPartido"],
        "title" => $titulo,
        "start" => $start,
        "extendedProps" => [
            "cancha"    => $row["canchaLugar"],
            "categoria" => $row["categoriaText"],
            "mes"       => (new DateTime($row["fecha"]))->format("m"),
            "idTorneo"  => $row["idTorneo"]   // ← AGREGAR ESTO
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
        if (!isset($_POST['id'])) {
            echo json_encode(["status" => "error", "msg" => "ID no recibido"]);
            exit;
        }

        $id = intval($_POST['id']);

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
    break;
    
    case "obtener_partidos_por_torneo_dia":
      $fecha    = $_POST['fecha']    ?? '';
      $idTorneo = isset($_POST['idTorneo']) ? (int)$_POST['idTorneo'] : 0;

      $sql = "
          SELECT 
              p.idPartido, p.fecha, p.hora, p.canchaLugar, p.categoriaText,
              e1.nombreEquipo AS equipoLocal,
              e2.nombreEquipo AS equipoVisitante,
              cp.nombreCategoria AS categoriaPago,
              a1.nombre AS arbitroPrincipal, cp.pagoArbitro1 AS pagoPrincipal,
              a2.nombre AS arbitroAsistente1, cp.pagoArbitro2 AS pagoAsistente1,
              a3.nombre AS arbitroAsistente2, cp.pagoArbitro3 AS pagoAsistente2,
              a4.nombre AS arbitroCuarto,    cp.pagoArbitro4 AS pagoCuarto
          FROM partido p
          INNER JOIN equipo e1 ON p.idEquipo1 = e1.idEquipo
          INNER JOIN equipo e2 ON p.idEquipo2 = e2.idEquipo
          INNER JOIN categoriaPagoArbitro cp ON cp.idCategoriaPagoArbitro = p.idCategoriaPagoArbitro
          LEFT JOIN  arbitro a1 ON p.idArbitro1 = a1.idArbitro
          LEFT JOIN  arbitro a2 ON p.idArbitro2 = a2.idArbitro
          LEFT JOIN  arbitro a3 ON p.idArbitro3 = a3.idArbitro
          LEFT JOIN  arbitro a4 ON p.idArbitro4 = a4.idArbitro
          WHERE p.fecha = ?
      ";

      $params = [$fecha];
      $types  = "s";

      if ($idTorneo > 0) {
          $sql   .= " AND p.idTorneoPartido = ?";
          $params[] = $idTorneo;
          $types .= "i";
      }

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result  = $stmt->get_result();
      $partidos = [];
      while ($row = $result->fetch_assoc()) {
          $partidos[] = $row;
      }
      echo json_encode($partidos);
      break;

    case 'eliminar_partido':
    $idPartido = isset($_POST['idPartido']) ? (int)$_POST['idPartido'] : 0;

    if ($idPartido <= 0) {
        echo json_encode(["status" => "error", "msg" => "ID inválido"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM partido WHERE idPartido = ?");
    $stmt->bind_param("i", $idPartido);

    if ($stmt->execute()) {
        echo json_encode(["status" => "ok", "eliminados" => $stmt->affected_rows]);
    } else {
        echo json_encode(["status" => "error", "msg" => $stmt->error]);
    }
    $stmt->close();
    break;

    case 'buscar_arbitros_programados':
    $fechaInicio = $_POST['fechaInicio'] ?? '';
    $fechaFin    = $_POST['fechaFin']    ?? '';
    $torneos     = $_POST['torneos']     ?? []; // array de idTorneo

    if (empty($fechaInicio) || empty($fechaFin)) {
        echo json_encode(['arbitros' => [], 'torneos_nombre' => '']);
        exit;
    }

    // Construir filtro de torneos
    $whereTorneo = '';
    $extraTypes  = '';
    $extraValues = [];

    if (!empty($torneos)) {
        $placeholders = implode(',', array_fill(0, count($torneos), '?'));
        $whereTorneo  = "AND p.idTorneoPartido IN ($placeholders)";
        $extraTypes   = str_repeat('i', count($torneos));
        $extraValues  = array_map('intval', $torneos);
    }

    // Query: árbitros distintos que participan en partidos del rango+torneos
    $sql = "
        SELECT
            a.idArbitro,
            a.nombre,
            a.apellido,
            a.cedula,
            COUNT(DISTINCT p.idPartido) AS totalPartidos
        FROM arbitro a
        INNER JOIN (
            SELECT idPartido, idArbitro1 AS idArbitro, idTorneoPartido, fecha FROM partido
            UNION ALL
            SELECT idPartido, idArbitro2, idTorneoPartido, fecha FROM partido WHERE idArbitro2 IS NOT NULL
            UNION ALL
            SELECT idPartido, idArbitro3, idTorneoPartido, fecha FROM partido WHERE idArbitro3 IS NOT NULL
            UNION ALL
            SELECT idPartido, idArbitro4, idTorneoPartido, fecha FROM partido WHERE idArbitro4 IS NOT NULL
        ) p ON p.idArbitro = a.idArbitro
        WHERE p.fecha BETWEEN ? AND ?
        $whereTorneo
        GROUP BY a.idArbitro, a.nombre, a.apellido, a.cedula
        ORDER BY a.apellido, a.nombre
    ";

    $types  = 'ss' . $extraTypes;
    $params = array_merge([$fechaInicio, $fechaFin], $extraValues);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $arbitros = [];
    while ($row = $result->fetch_assoc()) {
        $arbitros[] = $row;
    }
    $stmt->close();

    // Nombres de torneos seleccionados (para mostrar en UI)
    $torneos_nombre = '';
    if (!empty($torneos)) {
        $phs   = implode(',', array_fill(0, count($torneos), '?'));
        $stmtT = $conn->prepare("SELECT nombreTorneo FROM torneo WHERE idTorneo IN ($phs)");
        $typesT = str_repeat('i', count($torneos));
        $valsT  = array_map('intval', $torneos);
        $stmtT->bind_param($typesT, ...$valsT);
        $stmtT->execute();
        $resT = $stmtT->get_result();
        $nombres = [];
        while ($r = $resT->fetch_assoc()) $nombres[] = $r['nombreTorneo'];
        $torneos_nombre = implode(', ', $nombres);
        $stmtT->close();
    }

    echo json_encode([
        'arbitros'       => $arbitros,
        'torneos_nombre' => $torneos_nombre
    ]);
    break;
// ── REGISTRAR IMPRESIÓN ──────────────────────────────────────
    // Incrementa en 1 el contador del árbitro y guarda la fecha.
    // Llamado automáticamente al abrir generar_cuentas_cobro_html.php
    case 'registrar_impresion':
        $idArbitro = isset($_POST['idArbitro']) ? (int)$_POST['idArbitro'] : 0;

        if ($idArbitro <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            exit;
        }

        // INSERT ... ON DUPLICATE KEY UPDATE  → upsert atómico
        $stmt = $conn->prepare("
            INSERT INTO contador_impresion (idArbitro, totalImpresiones, ultimaImpresion)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                totalImpresiones = totalImpresiones + 1,
                ultimaImpresion  = NOW()
        ");
        $stmt->bind_param("i", $idArbitro);

        if ($stmt->execute()) {
            // Devolver el total actualizado
            $stmt2 = $conn->prepare("
                SELECT totalImpresiones FROM contador_impresion WHERE idArbitro = ?
            ");
            $stmt2->bind_param("i", $idArbitro);
            $stmt2->execute();
            $total = (int)$stmt2->get_result()->fetch_assoc()['totalImpresiones'];
            $stmt2->close();

            echo json_encode(['status' => 'ok', 'total' => $total]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $stmt->error]);
        }
        $stmt->close();
        break;

    // ── OBTENER CONTADOR ─────────────────────────────────────────
    // Devuelve el total actual de impresiones de un árbitro.
    case 'obtener_contador':
        $idArbitro = isset($_POST['idArbitro']) ? (int)$_POST['idArbitro'] : 0;

        if ($idArbitro <= 0) {
            echo json_encode(['total' => 0]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT totalImpresiones FROM contador_impresion WHERE idArbitro = ?
        ");
        $stmt->bind_param("i", $idArbitro);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        echo json_encode(['total' => $row ? (int)$row['totalImpresiones'] : 0]);
        break;

    // ── AJUSTAR CONTADOR ─────────────────────────────────────────
    // Permite corregir manualmente el valor del contador.
    case 'ajustar_contador':
        $idArbitro = isset($_POST['idArbitro']) ? (int)$_POST['idArbitro']  : 0;
        $nuevo     = isset($_POST['nuevo'])     ? (int)$_POST['nuevo']      : -1;

        if ($idArbitro <= 0 || $nuevo < 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Valores inválidos']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO contador_impresion (idArbitro, totalImpresiones, ultimaImpresion)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                totalImpresiones = ?,
                ultimaImpresion  = NOW()
        ");
        $stmt->bind_param("iii", $idArbitro, $nuevo, $nuevo);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'total' => $nuevo]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $stmt->error]);
        }
        $stmt->close();
        break;
    }
?>