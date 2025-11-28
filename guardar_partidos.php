<?php
header('Content-Type: application/json');
require "basedatos_pdo.php";

try {
    // Recibir datos JSON UNA sola vez
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data === null) {
        throw new Exception("JSON inválido.");
    }

    if (!isset($data['partidos']) || !is_array($data['partidos'])) {
        throw new Exception("Formato JSON incorrecto.");
    }

    $partidos = $data['partidos'];

    // Iniciar transacción
    $conn->beginTransaction();

    // SQL preparado
    $sql = "INSERT INTO partido (
        idEquipo1,
        idEquipo2,
        fecha,
        hora,
        idCategoriaPagoArbitro,
        idTorneoPartido,
        idArbitro1,
        idArbitro2,
        idArbitro3,
        idArbitro4,
        canchaLugar,
        categoriaText
    ) VALUES (
        :equipoLocal,
        :equipoVisitante,
        :fecha,
        :hora,
        :idCategoriaPago,
        :idTorneo,
        :arbitroPrincipal,
        :asistente1,
        :asistente2,
        :asistente3,
        :cancha,
        :categoria
    )";

    $stmt = $conn->prepare($sql);

    $guardados = 0;
    $errores = [];
    $notificaciones = [];

    foreach ($partidos as $i => $p) {
        try {

            // Convierte la fecha al formato de la BD
            $fecha = convertirFecha($p['FECHA'] ?? '');

            // Buscar o crear equipos
            $idEquipo1 = buscarOCrearEquipo($conn, $p['EQUIPO LOCAL'] ?? '');
            $idEquipo2 = buscarOCrearEquipo($conn, $p['EQUIPO VISITANTE'] ?? '');

            // Buscar o crear categoría
            $idCategoria = $p['CATEGORÍA'];

            // Buscar o crear torneo
            $idTorneo = buscarOCrearTorneo($conn, $p['GRUPO'] ?? '');

            // Buscar árbitros (sin crear, solo notificar si no existen)
            $idArbitroPrincipal = buscarArbitroConNotificacion($conn, $p['ARBITRO'] ?? '', $notificaciones);
            $idAsistente1 = buscarArbitroConNotificacion($conn, $p['ASISTENTE 1'] ?? '', $notificaciones);
            $idAsistente2 = buscarArbitroConNotificacion($conn, $p['ASISTENTE 2'] ?? '', $notificaciones);
            $idAsistente3 = buscarArbitroConNotificacion($conn, $p['ASISTENTE 3'] ?? '', $notificaciones);

            // Ejecutar INSERT
            $stmt->execute([
                ':equipoLocal'     => $idEquipo1,
                ':equipoVisitante' => $idEquipo2,
                ':fecha'           => $fecha,
                ':hora'            => $p['HORA'] ?? '',
                ':idCategoriaPago' => $idCategoria,
                ':idTorneo'        => $idTorneo,
                ':arbitroPrincipal'=> $idArbitroPrincipal,
                ':asistente1'      => $idAsistente1,
                ':asistente2'      => $idAsistente2,
                ':asistente3'      => $idAsistente3,
                ':cancha'          => $p['ESCENARIO DEPORTIVO'] ?? '',
                ':categoria'       => $p['CATEGORÍA'] ?? ''
            ]);

            $guardados++;

        } catch (Exception $e) {
            $errores[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
        }
    }

    // Guardar notificaciones de árbitros faltantes
    if (!empty($notificaciones)) {
        guardarNotificaciones($conn, $notificaciones);
    }

    $conn->commit();

    $mensaje = "$guardados partidos guardados correctamente";
    if (!empty($notificaciones)) {
        $mensaje .= ". Se encontraron " . count($notificaciones) . " árbitros no registrados (ver notificaciones)";
    }

    echo json_encode([
        "success"   => true,
        "guardados" => $guardados,
        "total"     => count($partidos),
        "errores"   => $errores,
        "message"   => $mensaje,
        "notificaciones" => count($notificaciones)
    ]);
    exit;

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
    exit;
}


/***** FUNCIONES *****/

function convertirFecha($f) {
    $meses = [
        'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
        'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
    ];

    if (preg_match('/(\d+)\s+de\s+(\w+)\s+de\s+(\d{4})/', strtolower($f), $m)) {
        return sprintf("%04d-%02d-%02d", $m[3], $meses[$m[2]], $m[1]);
    }

    return null;
}

/**
 * Busca un equipo, si no existe lo crea automáticamente
 */
function buscarOCrearEquipo(PDO $conn, $nombre) {
    if (!$nombre) return null;

    // Buscar primero
    $stmt = $conn->prepare("
        SELECT idEquipo 
        FROM equipo 
        WHERE nombreEquipo LIKE :nombre
        LIMIT 1
    ");
    
    $stmt->bindValue(':nombre', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Si existe, retornar
    if ($id !== false) {
        return $id;
    }

    // Si no existe, crear
    $stmtInsert = $conn->prepare("INSERT INTO equipo (nombreEquipo) VALUES (:nombre)");
    $stmtInsert->execute([':nombre' => $nombre]);
    
    return $conn->lastInsertId();
}

/**
 * Busca una categoría, si no existe la crea automáticamente
 */
function buscarOCrearCategoria(PDO $conn, $categoria) {
    if (!$categoria) return null;

    // Buscar primero
    $stmt = $conn->prepare("
        SELECT categoriaText
        FROM partido
        WHERE nombreCategoria LIKE :n
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$categoria%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Si existe, retornar
    if ($id !== false) {
        return $id;
    }

    // Si no existe, crear con valores por defecto
    $stmtInsert = $conn->prepare("
        INSERT INTO categoriaPagoArbitro (nombreCategoria, montoArbitro, montoAsistente) 
        VALUES (:nombre, 0, 0)
    ");
    $stmtInsert->execute([':nombre' => $categoria]);
    
    return $conn->lastInsertId();
}

/**
 * Busca un torneo, si no existe lo crea automáticamente
 */
function buscarOCrearTorneo(PDO $conn, $torneo) {
    if (!$torneo) return null;

    // Buscar primero
    $stmt = $conn->prepare("
        SELECT idTorneo 
        FROM torneo 
        WHERE nombreTorneo LIKE :n 
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$torneo%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Si existe, retornar
    if ($id !== false) {
        return $id;
    }

    // Si no existe, crear
    $stmtInsert = $conn->prepare("INSERT INTO torneo (nombreTorneo) VALUES (:nombre)");
    $stmtInsert->execute([':nombre' => $torneo]);
    
    return $conn->lastInsertId();
}

/**
 * Busca un árbitro, si no existe retorna NULL y agrega notificación
 */
function buscarArbitroConNotificacion(PDO $conn, $nombre, &$notificaciones) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE nombre LIKE :n 
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    // Si existe, retornar
    if ($id !== false) {
        return $id;
    }

    // Si no existe, agregar a notificaciones (evitar duplicados)
    if (!in_array($nombre, array_column($notificaciones, 'nombre'))) {
        $notificaciones[] = [
            'tipo' => 'arbitro_faltante',
            'nombre' => $nombre,
            'mensaje' => "El árbitro '$nombre' no está registrado en la base de datos"
        ];
    }

    return null;
}

/**
 * Guarda las notificaciones en la tabla de notificaciones
 */
function guardarNotificaciones(PDO $conn, $notificaciones) {
    $stmt = $conn->prepare("
        INSERT INTO notificaciones (tipo, mensaje, fecha_creacion) 
        VALUES (:tipo, :mensaje, NOW())
    ");

    foreach ($notificaciones as $notif) {
        $stmt->execute([
            ':tipo' => $notif['tipo'],
            ':mensaje' => $notif['mensaje']
        ]);
    }
}