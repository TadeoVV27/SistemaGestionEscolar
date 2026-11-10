<?php
require_once("config.php");

// 1. Detección automática de URL de conexión (Railway / Docker / Cloud)
$database_url = $_ENV['MYSQL_PRIVATE_URL'] ?? $_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('MYSQL_PRIVATE_URL') ?? getenv('MYSQL_URL') ?? getenv('DATABASE_URL');

if ($database_url) {
    $db_parts = parse_url($database_url);
    $servidor = $db_parts['host'] ?? "localhost";
    $puerto = isset($db_parts['port']) ? (int)$db_parts['port'] : 3306;
    $usuario = $db_parts['user'] ?? "root";
    $password = $db_parts['pass'] ?? "";
    $base_datos = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : "railway";

    $conexion = new mysqli($servidor, $usuario, $password, $base_datos, $puerto);
} else {
    // 2. Detección por variables individuales (Local XAMPP o variables directas de Railway)
    $servidor = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: "localhost";
    $usuario = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: "root";
    $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: "";
    $base_datos = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? $_ENV['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: "SistemaEscolarDB";
    $puerto = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? $_ENV['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: 3306);

    $conexion = new mysqli($servidor, $usuario, $password, $base_datos, $puerto);
}

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
