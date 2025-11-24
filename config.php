<?php
// config.php - Mantén este archivo FUERA del directorio público
// o protégelo con .htaccess

// Configuración de base de datos
define('DB_HOST', 'db-fde-02.apollopanel.com');
define('DB_PORT', '3306');
define('DB_USER', 'u136076_tCDay64NMd');
define('DB_PASS', 'AzlYnjAiSFN!d=ZtajgQa=q.');
define('DB_NAME', 's136076_Aribatraje');

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora

// Función para obtener conexión segura
function getDBConnection() {
    $conexion = new mysqli(
        DB_HOST . ':' . DB_PORT,
        DB_USER,
        DB_PASS,
        DB_NAME
    );
    
    if ($conexion->connect_error) {
        // En producción, no mostrar detalles del error
        error_log("Error de conexión BD: " . $conexion->connect_error);
        die("Error de conexión a la base de datos. Contacte al administrador.");
    }
    
    $conexion->set_charset("utf8mb4");
    return $conexion;
}

// Función helper para escapar HTML
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>