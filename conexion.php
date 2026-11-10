<?php
require_once("config.php");

// Activar reporte de errores detallado si estamos depurando conexión
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Detección automática de URL de conexión (Railway / Docker / Cloud)
$database_url = $_ENV['MYSQL_PRIVATE_URL'] ?? $_ENV['MYSQL_URL'] ?? $_ENV['DATABASE_URL'] ?? getenv('MYSQL_PRIVATE_URL') ?? getenv('MYSQL_URL') ?? getenv('DATABASE_URL');

if (!empty($database_url)) {
    $db_parts = parse_url($database_url);
    $servidor = $db_parts['host'] ?? "localhost";
    $puerto = isset($db_parts['port']) ? (int)$db_parts['port'] : 3306;
    $usuario = $db_parts['user'] ?? "root";
    $password = $db_parts['pass'] ?? "";
    $base_datos = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : "railway";
} else {
    // 2. Detección por variables individuales (Local XAMPP o variables directas de Railway)
    $servidor = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: ($_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: "localhost");
    $usuario = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: ($_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: "root");
    $password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: ($_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: "");
    $base_datos = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: ($_ENV['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: "SistemaEscolarDB");
    $puerto = (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: ($_ENV['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: 3306));
}

try {
    $conexion = new mysqli($servidor, $usuario, $password, $base_datos, $puerto);
    $conexion->set_charset("utf8mb4");
} catch (Throwable $e) {
    die("
    <div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 650px; margin: 50px auto; padding: 25px; border-radius: 12px; background: #FFF5F5; border: 1px solid #FEB2B2; color: #9B1C1C;'>
        <h2 style='margin-top: 0; color: #C53030;'>❌ Error de Conexión a la Base de Datos</h2>
        <p><strong>Detalle del error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <div style='background: #FFF; padding: 15px; border-radius: 8px; border: 1px solid #FED7D7; font-family: monospace; font-size: 14px;'>
            <div><strong>Servidor (Host):</strong> " . htmlspecialchars($servidor) . "</div>
            <div><strong>Puerto:</strong> " . htmlspecialchars($puerto) . "</div>
            <div><strong>Base de Datos:</strong> " . htmlspecialchars($base_datos) . "</div>
            <div><strong>Usuario:</strong> " . htmlspecialchars($usuario) . "</div>
        </div>
        <p style='margin-top: 15px; font-size: 14px; color: #4A5568;'>
            Verifica que las variables de entorno de MySQL estén configuradas en Railway dentro del servicio <strong>SistemaGestionEscolar</strong>.
        </p>
    </div>
    ");
}
?>
