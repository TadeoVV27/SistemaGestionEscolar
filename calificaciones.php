<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['correo'])) {
    header("location: index.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "info";

// 1. PROCESAR EL POST (Guardar una calificación nueva con SENTENCIA PREPARADA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor')) {
    $matricula = trim($_POST['matricula']);
    $id_materia = intval($_POST['id_materia']);
    $id_profesor = intval($_POST['id_profesor']);
    $calificacion = floatval($_POST['calificacion']);

    $stmt_ins = $conexion->prepare("INSERT INTO calificaciones (Matricula, IdMateria, IdProfesor, Calificacion) VALUES (?, ?, ?, ?)");
    if ($stmt_ins) {
        $stmt_ins->bind_param("siid", $matricula, $id_materia, $id_profesor, $calificacion);
        if ($stmt_ins->execute()) {
            $mensaje = "¡Calificación asentada exitosamente con valor <b>$calificacion</b>!";
            $tipo_alerta = "success";
        } else {
            $mensaje = "Error al asentar calificación: " . $stmt_ins->error;
            $tipo_alerta = "danger";
        }
        $stmt_ins->close();
    }
}

// 1.1 PROCESAR ELIMINACIÓN DE CALIFICACIÓN (Admin o Profesor con SENTENCIA PREPARADA)
if (isset($_GET['eliminar_calificacion']) && ($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor')) {
    $id_cal_del = intval($_GET['eliminar_calificacion']);
    if ($_SESSION['rol'] == 'Profesor') {
        $correo_actual = $_SESSION['correo'];
        $stmt_del = $conexion->prepare("DELETE c FROM calificaciones c JOIN profesores p ON c.IdProfesor = p.IdProfesor JOIN usuarios u ON p.IdUsuario = u.IdUsuario WHERE c.IdCalificacion = ? AND u.Correo = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("is", $id_cal_del, $correo_actual);
            $stmt_del->execute();
            $stmt_del->close();
        }
    } else {
        $stmt_del = $conexion->prepare("DELETE FROM calificaciones WHERE IdCalificacion = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("i", $id_cal_del);
            $stmt_del->execute();
            $stmt_del->close();
        }
    }
    $mensaje = "La calificación seleccionada ha sido eliminada.";
    $tipo_alerta = "warning";
}

// 2. AVERIGUAR EL ID DEL PROFESOR (SENTENCIA PREPARADA)
$id_profe_actual = null;
if ($_SESSION['rol'] == 'Profesor') {
    $correo_actual = $_SESSION['correo'];
    $stmt_prf = $conexion->prepare("SELECT p.IdProfesor FROM profesores p JOIN usuarios u ON p.IdUsuario = u.IdUsuario WHERE u.Correo = ?");
    if ($stmt_prf) {
        $stmt_prf->bind_param("s", $correo_actual);
        $stmt_prf->execute();
        $res_prf = $stmt_prf->get_result();
        if ($res_prf && $res_prf->num_rows > 0) {
            $datos_profe = $res_prf->fetch_assoc();
            $id_profe_actual = $datos_profe['IdProfesor'];
        }
        $stmt_prf->close();
    }
}

// 3. LLENAR SELECTS DEL FORMULARIO DE "ASENTAR CALIFICACIÓN"
if ($_SESSION['rol'] == 'Admin') {
    $alumnos = $conexion->query("SELECT Matricula, Nombre, Apellidos FROM alumnos ORDER BY Apellidos ASC");
    $materias = $conexion->query("SELECT IdMateria, NombreMateria FROM materias ORDER BY NombreMateria ASC");
    $profesores = $conexion->query("SELECT IdProfesor, Nombre, Apellidos, NumeroEmpleado FROM profesores ORDER BY Apellidos ASC");
} else if ($_SESSION['rol'] == 'Profesor' && $id_profe_actual) {
    $alumnos = $conexion->query("SELECT Matricula, Nombre, Apellidos FROM alumnos ORDER BY Apellidos ASC");
    $stmt_mprofe = $conexion->prepare("SELECT m.IdMateria, m.NombreMateria FROM materias m JOIN asignaciones a ON m.IdMateria = a.IdMateria WHERE a.IdProfesor = ? ORDER BY m.NombreMateria ASC");
    if ($stmt_mprofe) {
        $stmt_mprofe->bind_param("i", $id_profe_actual);
        $stmt_mprofe->execute();
        $materias = $stmt_mprofe->get_result();
    }
    $stmt_pinfo = $conexion->prepare("SELECT IdProfesor, Nombre, Apellidos, NumeroEmpleado FROM profesores WHERE IdProfesor = ?");
    if ($stmt_pinfo) {
        $stmt_pinfo->bind_param("i", $id_profe_actual);
        $stmt_pinfo->execute();
        $profesores = $stmt_pinfo->get_result();
    }
}

// 4. FILTROS DE BÚSQUEDA (GET)
$filtro_materia = (isset($_GET['filtro_materia']) && $_GET['filtro_materia'] !== '') ? intval($_GET['filtro_materia']) : null;
$filtro_profesor = (isset($_GET['filtro_profesor']) && $_GET['filtro_profesor'] !== '') ? intval($_GET['filtro_profesor']) : null;

// 5. SELECTS PARA FORMULARIO DE FILTROS
if ($_SESSION['rol'] == 'Profesor' && $id_profe_actual) {
    $stmt_flm = $conexion->prepare("SELECT m.IdMateria, m.NombreMateria FROM materias m JOIN asignaciones a ON m.IdMateria = a.IdMateria WHERE a.IdProfesor = ?");
    if ($stmt_flm) {
        $stmt_flm->bind_param("i", $id_profe_actual);
        $stmt_flm->execute();
        $filtro_list_materias = $stmt_flm->get_result();
    }
} else {
    $filtro_list_materias = $conexion->query("SELECT IdMateria, NombreMateria FROM materias ORDER BY NombreMateria ASC");
    $filtro_list_profes = $conexion->query("SELECT IdProfesor, Nombre, Apellidos FROM profesores ORDER BY Apellidos ASC");
}

// 6. HISTORIAL DE CALIFICACIONES SEGÚN ROL (SENTENCIAS PREPARADAS)
$condiciones = [];
$tipos = "";
$params = [];

if ($_SESSION['rol'] == 'Alumno' || $_SESSION['rol'] == 'Estudiante') {
    $correo_actual = $_SESSION['correo'];
    $sql_base = "SELECT c.IdCalificacion, a.Matricula, a.Nombre AS NombreAlumno, a.Apellidos AS ApellidosAlumno, 
                        m.NombreMateria, p.Nombre AS NombreProfesor, p.Apellidos AS ApellidosProfesor, 
                        c.Calificacion, c.FechaRegistro 
                 FROM calificaciones c
                 JOIN alumnos a ON c.Matricula = a.Matricula
                 JOIN materias m ON c.IdMateria = m.IdMateria
                 JOIN profesores p ON c.IdProfesor = p.IdProfesor
                 JOIN usuarios u ON a.IdUsuario = u.IdUsuario
                 WHERE u.Correo = ?";
    $tipos .= "s";
    $params[] = $correo_actual;

    if ($filtro_materia !== null) {
        $sql_base .= " AND c.IdMateria = ?";
        $tipos .= "i";
        $params[] = $filtro_materia;
    }
    if ($filtro_profesor !== null) {
        $sql_base .= " AND c.IdProfesor = ?";
        $tipos .= "i";
        $params[] = $filtro_profesor;
    }
} else if ($_SESSION['rol'] == 'Profesor') {
    $correo_actual = $_SESSION['correo'];
    $sql_base = "SELECT c.IdCalificacion, a.Matricula, a.Nombre AS NombreAlumno, a.Apellidos AS ApellidosAlumno, 
                        m.NombreMateria, p.Nombre AS NombreProfesor, p.Apellidos AS ApellidosProfesor, 
                        c.Calificacion, c.FechaRegistro 
                 FROM calificaciones c
                 JOIN alumnos a ON c.Matricula = a.Matricula
                 JOIN materias m ON c.IdMateria = m.IdMateria
                 JOIN profesores p ON c.IdProfesor = p.IdProfesor
                 JOIN usuarios u ON p.IdUsuario = u.IdUsuario
                 WHERE u.Correo = ?";
    $tipos .= "s";
    $params[] = $correo_actual;

    if ($filtro_materia !== null) {
        $sql_base .= " AND c.IdMateria = ?";
        $tipos .= "i";
        $params[] = $filtro_materia;
    }
} else {
    // Administrador
    $sql_base = "SELECT c.IdCalificacion, a.Matricula, a.Nombre AS NombreAlumno, a.Apellidos AS ApellidosAlumno, 
                        m.NombreMateria, p.Nombre AS NombreProfesor, p.Apellidos AS ApellidosProfesor, 
                        c.Calificacion, c.FechaRegistro 
                 FROM calificaciones c
                 JOIN alumnos a ON c.Matricula = a.Matricula
                 JOIN materias m ON c.IdMateria = m.IdMateria
                 JOIN profesores p ON c.IdProfesor = p.IdProfesor
                 WHERE 1=1";

    if ($filtro_materia !== null) {
        $sql_base .= " AND c.IdMateria = ?";
        $tipos .= "i";
        $params[] = $filtro_materia;
    }
    if ($filtro_profesor !== null) {
        $sql_base .= " AND c.IdProfesor = ?";
        $tipos .= "i";
        $params[] = $filtro_profesor;
    }
}

$sql_base .= " ORDER BY c.FechaRegistro DESC";

$stmt_cal_hist = $conexion->prepare($sql_base);
if ($stmt_cal_hist) {
    if (!empty($params)) {
        $stmt_cal_hist->bind_param($tipos, ...$params);
    }
    $stmt_cal_hist->execute();
    $resultado_calificaciones = $stmt_cal_hist->get_result();
} else {
    $resultado_calificaciones = false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Calificaciones - Sistema de Gestión Escolar</title>
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

        .btn-app-outline {
            color: var(--app-primary);
            border: 1.5px solid var(--app-primary);
            background: transparent;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-app-outline:hover {
            background-color: var(--app-primary);
            color: var(--app-white);
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

        .badge-nota-aprobada {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            font-weight: 700;
        }

        .badge-nota-reprobada {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            font-weight: 700;
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
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">Módulo de Calificaciones y Evaluaciones</h2>
                        <small class="text-muted">
                            <?php 
                            if ($_SESSION['rol'] == 'Profesor') {
                                echo 'Captura y control de notas para tus asignaturas asignadas.';
                            } else if ($_SESSION['rol'] == 'Alumno') {
                                echo 'Consulta de historial escolar y descarga de boleta oficial.';
                            } else {
                                echo 'Auditoría, captura y supervisión general de evaluaciones escolares.';
                            }
                            ?>
                        </small>
                    </div>
                </div>
                <div>
                    <?php if ($_SESSION['rol'] == 'Alumno') { ?>
                        <a href="boleta.php" target="_blank" class="btn btn-app-primary rounded-pill px-3 py-2 d-flex align-items-center gap-2 shadow-sm">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <span>Descargar Boleta PDF</span>
                        </a>
                    <?php } else { ?>
                        <span class="badge badge-app-pill px-3 py-2 fs-6">
                            <i class="bi bi-person-badge me-1"></i> Rol: <?php echo $_SESSION['rol']; ?>
                        </span>
                    <?php } ?>
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
            <?php if($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor') { ?>
                <!-- FORMULARIO DE ASENTAR CALIFICACIÓN -->
                <div class="col-lg-4">
                    <div class="card card-custom">
                        <div class="card-header d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square" style="color: var(--app-primary);"></i>
                            <span>Asentar Nueva Calificación</span>
                        </div>
                        <div class="card-body p-4">
                            <form action="calificaciones.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Estudiante</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-mortarboard"></i></span>
                                        <select class="form-select border-start-0" name="matricula" required>
                                            <option value="">Selecciona al alumno...</option>
                                            <?php if(isset($alumnos) && $alumnos) { 
                                                while($alu = $alumnos->fetch_assoc()) { 
                                                    echo "<option value='".$alu['Matricula']."'>".$alu['Matricula']." - ".$alu['Nombre']." ".$alu['Apellidos']."</option>"; 
                                                } 
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Materia</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-book"></i></span>
                                        <select class="form-select border-start-0" name="id_materia" required>
                                            <option value="">Selecciona la materia...</option>
                                            <?php if(isset($materias) && $materias) { 
                                                while($mat = $materias->fetch_assoc()) { 
                                                    echo "<option value='".$mat['IdMateria']."'>".$mat['NombreMateria']."</option>"; 
                                                } 
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Docente Evaluador</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-badge"></i></span>
                                        <select class="form-select border-start-0" name="id_profesor" required>
                                            <?php if($_SESSION['rol'] == 'Profesor' && isset($profesores)) { 
                                                $prof_row = $profesores->fetch_assoc();
                                                echo "<option value='".$prof_row['IdProfesor']."'>".$prof_row['Nombre']." ".$prof_row['Apellidos']."</option>";
                                            } else if(isset($profesores) && $profesores) { 
                                                echo "<option value=''>Selecciona al profesor...</option>";
                                                while($prof = $profesores->fetch_assoc()) { 
                                                    echo "<option value='".$prof['IdProfesor']."'>".$prof['Nombre']." ".$prof['Apellidos']."</option>"; 
                                                } 
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-semibold text-muted">Calificación (0 - 100)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-123"></i></span>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control border-start-0" name="calificacion" placeholder="Ej. 85.5" required>
                                    </div>
                                    <div class="form-text small">La calificación mínima aprobatoria es 70.</div>
                                </div>

                                <button type="submit" class="btn btn-app-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-save2"></i>
                                    <span>Guardar Calificación</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="<?php echo ($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor') ? 'col-lg-8' : 'col-lg-12'; ?>">
                <div class="card card-custom">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-card-checklist" style="color: var(--app-primary);"></i>
                            <span>
                                Historial de Evaluaciones 
                                <?php if($_SESSION['rol'] == 'Profesor') echo "(Mis Alumnos Evaluados)"; ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <!-- FILTROS DE BÚSQUEDA -->
                        <form method="GET" action="calificaciones.php" class="p-3 mb-4 rounded-3 border" style="background-color: var(--app-light);">
                            <div class="row g-2 align-items-center">
                                <?php if($_SESSION['rol'] != 'Profesor') { ?>
                                    <div class="col-md">
                                        <select name="filtro_profesor" class="form-select form-select-sm">
                                            <option value="">Todos los Profesores</option>
                                            <?php if(isset($filtro_list_profes)) { 
                                                while($fp = $filtro_list_profes->fetch_assoc()) { 
                                                    $selected = ($filtro_profesor == $fp['IdProfesor']) ? 'selected' : '';
                                                    echo "<option value='".$fp['IdProfesor']."' $selected>".$fp['Nombre']." ".$fp['Apellidos']."</option>"; 
                                                } 
                                            } ?>
                                        </select>
                                    </div>
                                <?php } ?>
                                
                                <div class="col-md">
                                    <select name="filtro_materia" class="form-select form-select-sm">
                                        <option value="">Todas las Materias</option>
                                        <?php if(isset($filtro_list_materias)) { 
                                            while($fm = $filtro_list_materias->fetch_assoc()) { 
                                                $selected = ($filtro_materia == $fm['IdMateria']) ? 'selected' : '';
                                                echo "<option value='".$fm['IdMateria']."' $selected>".$fm['NombreMateria']."</option>"; 
                                            } 
                                        } ?>
                                    </select>
                                </div>

                                <div class="col-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-app-primary btn-sm px-3 rounded-pill">
                                        <i class="bi bi-search me-1"></i> Filtrar
                                    </button>
                                    <a href="calificaciones.php" class="btn btn-light btn-sm px-3 rounded-pill border">
                                        Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Matrícula</th>
                                        <th>Estudiante</th>
                                        <th>Materia</th>
                                        <th>Docente</th>
                                        <th class="text-center">Calificación</th>
                                        <th class="pe-3 text-end">Fecha</th>
                                        <?php if($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor') { ?>
                                            <th class="pe-3 text-end">Acción</th>
                                        <?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultado_calificaciones && $resultado_calificaciones->num_rows > 0) {
                                        while($row = $resultado_calificaciones->fetch_assoc()) { ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        <?php echo htmlspecialchars($row['Matricula']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark">
                                                    <?php echo htmlspecialchars($row['NombreAlumno'] . " " . $row['ApellidosAlumno']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge border" style="background-color: var(--app-light); color: var(--app-primary);">
                                                        <?php echo htmlspecialchars($row['NombreMateria']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-secondary small">
                                                    <?php echo htmlspecialchars($row['NombreProfesor'] . " " . $row['ApellidosProfesor']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php 
                                                        $n = floatval($row['Calificacion']);
                                                        $cls_n = ($n >= 70) ? 'badge-nota-aprobada' : 'badge-nota-reprobada';
                                                    ?>
                                                    <span class="badge <?php echo $cls_n; ?> px-2 py-1 fw-bold">
                                                        <?php echo $n; ?>
                                                    </span>
                                                </td>
                                                <td class="pe-3 text-end small text-muted">
                                                    <?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?>
                                                </td>
                                                <?php if($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor') { ?>
                                                    <td class="pe-3 text-end">
                                                        <a href="calificaciones.php?eliminar_calificacion=<?php echo $row['IdCalificacion']; ?>" 
                                                           class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0"
                                                           onclick="return confirm('¿Confirmas que deseas eliminar esta calificación?');"
                                                           title="Eliminar calificación">
                                                            <i class="bi bi-trash3"></i>
                                                        </a>
                                                    </td>
                                                <?php } ?>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="<?php echo ($_SESSION['rol'] == 'Admin' || $_SESSION['rol'] == 'Profesor') ? '7' : '6'; ?>" class="text-center py-4 text-muted">
                                                No se encontraron registros de calificaciones con los criterios seleccionados.
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
