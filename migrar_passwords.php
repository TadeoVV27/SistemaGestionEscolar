<?php
// Script de utilidad para la Fase 2: Blindaje de Seguridad
// Abre este archivo en tu navegador: http://localhost/SistemaEscolar/migrar_passwords.php

include("conexion.php");

// 1. Asegurar que la columna Password tenga longitud suficiente (VARCHAR 255)
// Los hashes de password_hash() miden 60 caracteres.
$alerta_columna = "";
$modificar_col = $conexion->query("ALTER TABLE usuarios MODIFY Password VARCHAR(255) NOT NULL");
if ($modificar_col) {
    $alerta_columna = "Columna 'Password' ajustada a VARCHAR(255) con éxito.";
} else {
    $alerta_columna = "Nota sobre columna: " . $conexion->error;
}

// 2. Recorrer todos los usuarios y encriptar los que estén en texto plano
$resultado = $conexion->query("SELECT IdUsuario, Correo, Password FROM usuarios");
$total_migrados = 0;
$total_ya_encriptados = 0;
$detalles = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $id = $row['IdUsuario'];
        $correo = $row['Correo'];
        $pwd_actual = $row['Password'];

        // Si la contraseña no es un hash Bcrypt reconocido (no empieza con $2y$)
        $info = password_get_info($pwd_actual);
        if ($info['algo'] === 0 || $info['algoName'] === 'unknown' || !str_starts_with($pwd_actual, '$2y$')) {
            $nuevo_hash = password_hash($pwd_actual, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuarios SET Password = ? WHERE IdUsuario = ?");
            if ($stmt) {
                $stmt->bind_param("si", $nuevo_hash, $id);
                $stmt->execute();
                $stmt->close();
                $total_migrados++;
                $detalles[] = "Usuario <b>$correo</b>: Contraseña en texto plano encriptada con éxito.";
            }
        } else {
            $total_ya_encriptados++;
            $detalles[] = "Usuario <b>$correo</b>: Ya contaba con hash seguro.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración de Contraseñas a Hash - Fase 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8F7FD; }
        .card-custom { border-radius: 16px; border: 1px solid rgba(167, 139, 250, 0.3); }
    </style>
</head>
<body class="p-4 p-md-5">
    <div class="container col-lg-7">
        <div class="card card-custom shadow-sm bg-white p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: #EEE8FF; color: #6D4ACF; font-size: 1.5rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0" style="color: #29204F;">Migración de Contraseñas en MySQL</h4>
                    <small class="text-muted">Conversión de texto plano a hashes criptográficos Bcrypt</small>
                </div>
            </div>

            <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i> <?php echo $alerta_columna; ?>
            </div>

            <ul class="list-group mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Contraseñas que se acaban de encriptar:
                    <span class="badge bg-success rounded-pill fs-6"><?php echo $total_migrados; ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Contraseñas que ya estaban encriptadas:
                    <span class="badge bg-secondary rounded-pill fs-6"><?php echo $total_ya_encriptados; ?></span>
                </li>
            </ul>

            <h6 class="fw-bold text-muted small mt-3">Registro de usuarios procesados:</h6>
            <div class="border rounded p-2 mb-4 bg-light small" style="max-height: 150px; overflow-y: auto;">
                <?php foreach($detalles as $d) { echo "<div>&bull; $d</div>"; } ?>
            </div>

            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-primary px-4 rounded-pill" style="background-color: #6D4ACF; border-color: #6D4ACF;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Ir a Iniciar Sesión
                </a>
                <a href="panel.php" class="btn btn-outline-secondary px-4 rounded-pill">
                    Ir al Panel
                </a>
            </div>
        </div>
    </div>
</body>
</html>
