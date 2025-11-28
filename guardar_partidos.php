<?php
header('Content-Type: application/json');
require "basedatos_pdo.php";

try {
    // Recibir datos JSON
    $data = json_decode(file_get_contents("php://input"), true);

    if ($data === null) {
        throw new Exception("JSON inválido.");
    }

    if (!isset($data['partidos']) || !is_array($data['partidos'])) {
        throw new Exception("Formato JSON incorrecto.");
    }

    $partidos = $data['partidos'];

    // =============================================
    // VALIDACIÓN PREVIA: Verificar árbitros faltantes
    // =============================================
    $arbitrosFaltantes = [];
    
    foreach ($partidos as $i => $p) {
        $arbitrosEnPartido = [
            $p['ARBITRO'] ?? '',
            $p['ASISTENTE 1'] ?? '',
            $p['ASISTENTE 2'] ?? '',
            $p['ASISTENTE 3'] ?? ''
        ];
        
        foreach ($arbitrosEnPartido as $nombreArbitro) {
            if (!empty($nombreArbitro) && !arbitroExiste($conn, $nombreArbitro)) {
                // Agregar a la lista si no está duplicado
                if (!in_array($nombreArbitro, $arbitrosFaltantes)) {
                    $arbitrosFaltantes[] = $nombreArbitro;
                }
            }
        }
    }

    // Si hay árbitros faltantes, RECHAZAR la operación
    if (!empty($arbitrosFaltantes)) {
        echo json_encode([
            "success" => false,
            "error" => "No se puede guardar la programación. Faltan árbitros por registrar.",
            "arbitros_faltantes" => $arbitrosFaltantes,
            "total_faltantes" => count($arbitrosFaltantes),
            "mensaje_detallado" => "Se encontraron " . count($arbitrosFaltantes) . " árbitros no registrados. Por favor, regístralos antes de continuar."
        ]);
        exit;
    }

    // =============================================
    // Si llegamos aquí, TODOS los árbitros existen
    // =============================================

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

    foreach ($partidos as $i => $p) {
        try {
            // Convertir fecha
            $fecha = convertirFecha($p['FECHA'] ?? '');

            // Buscar o crear equipos
            $idEquipo1 = buscarOCrearEquipo($conn, $p['EQUIPO LOCAL'] ?? '');
            $idEquipo2 = buscarOCrearEquipo($conn, $p['EQUIPO VISITANTE'] ?? '');

            // Categoría
            $idCategoria = $p['CATEGORÍA'];

            // Buscar o crear torneo
            $idTorneo = buscarOCrearTorneo($conn, $p['GRUPO'] ?? '');

            // Buscar árbitros (ahora sabemos que TODOS existen)
            $idArbitroPrincipal = buscarArbitro($conn, $p['ARBITRO'] ?? '');
            $idAsistente1 = buscarArbitro($conn, $p['ASISTENTE 1'] ?? '');
            $idAsistente2 = buscarArbitro($conn, $p['ASISTENTE 2'] ?? '');
            $idAsistente3 = buscarArbitro($conn, $p['ASISTENTE 3'] ?? '');

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

    $conn->commit();

    echo json_encode([
        "success"   => true,
        "guardados" => $guardados,
        "total"     => count($partidos),
        "errores"   => $errores,
        "message"   => "$guardados partidos guardados correctamente"
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
 * Verifica si un árbitro existe en la base de datos
 */
function arbitroExiste(PDO $conn, $nombre) {
    if (!$nombre) return true; // Si está vacío, no es obligatorio

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE nombre LIKE :n 
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_COLUMN) !== false;
}

/**
 * Busca un árbitro (asumiendo que ya existe)
 */
function buscarArbitro(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE nombre LIKE :n 
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_COLUMN) ?: null;
}

/**
 * Busca un equipo, si no existe lo crea automáticamente
 */
function buscarOCrearEquipo(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idEquipo 
        FROM equipo 
        WHERE nombreEquipo LIKE :nombre
        LIMIT 1
    ");
    
    $stmt->bindValue(':nombre', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id !== false) {
        return $id;
    }

    $stmtInsert = $conn->prepare("INSERT INTO equipo (nombreEquipo) VALUES (:nombre)");
    $stmtInsert->execute([':nombre' => $nombre]);
    
    return $conn->lastInsertId();
}

/**
 * Busca un torneo, si no existe lo crea automáticamente
 */
function buscarOCrearTorneo(PDO $conn, $torneo) {
    if (!$torneo) return null;

    $stmt = $conn->prepare("
        SELECT idTorneo 
        FROM torneo 
        WHERE nombreTorneo LIKE :n 
        LIMIT 1
    ");
    $stmt->bindValue(':n', "%$torneo%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id !== false) {
        return $id;
    }

    $stmtInsert = $conn->prepare("INSERT INTO torneo (nombreTorneo) VALUES (:nombre)");
    $stmtInsert->execute([':nombre' => $torneo]);
    
    return $conn->lastInsertId();
}