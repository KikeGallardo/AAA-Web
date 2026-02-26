<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require "basedatos_pdo.php";

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput, true);

    if ($data === null) throw new Exception("JSON inválido: " . json_last_error_msg());
    if (!isset($data['partidos']) || !is_array($data['partidos'])) throw new Exception("Se esperaba 'partidos' array.");

    // =============================================
    // TORNEO: Obligatorio, viene del frontend
    // =============================================
    $idTorneo = isset($data['idTorneo']) ? (int)$data['idTorneo'] : 0;
    if (!$idTorneo) {
        echo json_encode(["success" => false, "error" => "Debes seleccionar un torneo antes de guardar."]);
        exit;
    }

    // Verificar que el torneo existe en la BD
    $stmtT = $conn->prepare("SELECT idTorneo FROM torneo WHERE idTorneo = ?");
    $stmtT->execute([$idTorneo]);
    if (!$stmtT->fetch()) {
        echo json_encode(["success" => false, "error" => "El torneo seleccionado no existe en la base de datos."]);
        exit;
    }

    $partidos = $data['partidos'];

    // =============================================
    // PASO 1: Recolectar valores únicos
    // =============================================
    $nombresArbitros   = [];
    $nombresEquipos    = [];
    $nombresCategorias = [];

    foreach ($partidos as $p) {
        foreach (['ARBITRO','ASISTENTE 1','ASISTENTE 2','ASISTENTE 3'] as $col) {
            if (!empty($p[$col])) $nombresArbitros[] = trim($p[$col]);
        }
        if (!empty($p['EQUIPO A'])) $nombresEquipos[] = trim($p['EQUIPO A']);
        if (!empty($p['EQUIPO B'])) $nombresEquipos[] = trim($p['EQUIPO B']);
        if (!empty($p['CATEGORIA'])) $nombresCategorias[] = trim($p['CATEGORIA']);
    }

    $nombresArbitros   = array_unique($nombresArbitros);
    $nombresEquipos    = array_unique($nombresEquipos);
    $nombresCategorias = array_unique($nombresCategorias);

    // =============================================
    // PASO 2: Cargar árbitros en memoria (1 query)
    // =============================================
    $mapArbitros = cargarTodosArbitros($conn);

    $arbitrosFaltantes = [];
    foreach ($nombresArbitros as $nombre) {
        if (!buscarIdEnMap($mapArbitros, $nombre)) {
            $arbitrosFaltantes[] = $nombre;
        }
    }
    if (!empty($arbitrosFaltantes)) {
        echo json_encode([
            "success" => false,
            "error" => "No se puede guardar la programación. Faltan árbitros por registrar.",
            "arbitros_faltantes" => array_values($arbitrosFaltantes),
            "total_faltantes" => count($arbitrosFaltantes)
        ]);
        exit;
    }

    // =============================================
    // PASO 3: Validar categorías — NO se crean
    // =============================================
    $mapCategorias = cargarCategorias($conn);

    $categoriasFaltantes = [];
    foreach ($nombresCategorias as $nombre) {
        if (!buscarIdEnMap($mapCategorias, $nombre)) {
            $categoriasFaltantes[] = $nombre;
        }
    }
    if (!empty($categoriasFaltantes)) {
        echo json_encode([
            "success" => false,
            "error" => "No se puede guardar la programación. Hay categorías que no existen en el sistema.",
            "categorias_faltantes" => array_values($categoriasFaltantes),
            "total_faltantes" => count($categoriasFaltantes)
        ]);
        exit;
    }

    // =============================================
    // PASO 4: Cargar/crear equipos (solo equipos pueden crearse)
    // =============================================
    $mapEquipos = cargarOCrearEquipos($conn, $nombresEquipos);

    // =============================================
    // PASO 5: Cargar conflictos de horario (1 query)
    // =============================================
    $conflictosExistentes = cargarConflictos($conn, $partidos);

    // =============================================
    // PASO 6: Procesar y preparar batch INSERT
    // =============================================
    $conn->beginTransaction();

    $filasParaInsertar = [];
    $errores           = [];
    $conflictosNuevos  = [];

    foreach ($partidos as $i => $p) {
        try {
            foreach (['FECHA','EQUIPO A','EQUIPO B','ARBITRO'] as $col) {
                if (empty($p[$col])) throw new Exception("Columna requerida vacía: $col");
            }

            $fecha = convertirFecha($p['FECHA']);
            if (!$fecha) throw new Exception("Fecha inválida: " . $p['FECHA']);

            $hora = convertirHora($p['HORA'] ?? '');

            $idEquipo1 = buscarIdEnMap($mapEquipos, $p['EQUIPO A']);
            $idEquipo2 = buscarIdEnMap($mapEquipos, $p['EQUIPO B']);
            if (!$idEquipo1 || !$idEquipo2) throw new Exception("Equipos no encontrados");

            $nombreCategoria = trim($p['CATEGORIA'] ?? '');
            if (empty($nombreCategoria)) throw new Exception("Categoría vacía");
            $idCategoria = buscarIdEnMap($mapCategorias, $nombreCategoria);
            if (!$idCategoria) throw new Exception("Categoría no encontrada: '$nombreCategoria'");

            $idArbitroPrincipal = buscarIdEnMap($mapArbitros, $p['ARBITRO'] ?? '');
            $idAsistente1       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 1'] ?? '');
            $idAsistente2       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 2'] ?? '');
            $idAsistente3       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 3'] ?? '');

            // Verificar conflictos de horario
            $arbitrosAsignados = array_filter([$idArbitroPrincipal, $idAsistente1, $idAsistente2, $idAsistente3]);
            foreach ($arbitrosAsignados as $idArbitro) {
                $clave = "{$idArbitro}_{$fecha}_{$hora}";
                if (isset($conflictosExistentes[$clave]) || isset($conflictosNuevos[$clave])) {
                    $nombre = obtenerNombreDeMap($mapArbitros, $idArbitro);
                    throw new Exception("Conflicto de horario: $nombre ya tiene partido el $fecha a las $hora");
                }
            }
            foreach ($arbitrosAsignados as $idArbitro) {
                $conflictosNuevos["{$idArbitro}_{$fecha}_{$hora}"] = true;
            }

            $filasParaInsertar[] = [
                $idEquipo1, $idEquipo2, $fecha, $hora,
                $idCategoria, $idTorneo,                  // <-- torneo del select, no del Excel
                $idArbitroPrincipal, $idAsistente1, $idAsistente2, $idAsistente3,
                $p['ESCENARIO'] ?? '', $p['CATEGORIA'] ?? ''
            ];

        } catch (Exception $e) {
            $errores[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
        }
    }

    $guardados = 0;
    if (!empty($filasParaInsertar)) {
        $guardados = insertarPartidosEnBatch($conn, $filasParaInsertar);
    }

    $conn->commit();

    echo json_encode([
        "success"   => true,
        "guardados" => $guardados,
        "total"     => count($partidos),
        "errores"   => $errores,
        "message"   => "$guardados partidos guardados correctamente"
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}


// =============================================
// FUNCIONES
// =============================================

function cargarTodosArbitros(PDO $conn): array {
    $stmt = $conn->query("SELECT idArbitro, UPPER(TRIM(CONCAT(nombre, ' ', apellido))) AS nombreCompleto, UPPER(TRIM(nombre)) AS solo_nombre FROM arbitro");
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['nombreCompleto']] = $row['idArbitro'];
        $map[$row['solo_nombre']]    = $row['idArbitro'];
    }
    return $map;
}

function cargarCategorias(PDO $conn): array {
    $stmt = $conn->query("SELECT idCategoriaPagoArbitro, UPPER(TRIM(nombreCategoria)) AS nombre FROM categoriaPagoArbitro");
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['nombre']] = $row['idCategoriaPagoArbitro'];
    }
    return $map;
}

function cargarOCrearEquipos(PDO $conn, array $nombres): array {
    if (empty($nombres)) return [];
    $stmt = $conn->query("SELECT idEquipo, UPPER(TRIM(nombreEquipo)) AS nombre FROM equipo");
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['nombre']] = $row['idEquipo'];
    }
    foreach ($nombres as $nombre) {
        $key = strtoupper(trim($nombre));
        if (!isset($map[$key])) {
            $ins = $conn->prepare("INSERT INTO equipo (nombreEquipo) VALUES (?)");
            $ins->execute([$nombre]);
            $map[$key] = $conn->lastInsertId();
        }
    }
    return $map;
}

function cargarConflictos(PDO $conn, array $partidos): array {
    $fechas = [];
    foreach ($partidos as $p) {
        $f = convertirFecha($p['FECHA'] ?? '');
        if ($f) $fechas[] = $conn->quote($f);
    }
    if (empty($fechas)) return [];

    $in   = implode(',', array_unique($fechas));
    $stmt = $conn->query("SELECT idArbitro1, idArbitro2, idArbitro3, idArbitro4, fecha, hora FROM partido WHERE fecha IN ($in)");
    $map  = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hora = substr($row['hora'], 0, 8);
        foreach (['idArbitro1','idArbitro2','idArbitro3','idArbitro4'] as $col) {
            if (!empty($row[$col])) {
                $map["{$row[$col]}_{$row['fecha']}_{$hora}"] = true;
            }
        }
    }
    return $map;
}

function insertarPartidosEnBatch(PDO $conn, array $filas): int {
    $total = 0;
    foreach (array_chunk($filas, 50) as $chunk) {
        $ph  = implode(',', array_fill(0, count($chunk), '(?,?,?,?,?,?,?,?,?,?,?,?)'));
        $sql = "INSERT INTO partido
                    (idEquipo1,idEquipo2,fecha,hora,idCategoriaPagoArbitro,idTorneoPartido,
                     idArbitro1,idArbitro2,idArbitro3,idArbitro4,canchaLugar,categoriaText)
                VALUES $ph";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_merge(...$chunk));
        $total += $stmt->rowCount();
    }
    return $total;
}

function buscarIdEnMap(array $map, ?string $nombre): ?int {
    if (empty($nombre)) return null;
    $key = strtoupper(trim($nombre));
    if (isset($map[$key])) return $map[$key];
    foreach ($map as $k => $v) {
        if (str_contains($k, $key) || str_contains($key, $k)) return $v;
    }
    return null;
}

function obtenerNombreDeMap(array $map, int $id): string {
    $flipped = array_flip($map);
    return $flipped[$id] ?? "Árbitro #$id";
}

function convertirFecha($f): ?string {
    if (empty($f)) return null;
    if (is_numeric($f) && $f > 1000) {
        $d = new DateTime();
        $d->setTimestamp((int)(($f - 25569) * 86400));
        return $d->format('Y-m-d');
    }
    $meses = ['enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
              'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12];
    if (preg_match('/(\d+)\s+de\s+(\w+)\s+de\s+(\d{4})/i', $f, $m)) {
        $mes = $meses[strtolower($m[2])] ?? null;
        if ($mes) return sprintf("%04d-%02d-%02d", $m[3], $mes, $m[1]);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) return $f;
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $f, $m)) return sprintf("%04d-%02d-%02d", $m[3], $m[2], $m[1]);
    return null;
}

function convertirHora($hora): string {
    if (is_numeric($hora) && $hora < 1 && $hora > 0) {
        $min = round($hora * 24 * 60);
        return sprintf("%02d:%02d:00", floor($min / 60), $min % 60);
    }
    if (is_string($hora) && preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i', $hora, $m)) {
        $h = (int)$m[1]; $min = (int)$m[2];
        if (!empty($m[3])) {
            if (strtoupper($m[3]) === 'PM' && $h < 12) $h += 12;
            if (strtoupper($m[3]) === 'AM' && $h === 12) $h = 0;
        }
        return sprintf("%02d:%02d:00", $h, $min);
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) return $hora;
    return '00:00:00';
}