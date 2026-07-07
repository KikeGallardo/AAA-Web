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

    $forzarDuplicados = !empty($data['forzarDuplicados']);

    $idTorneo = isset($data['idTorneo']) ? (int)$data['idTorneo'] : 0;
    if (!$idTorneo) {
        echo json_encode(["success" => false, "error" => "Debes seleccionar un torneo antes de guardar."]);
        exit;
    }

    $stmtT = $conn->prepare("SELECT idTorneo FROM torneo WHERE idTorneo = ?");
    $stmtT->execute([$idTorneo]);
    if (!$stmtT->fetch()) {
        echo json_encode(["success" => false, "error" => "El torneo seleccionado no existe en la base de datos."]);
        exit;
    }

    $partidos = $data['partidos'];

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

    // Construir mapa de árbitros separando únicos de ambiguos
    $arbitrosRaw = cargarTodosArbitros($conn);
    $mapArbitros  = [];
    $mapaAmbiguos = [];
    foreach ($arbitrosRaw as $key => $entries) {
        if (count($entries) === 1) {
            $mapArbitros[$key] = array_key_first($entries);
        } else {
            $mapaAmbiguos[$key] = array_map(
                fn($id, $name) => ['id' => $id, 'nombre' => $name],
                array_keys($entries), array_values($entries)
            );
        }
    }

    // Mapeo manual enviado desde el frontend {nombreExcel: idArbitro}
    $mapeoArbitrosManual = $data['mapeoArbitrosManual'] ?? [];

    // Inyectar mapeos manuales (resuelve tanto faltantes como ambiguos)
    foreach ($mapeoArbitrosManual as $nombreExcel => $idArbitro) {
        $mapArbitros[strtoupper(trim($nombreExcel))] = (int)$idArbitro;
    }

    // Clasificar cada árbitro del Excel: resuelto, ambiguo o faltante
    $arbitrosFaltantes    = [];
    $arbitrosAmbiguosRes  = [];
    foreach ($nombresArbitros as $nombre) {
        if (buscarIdEnMap($mapArbitros, $nombre)) continue;

        $key = strtoupper(trim($nombre));
        $ambiguousKey = null;
        if (isset($mapaAmbiguos[$key])) {
            $ambiguousKey = $key;
        } else {
            $tokens = preg_split('/\s+/', $key);
            if (count($tokens) >= 2) {
                $clave = $tokens[0] . ' ' . $tokens[1];
                if (isset($mapaAmbiguos[$clave])) $ambiguousKey = $clave;
            }
        }

        if ($ambiguousKey !== null) {
            $arbitrosAmbiguosRes[] = [
                'nombre_excel' => $nombre,
                'opciones'     => array_values($mapaAmbiguos[$ambiguousKey])
            ];
        } else {
            $arbitrosFaltantes[] = $nombre;
        }
    }

    // Primero resolver ambigüedades
    if (!empty($arbitrosAmbiguosRes)) {
        echo json_encode([
            "success"           => false,
            "error"             => count($arbitrosAmbiguosRes) . " nombre(s) del Excel coinciden con más de un árbitro.",
            "arbitros_ambiguos" => $arbitrosAmbiguosRes
        ]);
        exit;
    }

    // Luego resolver faltantes
    if (!empty($arbitrosFaltantes)) {
        $stmt = $conn->query("SELECT idArbitro, CONCAT(nombre, ' ', apellido) AS nombre FROM arbitro ORDER BY nombre, apellido");
        $disponibles = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $disponibles[] = ['id' => $r['idArbitro'], 'nombre' => $r['nombre']];
        }
        echo json_encode([
            "success"              => false,
            "error"                => "Hay árbitros del Excel que no están registrados en el sistema.",
            "arbitros_faltantes"   => array_values($arbitrosFaltantes),
            "arbitros_disponibles" => $disponibles
        ]);
        exit;
    }

    $mapCategorias = cargarCategorias($conn);

    // Mapeo manual enviado desde el frontend {nombreExcel: idCategoria}
    $mapeoManual = $data['mapeoCategoriasManual'] ?? [];

    $categoriasFaltantes = [];
    foreach ($nombresCategorias as $nombre) {
        if (!buscarIdEnMap($mapCategorias, $nombre) && !isset($mapeoManual[$nombre])) {
            $categoriasFaltantes[] = $nombre;
        }
    }
    if (!empty($categoriasFaltantes)) {
        // Devolver categorías disponibles para que el usuario escoja
        $disponibles = [];
        foreach ($mapCategorias as $nombre => $id) {
            $disponibles[$id] = $nombre;
        }
        ksort($disponibles);
        $lista = array_map(fn($id, $nom) => ['id' => $id, 'nombre' => $nom], array_keys($disponibles), $disponibles);

        echo json_encode([
            "success"                => false,
            "error"                  => "Hay categorías del Excel que no existen en el sistema.",
            "categorias_faltantes"   => array_values($categoriasFaltantes),
            "categorias_disponibles" => $lista
        ]);
        exit;
    }

    $mapEquipos = cargarOCrearEquipos($conn, $nombresEquipos);
    $conflictosExistentes = cargarConflictos($conn, $partidos);

    $conn->beginTransaction();

    $filasParaInsertar  = [];
    $errores            = [];
    $advertencias       = [];
    $duplicados         = [];
    $conflictosNuevos   = [];
    $partidosVistos     = [];

    foreach ($partidos as $i => $p) {
        try {
            foreach (['FECHA','EQUIPO A','EQUIPO B','ARBITRO'] as $col) {
                if (empty($p[$col])) throw new Exception("Columna requerida vacía: $col");
            }

            $fecha = convertirFecha($p['FECHA']);
            if (!$fecha) throw new Exception("Fecha inválida: " . $p['FECHA']);

            $advertenciaHora = null;
            $hora = convertirHora($p['HORA'] ?? '', $advertenciaHora);
            if ($advertenciaHora) $advertencias[] = "Fila " . ($i + 1) . ": $advertenciaHora";

            $idEquipo1 = buscarIdEnMap($mapEquipos, $p['EQUIPO A']);
            $idEquipo2 = buscarIdEnMap($mapEquipos, $p['EQUIPO B']);
            if (!$idEquipo1 || !$idEquipo2) throw new Exception("Equipos no encontrados");

            $nombreCategoria = trim($p['CATEGORIA'] ?? '');
            if (empty($nombreCategoria)) throw new Exception("Categoría vacía");
            $idCategoria = buscarIdEnMap($mapCategorias, $nombreCategoria)
                        ?? (isset($mapeoManual[$nombreCategoria]) ? (int)$mapeoManual[$nombreCategoria] : null);
            if (!$idCategoria) throw new Exception("Categoría no encontrada: '$nombreCategoria'");

            $idArbitroPrincipal = buscarIdEnMap($mapArbitros, $p['ARBITRO']     ?? '');
            $idAsistente1       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 1'] ?? '');
            $idAsistente2       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 2'] ?? '');
            $idAsistente3       = buscarIdEnMap($mapArbitros, $p['ASISTENTE 3'] ?? '');

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

            $cancha = $p['ESCENARIO'] ?? '';
            $clavePartido = "{$idEquipo1}_{$idEquipo2}_{$fecha}_{$hora}_{$cancha}";
            if (isset($partidosVistos[$clavePartido])) {
                $duplicados[] = "Fila " . ($i + 1) . ": {$p['EQUIPO A']} vs {$p['EQUIPO B']} el {$fecha} a las {$hora} en '{$cancha}'";
            }
            $partidosVistos[$clavePartido] = true;

            $filasParaInsertar[] = [
                $idEquipo1, $idEquipo2, $fecha, $hora,
                $idCategoria, $idTorneo,
                $idArbitroPrincipal, $idAsistente1, $idAsistente2, $idAsistente3,
                $cancha, $p['CATEGORIA'] ?? ''
            ];

        } catch (Exception $e) {
            $errores[] = "Fila " . ($i + 1) . ": " . $e->getMessage();
        }
    }

    // Si hay duplicados y el usuario no confirmó, detener sin guardar
    if (!empty($duplicados) && !$forzarDuplicados) {
        $conn->rollBack();
        echo json_encode([
            "success"    => false,
            "duplicados" => $duplicados,
            "error"      => "Se encontraron " . count($duplicados) . " partido(s) duplicado(s). Confirma si deseas guardar de todas formas."
        ]);
        exit;
    }

    $guardados = 0;
    if (!empty($filasParaInsertar)) {
        $guardados = insertarPartidosEnBatch($conn, $filasParaInsertar);
    }

    $conn->commit();

    echo json_encode([
        "success"      => true,
        "guardados"    => $guardados,
        "total"        => count($partidos),
        "errores"      => $errores,
        "advertencias" => $advertencias,
        "message"      => "$guardados partidos guardados correctamente"
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

// =============================================
// FUNCIONES
// =============================================

function cargarTodosArbitros(PDO $conn): array {
    $stmt = $conn->query("SELECT idArbitro, UPPER(TRIM(nombre)) AS nombre, UPPER(TRIM(apellido)) AS apellido FROM arbitro");
    $rawMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id             = $row['idArbitro'];
        $nombre         = $row['nombre'];
        $apellido       = $row['apellido'];
        $primerNombre   = explode(' ', $nombre)[0];
        $primerApellido = explode(' ', $apellido)[0];
        $fullName       = "{$nombre} {$apellido}";
        foreach (array_unique([
            "{$nombre} {$apellido}",
            "{$primerNombre} {$primerApellido}",
            "{$primerNombre} {$apellido}",
            "{$nombre} {$primerApellido}"
        ]) as $key) {
            $rawMap[$key][$id] = $fullName;
        }
    }
    return $rawMap;
}

function obtenerClavesArbitros(PDO $conn): array {
    $stmt = $conn->query("SELECT idArbitro, UPPER(TRIM(nombre)) AS nombre, UPPER(TRIM(apellido)) AS apellido FROM arbitro");
    $debug = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombre         = $row['nombre'];
        $apellido       = $row['apellido'];
        $primerNombre   = explode(' ', $nombre)[0];
        $primerApellido = explode(' ', $apellido)[0];
        $debug[$row['idArbitro']] = [
            "bd_nombre"   => $nombre,
            "bd_apellido" => $apellido,
            "claves" => [
                "{$nombre} {$apellido}",
                "{$primerNombre} {$primerApellido}",
                "{$primerNombre} {$apellido}",
                "{$nombre} {$primerApellido}"
            ]
        ];
    }
    return $debug;
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

    // 1. Coincidencia exacta
    if (isset($map[$key])) return $map[$key];

    // 2. Primer nombre + primer apellido
    $tokens = preg_split('/\s+/', $key);
    if (count($tokens) >= 2) {
        $clave = $tokens[0] . ' ' . $tokens[1];
        if (isset($map[$clave])) return $map[$clave];
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

function convertirHora($hora, &$advertencia = null): string {
    if (is_numeric($hora) && $hora < 1 && $hora > 0) {
        $min = round($hora * 24 * 60);
        return sprintf("%02d:%02d:00", floor($min / 60), $min % 60);
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) return $hora;
    if (is_string($hora)) {
        // Normalizar separadores: coma o punto entre dígitos → dos puntos
        $normalizada = preg_replace('/(\d)[,.](\d)/', '$1:$2', trim($hora));
        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i', $normalizada, $m)) {
            $h = (int)$m[1]; $min = (int)$m[2];
            if (!empty($m[3])) {
                if (strtoupper($m[3]) === 'PM' && $h < 12) $h += 12;
                if (strtoupper($m[3]) === 'AM' && $h === 12) $h = 0;
            }
            $resultado = sprintf("%02d:%02d:00", $h, $min);
            if ($normalizada !== trim($hora)) {
                $advertencia = "Hora corregida: '{$hora}' → '{$resultado}'";
            }
            return $resultado;
        }
    }
    $advertencia = "Hora inválida: '{$hora}' → se usó 00:00:00";
    return '00:00:00';
}
