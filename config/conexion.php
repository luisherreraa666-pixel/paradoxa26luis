<?php
// config/conexion.php

require_once __DIR__ . '/app.php';

$server = "localhost";
$user   = "root";
$pass   = "";
$db     = "paradoxa26";

// Que mysqli lance excepciones (es el comportamiento por defecto desde PHP 8.1,
// acá se fija explícito para que ande igual en cualquier versión).
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli($server, $user, $pass, $db);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("[paradoxa26] Error de conexión a la base de datos: " . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar a la base de datos. Verificá que MySQL esté corriendo (XAMPP) y que exista la base '" . $db . "' (importá paradoxa26.sql).");
}

// Accesible como global también si este archivo se incluye desde dentro de una función.
$GLOBALS['conexion'] = $conexion;
