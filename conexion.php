<?php
require_once("config.php");

$servidor = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
$usuario = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: "root";
$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: "root";
$base_datos = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: "SistemaEscolarDB";

$conexion = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");
?>
