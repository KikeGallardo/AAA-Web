<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require "basedatos_pdo.php";
    
    $rawInput = file_get_contents("php://input");
    error_log("Raw input recibido: " . substr($rawInput, 0, 500));
    
    $data = json_decode($rawInput, true);

    if ($data === null) {
        throw new Exception("JSON inválido. Error: " . json_last_error_msg());
    }

    if (!isset($data['partidos']) || !is_array($data['partidos'])) {
        throw new Exception("Formato JSON incorrecto. Se esperaba 'partidos' array.");
    }

    $partidos = $data['partidos'];
    error_log("Total de partidos recibidos: " . count($partidos));

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
                if (!in_array($nombreArbitro, $arbitrosFaltantes)) {
                    $arbitrosFaltantes[] = $nombreArbitro;
                }
            }
        }
    }

    if (!empty($arbitrosFaltantes)) {
        echo json_encode([
            "success" => false,
            "error" => "No se puede guardar la programación. Faltan árbitros por registrar.",
            "arbitros_faltantes" => $arbitrosFaltantes,
            "total_faltantes" => count($arbitrosFaltantes)
        ]);
        exit;
    }

    // =============================================
    // Si llegamos aquí, TODOS los árbitros existen
    // =============================================

    $conn->beginTransaction();

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
            error_log("Procesando partido " . ($i + 1) . ": " . json_encode($p));
            
            // Verificar columnas necesarias
            $columnasRequeridas = ['FECHA', 'EQUIPO A', 'EQUIPO B', 'CATEGORIA', 'ARBITRO'];
            $columnasFaltantes = [];
            foreach ($columnasRequeridas as $col) {
                if (!isset($p[$col])) {
                    $columnasFaltantes[] = $col;
                } elseif ($col !== 'CATEGORIA' && ($p[$col] === '' || $p[$col] === null)) {
                    $columnasFaltantes[] = $col;
                }
            }
            
            if (!empty($columnasFaltantes)) {
                throw new Exception("Faltan columnas: " . implode(', ', $columnasFaltantes));
            }
            
            // Convertir fecha
            $fecha = convertirFecha($p['FECHA'] ?? '');
            if (!$fecha) {
                throw new Exception("Fecha inválida: " . ($p['FECHA'] ?? 'vacía'));
            }

            // ✅ CORREGIR HORA
            $hora = $p['HORA'] ?? '';
            
            // Si es un decimal de Excel (0.625 = 3:00 PM)
            if (is_numeric($hora) && $hora < 1 && $hora > 0) {
                $totalMinutos = round($hora * 24 * 60);
                $horas = floor($totalMinutos / 60);
                $minutos = $totalMinutos % 60;
                $hora = sprintf("%02d:%02d:00", $horas, $minutos);
            }
            // Si es texto con formato "3:00 PM" o "15:00"
            elseif (is_string($hora) && preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i', $hora, $matches)) {
                $h = (int)$matches[1];
                $m = (int)$matches[2];
                
                if (isset($matches[3])) {
                    $period = strtoupper($matches[3]);
                    if ($period === 'PM' && $h < 12) $h += 12;
                    if ($period === 'AM' && $h === 12) $h = 0;
                }
                
                $hora = sprintf("%02d:%02d:00", $h, $m);
            }
            // Si ya está en formato HH:MM:SS
            elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
                // Ya está bien, no hacer nada
            }
            // Formato inválido
            else {
                $hora = '00:00:00';
                error_log("⚠️ Hora inválida en fila " . ($i + 1) . ": " . ($p['HORA'] ?? 'vacía') . ", usando 00:00:00");
            }

            // Buscar o crear equipos
            $idEquipo1 = buscarOCrearEquipo($conn, $p['EQUIPO A'] ?? '');
            $idEquipo2 = buscarOCrearEquipo($conn, $p['EQUIPO B'] ?? '');

            if (!$idEquipo1 || !$idEquipo2) {
                throw new Exception("No se pudieron crear/encontrar los equipos");
            }

            // Categoría - BUSCAR POR NOMBRE
            $nombreCategoria = trim($p['CATEGORIA'] ?? '');
            if (empty($nombreCategoria)) {
                throw new Exception("Categoría vacía");
            }
            
            $idCategoria = buscarCategoriaPorNombre($conn, $nombreCategoria);
            if (!$idCategoria) {
                throw new Exception("Categoría no encontrada: '$nombreCategoria'");
            }

            // Buscar o crear torneo
            $nombreTorneo = $p['GRUPO'] ?? $p['CATEGORIA'] ?? 'Sin Torneo';
            $idTorneo = buscarOCrearTorneo($conn, $nombreTorneo);
            if (!$idTorneo) {
                throw new Exception("No se pudo crear/encontrar el torneo");
            }

            // Buscar árbitros
            $idArbitroPrincipal = buscarArbitro($conn, $p['ARBITRO'] ?? '');
            $idAsistente1 = buscarArbitro($conn, $p['ASISTENTE 1'] ?? '');
            $idAsistente2 = buscarArbitro($conn, $p['ASISTENTE 2'] ?? '');
            $idAsistente3 = buscarArbitro($conn, $p['ASISTENTE 3'] ?? '');

            // ✅ VALIDAR CONFLICTOS DE HORARIO
            $arbitrosAsignados = array_filter([
                $idArbitroPrincipal, 
                $idAsistente1, 
                $idAsistente2, 
                $idAsistente3
            ]);
            
            foreach ($arbitrosAsignados as $idArbitro) {
                if ($idArbitro && tieneConflictoHorario($conn, $idArbitro, $fecha, $hora)) {
                    $nombreArbitro = obtenerNombreArbitro($conn, $idArbitro);
                    throw new Exception("Conflicto de horario: $nombreArbitro ya tiene un partido el $fecha a las $hora");
                }
            }

            // Ejecutar INSERT
            $stmt->execute([
                ':equipoLocal'     => $idEquipo1,
                ':equipoVisitante' => $idEquipo2,
                ':fecha'           => $fecha,
                ':hora'            => $hora,
                ':idCategoriaPago' => $idCategoria,
                ':idTorneo'        => $idTorneo,
                ':arbitroPrincipal'=> $idArbitroPrincipal,
                ':asistente1'      => $idAsistente1,
                ':asistente2'      => $idAsistente2,
                ':asistente3'      => $idAsistente3,
                ':cancha'          => $p['ESCENARIO'] ?? '',
                ':categoria'       => $p['CATEGORIA'] ?? ''
            ]);

            $guardados++;

        } catch (Exception $e) {
            $mensaje = "Fila " . ($i + 1) . ": " . $e->getMessage();
            $errores[] = $mensaje;
            error_log("ERROR: " . $mensaje);
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
    error_log("ERROR FATAL: " . $e->getMessage());
    
    if (isset($conn) && $conn->inTransaction()) {
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
    if (empty($f)) return null;
    
    // Si es un número (serial de Excel)
    if (is_numeric($f) && $f > 1000) {
        $unixTimestamp = ($f - 25569) * 86400;
        $date = new DateTime();
        $date->setTimestamp($unixTimestamp);
        return $date->format('Y-m-d');
    }
    
    $meses = [
        'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
        'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
    ];

    if (preg_match('/(\d+)\s+de\s+(\w+)\s+de\s+(\d{4})/i', $f, $m)) {
        $mes = $meses[strtolower($m[2])] ?? null;
        if ($mes) {
            return sprintf("%04d-%02d-%02d", $m[3], $mes, $m[1]);
        }
    }
    
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
        return $f;
    }
    
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $f, $m)) {
        return sprintf("%04d-%02d-%02d", $m[3], $m[2], $m[1]);
    }

    return null;
}

function categoriaExiste(PDO $conn, $idCategoria) {
    $stmt = $conn->prepare("SELECT idCategoriaPagoArbitro FROM categoriaPagoArbitro WHERE idCategoriaPagoArbitro = ?");
    $stmt->execute([$idCategoria]);
    return $stmt->fetch(PDO::FETCH_COLUMN) !== false;
}

function buscarCategoriaPorNombre(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idCategoriaPagoArbitro 
        FROM categoriaPagoArbitro 
        WHERE UPPER(nombreCategoria) = UPPER(:nombre)
        LIMIT 1
    ");
    
    $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
    $stmt->execute();
    
    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id !== false) {
        return $id;
    }

    $stmt = $conn->prepare("
        SELECT idCategoriaPagoArbitro 
        FROM categoriaPagoArbitro 
        WHERE UPPER(nombreCategoria) LIKE UPPER(:nombre)
        LIMIT 1
    ");
    
    $stmt->bindValue(':nombre', "%$nombre%", PDO::PARAM_STR);
    $stmt->execute();
    
    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id === false) {
        error_log("⚠️ Categoría NO encontrada: '$nombre'");
    }
    
    return $id ?: null;
}

function arbitroExiste(PDO $conn, $nombre) {
    if (!$nombre) return true;

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE UPPER(nombre) LIKE UPPER(:n) 
           OR UPPER(CONCAT(nombre, ' ', apellido)) LIKE UPPER(:n)
        LIMIT 1
    ");
    
    $searchTerm = "%$nombre%";
    $stmt->bindValue(':n', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_COLUMN) !== false;
    
    if (!$resultado) {
        error_log("⚠️ Árbitro NO encontrado: '$nombre'");
    }

    return $resultado;
}

function buscarArbitro(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE UPPER(CONCAT(nombre, ' ', apellido)) LIKE UPPER(:n)
        LIMIT 1
    ");
    
    $searchTerm = "%$nombre%";
    $stmt->bindValue(':n', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id !== false) {
        return $id;
    }

    $stmt = $conn->prepare("
        SELECT idArbitro 
        FROM arbitro 
        WHERE UPPER(nombre) LIKE UPPER(:n)
        LIMIT 1
    ");
    
    $stmt->bindValue(':n', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $id = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($id === false) {
        error_log("⚠️ No se encontró árbitro: '$nombre'");
    }

    return $id ?: null;
}

function buscarOCrearEquipo(PDO $conn, $nombre) {
    if (!$nombre) return null;

    $stmt = $conn->prepare("
        SELECT idEquipo 
        FROM equipo 
        WHERE UPPER(nombreEquipo) LIKE UPPER(:nombre)
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

function buscarOCrearTorneo(PDO $conn, $torneo) {
    if (!$torneo) return null;

    $stmt = $conn->prepare("
        SELECT idTorneo 
        FROM torneo 
        WHERE UPPER(nombreTorneo) LIKE UPPER(:n)
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

/**
 * ✅ NUEVA FUNCIÓN: Verificar conflictos de horario
 */
function tieneConflictoHorario(PDO $conn, $idArbitro, $fecha, $hora) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM partido 
        WHERE fecha = :fecha 
          AND hora = :hora
          AND (idArbitro1 = :id OR idArbitro2 = :id OR idArbitro3 = :id OR idArbitro4 = :id)
    ");
    
    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':id' => $idArbitro
    ]);
    
    return $stmt->fetch(PDO::FETCH_COLUMN) > 0;
}

/**
 * ✅ NUEVA FUNCIÓN: Obtener nombre del árbitro para mensajes de error
 */
function obtenerNombreArbitro(PDO $conn, $idArbitro) {
    $stmt = $conn->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM arbitro WHERE idArbitro = ?");
    $stmt->execute([$idArbitro]);
    return $stmt->fetch(PDO::FETCH_COLUMN) ?: "Árbitro #$idArbitro";
}