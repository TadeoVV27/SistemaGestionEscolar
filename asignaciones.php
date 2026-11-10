<?php
session_start();
include("conexion.php");

// Candado: Solo el Administrador puede entrar aquí
if (!isset($_SESSION['correo']) || $_SESSION['rol'] != 'Admin') {
    header("location: panel.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "info";

// PROCESAR ELIMINACIÓN DE ASIGNACIÓN (SENTENCIAS PREPARADAS)
if (isset($_GET['eliminar_asignacion'])) {
    $id_asig_del = intval($_GET['eliminar_asignacion']);

    $stmt_del = $conexion->prepare("DELETE FROM Asignaciones WHERE IdAsignacion = ?");
    if ($stmt_del) {
        $stmt_del->bind_param("i", $id_asig_del);
        if ($stmt_del->execute()) {
            $mensaje = "La asignación de materia al docente ha sido removida exitosamente.";
            $tipo_alerta = "warning";
        } else {
            $mensaje = "Error al quitar la asignación: " . $stmt_del->error;
            $tipo_alerta = "danger";
        }
        $stmt_del->close();
    }
}

// PROCESAR FORMULARIO PARA ASIGNAR (SENTENCIAS PREPARADAS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_profesor = intval($_POST['id_profesor']);
    $id_materia = intval($_POST['id_materia']);

    // Validar con sentencia preparada que no se asigne la misma materia dos veces
    $stmt_chk = $conexion->prepare("SELECT IdAsignacion FROM Asignaciones WHERE IdProfesor = ? AND IdMateria = ?");
    if ($stmt_chk) {
        $stmt_chk->bind_param("ii", $id_profesor, $id_materia);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();

        if ($res_chk && $res_chk->num_rows > 0) {
            $mensaje = "Ese profesor ya tiene asignada esta materia.";
            $tipo_alerta = "warning";
            $stmt_chk->close();
        } else {
            $stmt_chk->close();

            $stmt_ins = $conexion->prepare("INSERT INTO Asignaciones (IdProfesor, IdMateria) VALUES (?, ?)");
            if ($stmt_ins) {
                $stmt_ins->bind_param("ii", $id_profesor, $id_materia);
                if ($stmt_ins->execute()) {
                    $mensaje = "¡Materia asignada al docente con éxito!";
                    $tipo_alerta = "success";
                } else {
                    $mensaje = "Error al vincular materia: " . $stmt_ins->error;
                    $tipo_alerta = "danger";
                }
                $stmt_ins->close();
            }
        }
    }
}

// Consultas para las listas desplegables del formulario
$profesores = $conexion->query("SELECT IdProfesor, Nombre, Apellidos, NumeroEmpleado FROM Profesores ORDER BY Apellidos ASC");
$materias = $conexion->query("SELECT IdMateria, NombreMateria FROM Materias ORDER BY NombreMateria ASC");

// FILTRO POR PROFESOR (SENTENCIA PREPARADA)
$filtro_profesor = isset($_GET['filtro_profesor']) ? intval($_GET['filtro_profesor']) : 0;
$nombre_profesor_filtrado = "";

if ($filtro_profesor > 0) {
    $stmt_pnom = $conexion->prepare("SELECT Nombre, Apellidos, NumeroEmpleado FROM Profesores WHERE IdProfesor = ?");
    if ($stmt_pnom) {
        $stmt_pnom->bind_param("i", $filtro_profesor);
        $stmt_pnom->execute();
        $res_pn = $stmt_pnom->get_result();
        if ($res_pn && $res_pn->num_rows > 0) {
            $d_pf = $res_pn->fetch_assoc();
            $nombre_profesor_filtrado = $d_pf['Nombre'] . " " . $d_pf['Apellidos'] . " (No. " . $d_pf['NumeroEmpleado'] . ")";
        }
        $stmt_pnom->close();
    }

    $stmt_asig = $conexion->prepare("SELECT a.IdAsignacion, p.IdProfesor, p.NumeroEmpleado, p.Nombre AS NombreProfesor, p.Apellidos, m.NombreMateria, m.Creditos 
                                     FROM Asignaciones a
                                     JOIN Profesores p ON a.IdProfesor = p.IdProfesor
                                     JOIN Materias m ON a.IdMateria = m.IdMateria
                                     WHERE a.IdProfesor = ?
                                     ORDER BY m.NombreMateria ASC");
    if ($stmt_asig) {
        $stmt_asig->bind_param("i", $filtro_profesor);
        $stmt_asig->execute();
        $resultado_asignaciones = $stmt_asig->get_result();
    }
} else {
    $query_asignaciones = "SELECT a.IdAsignacion, p.IdProfesor, p.NumeroEmpleado, p.Nombre AS NombreProfesor, p.Apellidos, m.NombreMateria, m.Creditos 
                           FROM Asignaciones a
                           JOIN Profesores p ON a.IdProfesor = p.IdProfesor
                           JOIN Materias m ON a.IdMateria = m.IdMateria
                           ORDER BY p.Apellidos ASC, m.NombreMateria ASC";
    $resultado_asignaciones = $conexion->query($query_asignaciones);
}

$total_asignaciones_mostradas = ($resultado_asignaciones) ? $resultado_asignaciones->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones - Sistema de Gestión Escolar</title>
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

        .form-select:focus {
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
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">Asignación de Cursos y Materias</h2>
                        <small class="text-muted">Vinculación de materias a docentes, consulta por profesor y bajas protegidas.</small>
                    </div>
                </div>
                <div>
                    <span class="badge badge-app-pill px-3 py-2 fs-6">
                        <i class="bi bi-link-45deg me-1"></i> <?php echo $total_asignaciones_mostradas; ?> Asignaciones Mostradas
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
            <!-- FORMULARIO DE ASIGNACIÓN -->
            <div class="col-lg-4">
                <div class="card card-custom">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-link" style="color: var(--app-primary);"></i>
                        <span>Nueva Asignación Docente</span>
                    </div>
                    <div class="card-body p-4">
                        <form action="asignaciones.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Seleccionar Profesor</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-badge"></i></span>
                                    <select class="form-select border-start-0" name="id_profesor" required>
                                        <option value="">Selecciona al docente...</option>
                                        <?php if($profesores) { while($prof = $profesores->fetch_assoc()) { ?>
                                            <option value="<?php echo $prof['IdProfesor']; ?>">
                                                <?php echo $prof['NumeroEmpleado'] . " - " . $prof['Apellidos'] . " " . $prof['Nombre']; ?>
                                            </option>
                                        <?php } } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Materia a Impartir</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-journal-check"></i></span>
                                    <select class="form-select border-start-0" name="id_materia" required>
                                        <option value="">Selecciona la materia...</option>
                                        <?php if($materias) { while($mat = $materias->fetch_assoc()) { ?>
                                            <option value="<?php echo $mat['IdMateria']; ?>">
                                                <?php echo $mat['NombreMateria']; ?>
                                            </option>
                                        <?php } } ?>
                                    </select>
                                </div>
                                <div class="form-text small">El docente solo podrá evaluar a los alumnos de esta materia asignada.</div>
                            </div>

                            <button type="submit" class="btn btn-app-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-check"></i>
                                <span>Asignar Materia Segura</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLA DE ASIGNACIONES CON FILTRO POR PROFESOR -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-card-checklist" style="color: var(--app-primary);"></i>
                            <span>
                                <?php if ($filtro_profesor > 0) { ?>
                                    Materias impartidas por: <strong><?php echo htmlspecialchars($nombre_profesor_filtrado); ?></strong>
                                <?php } else { ?>
                                    Directorio de Cargas Docentes
                                <?php } ?>
                            </span>
                        </div>
                    </div>

                    <!-- BARRA DE FILTRO POR PROFESOR -->
                    <div class="p-3 border-bottom" style="background-color: var(--app-light);">
                        <form method="GET" action="asignaciones.php" class="row g-2 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-funnel"></i></span>
                                    <select name="filtro_profesor" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="0">Ver todas las materias de todos los profesores...</option>
                                        <?php 
                                        $lista_profes_filtro = $conexion->query("SELECT IdProfesor, Nombre, Apellidos, NumeroEmpleado FROM Profesores ORDER BY Apellidos ASC");
                                        if($lista_profes_filtro) {
                                            while($pf = $lista_profes_filtro->fetch_assoc()) {
                                                $sel = ($filtro_profesor == $pf['IdProfesor']) ? 'selected' : '';
                                                echo "<option value='".$pf['IdProfesor']."' $sel>".$pf['Apellidos']." ".$pf['Nombre']." (No. ".$pf['NumeroEmpleado'].")</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-app-primary btn-sm rounded-pill px-3">
                                    <i class="bi bi-search me-1"></i> Filtrar
                                </button>
                                <?php if($filtro_profesor > 0) { ?>
                                    <a href="asignaciones.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 bg-white">
                                        <i class="bi bi-x-circle me-1"></i> Ver todas
                                    </a>
                                <?php } ?>
                            </div>
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-4">No. Empleado</th>
                                        <th>Profesor Titular</th>
                                        <th>Materia Asignada</th>
                                        <th class="pe-4 text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultado_asignaciones && $resultado_asignaciones->num_rows > 0) { 
                                        while($row = $resultado_asignaciones->fetch_assoc()) { ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?php echo htmlspecialchars($row['NumeroEmpleado']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark">
                                                    <i class="bi bi-person me-1 text-secondary"></i>
                                                    <?php echo htmlspecialchars($row['Apellidos'] . " " . $row['NombreProfesor']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge border" style="background-color: var(--app-light); color: var(--app-primary);">
                                                        <i class="bi bi-book me-1"></i>
                                                        <?php echo htmlspecialchars($row['NombreMateria']); ?>
                                                    </span>
                                                    <small class="text-muted ms-1">(<?php echo htmlspecialchars($row['Creditos']); ?> pts)</small>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="asignaciones.php?eliminar_asignacion=<?php echo $row['IdAsignacion']; ?><?php if($filtro_profesor > 0) echo '&filtro_profesor='.$filtro_profesor; ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                       onclick="return confirm('¿Confirmas que deseas retirar la asignación de <?php echo htmlspecialchars($row['NombreMateria']); ?> a este profesor?');">
                                                        <i class="bi bi-trash3 me-1"></i> Quitar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } 
                                    } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <?php if ($filtro_profesor > 0) { ?>
                                                    <i class="bi bi-folder2-open fs-3 d-block mb-1 text-secondary"></i>
                                                    Este profesor aún no tiene materias asignadas a su cargo.
                                                <?php } else { ?>
                                                    No hay materias asignadas a docentes aún.
                                                <?php } ?>
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
