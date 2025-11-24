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

    foreach ($partidos as $i => $p) {
        try {

            $fecha = convertirFecha($p['FECHA'] ?? '');

            // Buscar IDs
            $idEquipo1 = buscarEquipoId($conn, $p['EQUIPO LOCAL'] ?? '');
            $idEquipo2 = buscarEquipoId($conn, $p['EQUIPO VISITANTE'] ?? '');

            if ($idEquipo1 === null) throw new Exception("Equipo local no encontrado: " . ($p['EQUIPO LOCAL'] ?? ''));
            if ($idEquipo2 === null) throw new Exception("Equipo visitante no encontrado: " . ($p['EQUIPO VISITANTE'] ?? ''));

            // Ejecutar INSERT
            $stmt->execute([
                ':equipoLocal'     => $idEquipo1,
                ':equipoVisitante' => $idEquipo2,
                ':fecha'           => $fecha,
                ':hora'            => $p['HORA'] ?? '',
                ':idCategoriaPago' => buscarCategoriaPagoId($conn, $p['CATEGORÍA'] ?? ''),
                ':idTorneo'        => buscarTorneoId($conn, $p['GRUPO'] ?? ''),
                ':arbitroPrincipal'=> buscarArbitroId($conn, $p['ARBITRO'] ?? ''),
                ':asistente1'      => buscarArbitroId($conn, $p['ASISTENTE 1'] ?? ''),
                ':asistente2'      => buscarArbitroId($conn, $p['ASISTENTE 2'] ?? ''),
                ':asistente3'      => buscarArbitroId($conn, $p['ASISTENTE 3'] ?? ''),
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
        "errores"   => $errores
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

function buscarEquipoId(PDO $conn, $nombre) {
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
    return $id !== false ? $id : null;
}

function buscarArbitroId(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("SELECT idArbitro FROM arbitro WHERE nombre LIKE :n LIMIT 1");
    $stmt->bindValue(':n', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    return $id !== false ? $id : null;
}

function buscarCategoriaPagoId(PDO $conn, $categoria) {
    if (!$categoria) return null;

    $stmt = $conn->prepare("SELECT idCategoriaPagoArbitro FROM categoriaPagoArbitro WHERE nombreCategoria LIKE :n LIMIT 1");
    $stmt->bindValue(':n', "%$categoria%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    return $id !== false ? $id : null;
}

function buscarTorneoId(PDO $conn, $torneo) {
    if (!$torneo) return null;

    $stmt = $conn->prepare("SELECT idTorneo FROM torneo WHERE nombreTorneo LIKE :n LIMIT 1");
    $stmt->bindValue(':n', "%$torneo%", PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    return $id !== false ? $id : null;
}
