<?php
session_start();
include("conexion.php");

// Candado de seguridad: Si NO es Admin, lo patea al panel
if (!isset($_SESSION['correo']) || $_SESSION['rol'] != 'Admin') {
    header("location: panel.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "info";

// PROCESAR ELIMINACIÓN DE ALUMNO (SENTENCIAS PREPARADAS)
if (isset($_GET['eliminar_matricula'])) {
    $matricula_del = trim($_GET['eliminar_matricula']);
    
    // Obtener IdUsuario del alumno antes de borrarlo con sentencia preparada
    $stmt_u = $conexion->prepare("SELECT IdUsuario, Nombre, Apellidos FROM Alumnos WHERE Matricula = ?");
    if ($stmt_u) {
        $stmt_u->bind_param("s", $matricula_del);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();

        if ($res_u && $res_u->num_rows > 0) {
            $datos_u = $res_u->fetch_assoc();
            $id_usuario_del = $datos_u['IdUsuario'];
            $nombre_completo = $datos_u['Nombre'] . " " . $datos_u['Apellidos'];
            $stmt_u->close();

            // 1. Eliminar calificaciones registradas del alumno
            $stmt_cal = $conexion->prepare("DELETE FROM Calificaciones WHERE Matricula = ?");
            if ($stmt_cal) {
                $stmt_cal->bind_param("s", $matricula_del);
                $stmt_cal->execute();
                $stmt_cal->close();
            }

            // 2. Eliminar al alumno
            $stmt_al = $conexion->prepare("DELETE FROM Alumnos WHERE Matricula = ?");
            if ($stmt_al) {
                $stmt_al->bind_param("s", $matricula_del);
                $stmt_al->execute();
                $stmt_al->close();
            }

            // 3. Eliminar usuario de acceso
            if (!empty($id_usuario_del)) {
                $stmt_usr = $conexion->prepare("DELETE FROM Usuarios WHERE IdUsuario = ?");
                if ($stmt_usr) {
                    $stmt_usr->bind_param("i", $id_usuario_del);
                    $stmt_usr->execute();
                    $stmt_usr->close();
                }
            }

            $mensaje = "El alumno <b>$nombre_completo</b> ($matricula_del) y su cuenta institucional han sido eliminados del sistema.";
            $tipo_alerta = "warning";
        } else {
            $stmt_u->close();
            $mensaje = "No se encontró el alumno especificado para eliminar.";
            $tipo_alerta = "danger";
        }
    }
}

// PROCESAR REGISTRO DE ALUMNO (SENTENCIAS PREPARADAS Y HASH)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = trim($_POST['matricula']);
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);

    // 1. Validar que la matrícula no esté duplicada usando sentencia preparada
    $stmt_chk = $conexion->prepare("SELECT Matricula FROM Alumnos WHERE Matricula = ?");
    if ($stmt_chk) {
        $stmt_chk->bind_param("s", $matricula);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();

        if ($res_chk && $res_chk->num_rows > 0) {
            $mensaje = "Ya existe un alumno registrado con la matrícula <b>$matricula</b>.";
            $tipo_alerta = "warning";
            $stmt_chk->close();
        } else {
            $stmt_chk->close();

            // 2. Crear la cuenta de usuario con PASSWORD ENCRIPTADA (password_hash)
            $correo_institucional = $matricula . "@escolar.edu";
            $password_default = "1234";
            $password_encriptada = password_hash($password_default, PASSWORD_DEFAULT);
            $rol_alumno = "Alumno";

            $stmt_usr = $conexion->prepare("INSERT INTO Usuarios (Correo, Password, Rol) VALUES (?, ?, ?)");
            if ($stmt_usr) {
                $stmt_usr->bind_param("sss", $correo_institucional, $password_encriptada, $rol_alumno);
                
                if ($stmt_usr->execute()) {
                    $id_usuario_nuevo = $stmt_usr->insert_id;
                    $stmt_usr->close();

                    // 3. Registrar al alumno vinculado a su IdUsuario mediante sentencia preparada
                    $stmt_al = $conexion->prepare("INSERT INTO Alumnos (Matricula, Nombre, Apellidos, IdUsuario) VALUES (?, ?, ?, ?)");
                    if ($stmt_al) {
                        $stmt_al->bind_param("sssi", $matricula, $nombre, $apellidos, $id_usuario_nuevo);
                        
                        if ($stmt_al->execute()) {
                            $mensaje = "¡Alumno registrado exitosamente! Su correo institucional asignado es: <b>$correo_institucional</b>";
                            $tipo_alerta = "success";
                        } else {
                            $mensaje = "Error al guardar los datos del alumno: " . $stmt_al->error;
                            $tipo_alerta = "danger";
                        }
                        $stmt_al->close();
                    }
                } else {
                    $mensaje = "Error al generar la cuenta de usuario: " . $stmt_usr->error;
                    $tipo_alerta = "danger";
                    $stmt_usr->close();
                }
            }
        }
    }
}

$resultado_alumnos = $conexion->query("SELECT * FROM Alumnos ORDER BY Matricula ASC");
$total_alumnos = ($resultado_alumnos) ? $resultado_alumnos->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Alumnos - Sistema de Gestión Escolar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --app-dark: #29204F;
            --app-primary: #6D4ACF;
            --app-primary-hover: #5b3cb5;
            --app-accent: #A78BFA;
            --app-light: #EEE8FF;
            --app-white: #FFFFFF;
            --app-bg: #F8F7FD;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--app-bg);
            color: #2D2845;
        }

        .navbar-custom {
            background-color: var(--app-dark);
            border-bottom: 3px solid var(--app-accent);
        }

        .navbar-brand-title {
            font-weight: 700;
            color: var(--app-white);
            letter-spacing: 0.3px;
        }

        .navbar-brand-subtitle {
            color: var(--app-accent);
            font-size: 0.78rem;
        }

        .hero-banner {
            background: linear-gradient(135deg, var(--app-white) 0%, var(--app-light) 100%);
            border-left: 6px solid var(--app-primary);
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(41, 32, 79, 0.05);
        }

        .card-custom {
            background: var(--app-white);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(41, 32, 79, 0.04);
            overflow: hidden;
        }

        .card-custom .card-header {
            background-color: var(--app-white);
            border-bottom: 1px solid rgba(167, 139, 250, 0.2);
            padding: 1rem 1.25rem;
            font-weight: 700;
            color: var(--app-dark);
        }

        .btn-app-primary {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
            color: var(--app-white);
            font-weight: 600;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            transition: all 0.2s ease;
        }

        .btn-app-primary:hover {
            background-color: var(--app-primary-hover);
            border-color: var(--app-primary-hover);
            color: var(--app-white);
        }

        .form-control:focus {
            border-color: var(--app-accent);
            box-shadow: 0 0 0 0.25rem rgba(109, 74, 207, 0.15);
        }

        .table-custom thead {
            background-color: var(--app-light);
            color: var(--app-dark);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-app-pill {
            background-color: var(--app-light);
            color: var(--app-primary);
            font-weight: 700;
            border: 1px solid var(--app-accent);
        }
    </style>
</head>
<body>

    <!-- NAVBAR INSTITUCIONAL -->
    <nav class="navbar navbar-expand-lg navbar-custom py-2 shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="panel.php">
                <i class="bi bi-mortarboard-fill text-white fs-3" style="color: var(--app-accent) !important;"></i>
                <div>
                    <span class="d-block lh-1 navbar-brand-title">Gestión Escolar</span>
                    <small class="navbar-brand-subtitle">Portal Institucional</small>
                </div>
            </a>
            
            <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="panel.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-house-door me-1"></i> Panel Principal
                </a>
                <a href="logout.php" class="btn btn-light btn-sm rounded-pill px-3 text-dark">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">

        <!-- HERO DEL MÓDULO -->
        <div class="hero-banner p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: var(--app-light); color: var(--app-primary); font-size: 1.5rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">Gestión de Alumnos</h2>
                        <small class="text-muted">Registro oficial de estudiantes, expedientes y bajas escolares seguras.</small>
                    </div>
                </div>
                <div>
                    <span class="badge badge-app-pill px-3 py-2 fs-6">
                        <i class="bi bi-person-check me-1"></i> <?php echo $total_alumnos; ?> Alumnos Registrados
                    </span>
                </div>
            </div>
        </div>

        <?php if(!empty($mensaje)) { ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div><?php echo $mensaje; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php } ?>

        <div class="row g-4">
            <!-- FORMULARIO DE REGISTRO -->
            <div class="col-lg-4">
                <div class="card card-custom">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-person-plus-fill" style="color: var(--app-primary);"></i>
                        <span>Registrar Nuevo Alumno</span>
                    </div>
                    <div class="card-body p-4">
                        <form action="alumnos.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Matrícula Escolar</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-heading"></i></span>
                                    <input type="number" class="form-control border-start-0" name="matricula" placeholder="Ej. 2158224" required>
                                </div>
                                <div class="form-text small">Se creará su correo <code>matricula@escolar.edu</code> con contraseña encriptada <code>1234</code>.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Nombre(s)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0" name="nombre" placeholder="Ej. Joel Guadalupe" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Apellidos</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0" name="apellidos" placeholder="Ej. Tamez Olmos" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-app-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-check"></i>
                                <span>Guardar Alumno Seguro</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLA DE ALUMNOS REGISTRADOS -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul" style="color: var(--app-primary);"></i>
                            <span>Padrón de Estudiantes</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Matrícula</th>
                                        <th>Nombre Completo</th>
                                        <th>Correo Asignado</th>
                                        <th class="pe-4 text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultado_alumnos && $resultado_alumnos->num_rows > 0) { ?>
                                        <?php while($row = $resultado_alumnos->fetch_assoc()) { ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?php echo htmlspecialchars($row['Matricula']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark">
                                                    <i class="bi bi-person-circle me-1 text-secondary"></i>
                                                    <?php echo htmlspecialchars($row['Nombre'] . " " . $row['Apellidos']); ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <code><?php echo htmlspecialchars($row['Matricula'] . "@escolar.edu"); ?></code>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="alumnos.php?eliminar_matricula=<?php echo urlencode($row['Matricula']); ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                       onclick="return confirm('¿Confirmas que deseas eliminar al alumno <?php echo htmlspecialchars($row['Nombre'].' '.$row['Apellidos']); ?>? Se borrará su usuario de acceso y sus calificaciones.');">
                                                        <i class="bi bi-trash3 me-1"></i> Quitar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                No hay alumnos registrados actualmente.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
