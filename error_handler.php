<?php

// ── Manejo global de errores ──────────────────────────────────
function manejarError($errno, $errstr, $errfile, $errline) {
    $mensaje = "[ERROR $errno] $errstr en $errfile:$errline";
    error_log($mensaje);
    
    // En producción, no mostrar detalles al usuario
    if (defined('ENTORNO') && ENTORNO === 'desarrollo') {
        echo "<pre style='color:red;background:#fff3f3;padding:10px;border:1px solid red;'>$mensaje</pre>";
    }
    return true; // Evita que PHP maneje el error por defecto
}

function manejarExcepcion(Throwable $e) {
    $mensaje = "[EXCEPCIÓN] " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine();
    error_log($mensaje);
    
    // Si la petición espera JSON (endpoints AJAX), responder en JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => 'Error interno del servidor']);
        exit;
    }
    
    http_response_code(500);
    echo "<div style='text-align:center;padding:50px;font-family:Arial;'>
            <h2>Ocurrió un error inesperado</h2>
            <p>Por favor contacta al administrador.</p>
          </div>";
    exit;
}

function manejarShutdown() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        manejarExcepcion(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
    }
}

define('ENTORNO', 'produccion'); // Cambia a 'desarrollo' para ver errores detallados

set_error_handler('manejarError');
set_exception_handler('manejarExcepcion');
register_shutdown_function('manejarShutdown');

// Suprimir errores en pantalla (se guardan en log)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

?>