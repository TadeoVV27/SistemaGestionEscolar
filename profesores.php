<?php
session_start();
include("conexion.php");

// Candado de seguridad: Solo Administrador
if (!isset($_SESSION['correo']) || $_SESSION['rol'] != 'Admin') {
    header("location: panel.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "info";

// PROCESAR ELIMINACIÓN DE PROFESOR (SENTENCIAS PREPARADAS)
if (isset($_GET['eliminar_profesor'])) {
    $id_profe_del = intval($_GET['eliminar_profesor']);
    
    // Obtener datos del profesor antes de eliminar
    $stmt_u = $conexion->prepare("SELECT IdUsuario, Nombre, Apellidos, NumeroEmpleado FROM Profesores WHERE IdProfesor = ?");
    if ($stmt_u) {
        $stmt_u->bind_param("i", $id_profe_del);
        $stmt_u->execute();
        $res_u = $stmt_u->get_result();

        if ($res_u && $res_u->num_rows > 0) {
            $datos_u = $res_u->fetch_assoc();
            $id_usuario_del = $datos_u['IdUsuario'];
            $nombre_profe = $datos_u['Nombre'] . " " . $datos_u['Apellidos'];
            $num_emp = $datos_u['NumeroEmpleado'];
            $stmt_u->close();

            // 1. Eliminar asignaciones vinculadas a este profesor
            $stmt_asig = $conexion->prepare("DELETE FROM Asignaciones WHERE IdProfesor = ?");
            if ($stmt_asig) {
                $stmt_asig->bind_param("i", $id_profe_del);
                $stmt_asig->execute();
                $stmt_asig->close();
            }

            // 2. Eliminar calificaciones registradas por este docente
            $stmt_cal = $conexion->prepare("DELETE FROM Calificaciones WHERE IdProfesor = ?");
            if ($stmt_cal) {
                $stmt_cal->bind_param("i", $id_profe_del);
                $stmt_cal->execute();
                $stmt_cal->close();
            }

            // 3. Eliminar de la tabla Profesores
            $stmt_pr = $conexion->prepare("DELETE FROM Profesores WHERE IdProfesor = ?");
            if ($stmt_pr) {
                $stmt_pr->bind_param("i", $id_profe_del);
                $stmt_pr->execute();
                $stmt_pr->close();
            }

            // 4. Eliminar cuenta de usuario
            if (!empty($id_usuario_del)) {
                $stmt_usr = $conexion->prepare("DELETE FROM Usuarios WHERE IdUsuario = ?");
                if ($stmt_usr) {
                    $stmt_usr->bind_param("i", $id_usuario_del);
                    $stmt_usr->execute();
                    $stmt_usr->close();
                }
            }

            $mensaje = "El docente <b>$nombre_profe</b> (Empleado #$num_emp) y sus asignaciones han sido eliminados correctamente.";
            $tipo_alerta = "warning";
        } else {
            $stmt_u->close();
            $mensaje = "No se encontró el profesor especificado para eliminar.";
            $tipo_alerta = "danger";
        }
    }
}

// PROCESAR REGISTRO DE PROFESOR (SENTENCIAS PREPARADAS Y HASH)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num_empleado = trim($_POST['num_empleado']);
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);

    // 1. Validar duplicado de número de empleado con sentencia preparada
    $stmt_chk = $conexion->prepare("SELECT NumeroEmpleado FROM Profesores WHERE NumeroEmpleado = ?");
    if ($stmt_chk) {
        $stmt_chk->bind_param("s", $num_empleado);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();

        if ($res_chk && $res_chk->num_rows > 0) {
            $mensaje = "Ya existe un docente registrado con el número de empleado <b>$num_empleado</b>.";
            $tipo_alerta = "warning";
            $stmt_chk->close();
        } else {
            $stmt_chk->close();

            // 2. Crear la cuenta de usuario con PASSWORD ENCRIPTADA (password_hash)
            $correo_institucional = "docente" . $num_empleado . "@escolar.edu";
            $password_default = "1234";
            $password_encriptada = password_hash($password_default, PASSWORD_DEFAULT);
            $rol_profe = "Profesor";

            $stmt_usr = $conexion->prepare("INSERT INTO Usuarios (Correo, Password, Rol) VALUES (?, ?, ?)");
            if ($stmt_usr) {
                $stmt_usr->bind_param("sss", $correo_institucional, $password_encriptada, $rol_profe);

                if ($stmt_usr->execute()) {
                    $id_usuario_nuevo = $stmt_usr->insert_id;
                    $stmt_usr->close();

                    // 3. Registrar al profesor con sentencia preparada
                    $stmt_pr = $conexion->prepare("INSERT INTO Profesores (NumeroEmpleado, Nombre, Apellidos, IdUsuario) VALUES (?, ?, ?, ?)");
                    if ($stmt_pr) {
                        $stmt_pr->bind_param("sssi", $num_empleado, $nombre, $apellidos, $id_usuario_nuevo);

                        if ($stmt_pr->execute()) {
                            $mensaje = "¡Profesor registrado exitosamente! Su correo asignado es: <b>$correo_institucional</b>";
                            $tipo_alerta = "success";
                        } else {
                            $mensaje = "Error al guardar profesor: " . $stmt_pr->error;
                            $tipo_alerta = "danger";
                        }
                        $stmt_pr->close();
                    }
                } else {
                    $mensaje = "Error al crear la cuenta de usuario: " . $stmt_usr->error;
                    $tipo_alerta = "danger";
                    $stmt_usr->close();
                }
            }
        }
    }
}

$resultado_profesores = $conexion->query("SELECT * FROM Profesores ORDER BY Apellidos ASC");
$total_profesores = ($resultado_profesores) ? $resultado_profesores->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Profesores - Sistema de Gestión Escolar</title>
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
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">Gestión de Profesores</h2>
                        <small class="text-muted">Registro, administración de plantilla docente y bajas seguras del sistema.</small>
                    </div>
                </div>
                <div>
                    <span class="badge badge-app-pill px-3 py-2 fs-6">
                        <i class="bi bi-person-badge me-1"></i> <?php echo $total_profesores; ?> Docentes Registrados
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
                        <i class="bi bi-person-fill-add" style="color: var(--app-primary);"></i>
                        <span>Registrar Nuevo Docente</span>
                    </div>
                    <div class="card-body p-4">
                        <form action="profesores.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Número de Empleado</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-hash"></i></span>
                                    <input type="number" class="form-control border-start-0" name="num_empleado" placeholder="Ej. 10452" required>
                                </div>
                                <div class="form-text small">Se creará su correo <code>docente{No}@escolar.edu</code> con contraseña encriptada <code>1234</code>.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Nombre(s)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0" name="nombre" placeholder="Ej. Daniela Natali" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Apellidos</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control border-start-0" name="apellidos" placeholder="Ej. García Carreño" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-app-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-check"></i>
                                <span>Guardar Profesor Seguro</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLA DE PROFESORES REGISTRADOS -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul" style="color: var(--app-primary);"></i>
                            <span>Plantilla Docente</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-4">No. Empleado</th>
                                        <th>Profesor</th>
                                        <th>Correo Institucional</th>
                                        <th class="pe-4 text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultado_profesores && $resultado_profesores->num_rows > 0) { ?>
                                        <?php while($row = $resultado_profesores->fetch_assoc()) { ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?php echo htmlspecialchars($row['NumeroEmpleado']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark">
                                                    <i class="bi bi-person-badge me-1 text-secondary"></i>
                                                    <?php echo htmlspecialchars($row['Nombre'] . " " . $row['Apellidos']); ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <code>docente<?php echo htmlspecialchars($row['NumeroEmpleado']); ?>@escolar.edu</code>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="asignaciones.php?filtro_profesor=<?php echo $row['IdProfesor']; ?>" 
                                                       class="btn btn-sm rounded-pill px-3 me-1" 
                                                       style="color: var(--app-primary); border: 1.5px solid var(--app-primary);"
                                                       title="Ver materias que imparte este profesor">
                                                        <i class="bi bi-book me-1"></i> Ver Materias
                                                    </a>
                                                    <a href="profesores.php?eliminar_profesor=<?php echo $row['IdProfesor']; ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                       onclick="return confirm('¿Confirmas que deseas eliminar al docente <?php echo htmlspecialchars($row['Nombre'].' '.$row['Apellidos']); ?>? Se borrarán sus materias asignadas y su cuenta.');">
                                                        <i class="bi bi-trash3 me-1"></i> Quitar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                No hay profesores registrados actualmente.
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
