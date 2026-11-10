<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['correo'])) {
    header("location: index.php");
    exit();
}

$rol = $_SESSION['rol'];
$correo = $_SESSION['correo'];

// ==========================================
// 1. CONSULTAS PARA EL ADMINISTRADOR
// ==========================================
if ($rol == 'Admin') {
    $total_alumnos = 0;
    $total_profesores = 0;
    $total_materias = 0;
    $total_asignaciones = 0;
    $total_calificaciones = 0;

    $res_al = $conexion->query("SELECT COUNT(*) AS total FROM Alumnos");
    if ($res_al) { $total_alumnos = $res_al->fetch_assoc()['total']; }

    $res_pr = $conexion->query("SELECT COUNT(*) AS total FROM Profesores");
    if ($res_pr) { $total_profesores = $res_pr->fetch_assoc()['total']; }

    $res_mat = $conexion->query("SELECT COUNT(*) AS total FROM Materias");
    if ($res_mat) { $total_materias = $res_mat->fetch_assoc()['total']; }

    $res_asig = $conexion->query("SELECT COUNT(*) AS total FROM Asignaciones");
    if ($res_asig) { $total_asignaciones = $res_asig->fetch_assoc()['total']; }

    $res_cal = $conexion->query("SELECT COUNT(*) AS total FROM Calificaciones");
    if ($res_cal) { $total_calificaciones = $res_cal->fetch_assoc()['total']; }

    $query_recientes = "SELECT c.Calificacion, c.FechaRegistro, 
                               a.Nombre AS AlumnoNom, a.Apellidos AS AlumnoApe, a.Matricula, 
                               m.NombreMateria, 
                               p.Nombre AS ProfeNom, p.Apellidos AS ProfeApe 
                        FROM Calificaciones c 
                        JOIN Alumnos a ON c.Matricula = a.Matricula 
                        JOIN Materias m ON c.IdMateria = m.IdMateria 
                        JOIN Profesores p ON c.IdProfesor = p.IdProfesor 
                        ORDER BY c.FechaRegistro DESC LIMIT 5";
    $ultimas_calificaciones = $conexion->query($query_recientes);
}

// ==========================================
// 2. CONSULTAS PARA EL PROFESOR (SENTENCIAS PREPARADAS)
// ==========================================
if ($rol == 'Profesor') {
    $id_profesor = null;
    $nombre_profesor = "Profesor";
    $num_empleado = "N/A";
    $total_mis_materias = 0;
    $total_mis_evaluados = 0;
    $promedio_grupo = 0;

    $stmt_profe = $conexion->prepare("SELECT p.IdProfesor, p.Nombre, p.Apellidos, p.NumeroEmpleado 
                                     FROM Profesores p 
                                     JOIN Usuarios u ON p.IdUsuario = u.IdUsuario 
                                     WHERE u.Correo = ?");
    if ($stmt_profe) {
        $stmt_profe->bind_param("s", $correo);
        $stmt_profe->execute();
        $query_profe = $stmt_profe->get_result();

        if ($query_profe && $query_profe->num_rows > 0) {
            $datos_profe = $query_profe->fetch_assoc();
            $id_profesor = $datos_profe['IdProfesor'];
            $nombre_profesor = $datos_profe['Nombre'] . " " . $datos_profe['Apellidos'];
            $num_empleado = $datos_profe['NumeroEmpleado'];
            $stmt_profe->close();

            // Métricas del docente con sentencias preparadas
            $stmt_mat = $conexion->prepare("SELECT COUNT(*) AS total FROM Asignaciones WHERE IdProfesor = ?");
            if ($stmt_mat) {
                $stmt_mat->bind_param("i", $id_profesor);
                $stmt_mat->execute();
                $q_mat = $stmt_mat->get_result();
                if ($q_mat) { $total_mis_materias = $q_mat->fetch_assoc()['total']; }
                $stmt_mat->close();
            }

            $stmt_ev = $conexion->prepare("SELECT COUNT(*) AS total, AVG(Calificacion) AS prom FROM Calificaciones WHERE IdProfesor = ?");
            if ($stmt_ev) {
                $stmt_ev->bind_param("i", $id_profesor);
                $stmt_ev->execute();
                $q_ev = $stmt_ev->get_result();
                if ($q_ev) {
                    $row_ev = $q_ev->fetch_assoc();
                    $total_mis_evaluados = $row_ev['total'];
                    $promedio_grupo = $row_ev['prom'] ? round(floatval($row_ev['prom']), 1) : 0;
                }
                $stmt_ev->close();
            }

            // Lista de materias asignadas a este profesor
            $stmt_mprofe = $conexion->prepare("SELECT m.IdMateria, m.NombreMateria, m.Creditos,
                                                (SELECT COUNT(*) FROM Calificaciones c WHERE c.IdMateria = m.IdMateria AND c.IdProfesor = ?) AS Evaluados
                                         FROM Materias m 
                                         JOIN Asignaciones a ON m.IdMateria = a.IdMateria 
                                         WHERE a.IdProfesor = ?");
            if ($stmt_mprofe) {
                $stmt_mprofe->bind_param("ii", $id_profesor, $id_profesor);
                $stmt_mprofe->execute();
                $materias_asignadas = $stmt_mprofe->get_result();
            }

            // Últimas 5 notas asentadas por este profesor
            $stmt_mis_notas = $conexion->prepare("SELECT c.Calificacion, c.FechaRegistro, 
                                           a.Matricula, a.Nombre AS AlumnoNom, a.Apellidos AS AlumnoApe, 
                                           m.NombreMateria 
                                    FROM Calificaciones c 
                                    JOIN Alumnos a ON c.Matricula = a.Matricula 
                                    JOIN Materias m ON c.IdMateria = m.IdMateria 
                                    WHERE c.IdProfesor = ? 
                                    ORDER BY c.FechaRegistro DESC LIMIT 5");
            if ($stmt_mis_notas) {
                $stmt_mis_notas->bind_param("i", $id_profesor);
                $stmt_mis_notas->execute();
                $ultimas_evaluaciones_profe = $stmt_mis_notas->get_result();
            }
        }
    }
}

// ==========================================
// 3. CONSULTAS PARA EL ALUMNO (SENTENCIAS PREPARADAS)
// ==========================================
if ($rol == 'Alumno') {
    $matricula = "N/A";
    $nombre_alumno = "Estudiante";
    $promedio_alumno = 0;
    $materias_cursadas = 0;
    $materias_aprobadas = 0;
    $materias_reprobadas = 0;

    $stmt_alu = $conexion->prepare("SELECT a.Matricula, a.Nombre, a.Apellidos 
                                  FROM Alumnos a 
                                  JOIN Usuarios u ON a.IdUsuario = u.IdUsuario 
                                  WHERE u.Correo = ?");
    if ($stmt_alu) {
        $stmt_alu->bind_param("s", $correo);
        $stmt_alu->execute();
        $query_alumno = $stmt_alu->get_result();

        if ($query_alumno && $query_alumno->num_rows > 0) {
            $datos_alumno = $query_alumno->fetch_assoc();
            $matricula = $datos_alumno['Matricula'];
            $nombre_alumno = $datos_alumno['Nombre'] . " " . $datos_alumno['Apellidos'];
            $stmt_alu->close();

            // Métricas académicas con sentencias preparadas
            $stmt_prom = $conexion->prepare("SELECT AVG(Calificacion) AS prom, COUNT(*) AS total FROM Calificaciones WHERE Matricula = ?");
            if ($stmt_prom) {
                $stmt_prom->bind_param("s", $matricula);
                $stmt_prom->execute();
                $q_prom = $stmt_prom->get_result();
                if ($q_prom) {
                    $row_prom = $q_prom->fetch_assoc();
                    $promedio_alumno = $row_prom['prom'] ? round(floatval($row_prom['prom']), 2) : 0;
                    $materias_cursadas = $row_prom['total'];
                }
                $stmt_prom->close();
            }

            $stmt_aprob = $conexion->prepare("SELECT COUNT(*) AS total FROM Calificaciones WHERE Matricula = ? AND Calificacion >= 70");
            if ($stmt_aprob) {
                $stmt_aprob->bind_param("s", $matricula);
                $stmt_aprob->execute();
                $q_aprob = $stmt_aprob->get_result();
                if ($q_aprob) { $materias_aprobadas = $q_aprob->fetch_assoc()['total']; }
                $stmt_aprob->close();
            }

            $stmt_rep = $conexion->prepare("SELECT COUNT(*) AS total FROM Calificaciones WHERE Matricula = ? AND Calificacion < 70");
            if ($stmt_rep) {
                $stmt_rep->bind_param("s", $matricula);
                $stmt_rep->execute();
                $q_rep = $stmt_rep->get_result();
                if ($q_rep) { $materias_reprobadas = $q_rep->fetch_assoc()['total']; }
                $stmt_rep->close();
            }

            // Kardex completo del alumno
            $stmt_kardex = $conexion->prepare("SELECT m.NombreMateria, m.Creditos, 
                                        p.Nombre AS ProfeNom, p.Apellidos AS ProfeApe, 
                                        c.Calificacion, c.FechaRegistro 
                                 FROM Calificaciones c 
                                 JOIN Materias m ON c.IdMateria = m.IdMateria 
                                 JOIN Profesores p ON c.IdProfesor = p.IdProfesor 
                                 WHERE c.Matricula = ? 
                                 ORDER BY c.FechaRegistro DESC");
            if ($stmt_kardex) {
                $stmt_kardex->bind_param("s", $matricula);
                $stmt_kardex->execute();
                $kardex = $stmt_kardex->get_result();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Sistema de Gestión Escolar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PALETA DE COLORES PERSONALIZADA -->
    <!-- #29204F (Oscuro) | #6D4ACF (Primario) | #A78BFA (Acento) | #EEE8FF (Claro) | #FFFFFF (Blanco) -->
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

        /* Navbar */
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

        /* Hero / Bienvenida */
        .hero-banner {
            background: linear-gradient(135deg, var(--app-white) 0%, var(--app-light) 100%);
            border-left: 6px solid var(--app-primary);
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(41, 32, 79, 0.05);
        }

        /* Tarjetas de estadísticas */
        .stat-card {
            background: var(--app-white);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(41, 32, 79, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 22px rgba(109, 74, 207, 0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: var(--app-light);
            color: var(--app-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* Tarjetas de módulos */
        .module-card {
            background: var(--app-white);
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(41, 32, 79, 0.04);
            transition: all 0.25s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(109, 74, 207, 0.15);
            border-color: var(--app-primary);
        }

        .module-icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background-color: var(--app-light);
            color: var(--app-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }

        /* Botones personalizados */
        .btn-app-primary {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
            color: var(--app-white);
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-app-primary:hover, .btn-app-primary:focus {
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

        .badge-app-pill {
            background-color: var(--app-light);
            color: var(--app-primary);
            font-weight: 700;
            border: 1px solid var(--app-accent);
        }

        /* Tablas */
        .card-table-custom {
            border: 1px solid rgba(167, 139, 250, 0.2);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(41, 32, 79, 0.04);
        }

        .table-custom thead {
            background-color: var(--app-light);
            color: var(--app-dark);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="text-end d-none d-md-block text-white">
                    <div class="fw-semibold small"><?php echo htmlspecialchars($correo); ?></div>
                    <span class="badge badge-app-pill px-2 py-1"><?php echo htmlspecialchars($rol); ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1 px-3 py-1 rounded-pill">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">

        <?php if ($rol == 'Admin') { ?>

            <!-- ============================================== -->
            <!-- 1. DASHBOARD DEL ADMINISTRADOR: GESTIÓN TOTAL  -->
            <!-- ============================================== -->

            <!-- HERO DE BIENVENIDA -->
            <div class="hero-banner p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1" style="color: var(--app-dark);">
                            <i class="bi bi-shield-lock-fill me-2" style="color: var(--app-primary);"></i>Dashboard de Administración
                        </h2>
                        <p class="text-muted mb-0 small">
                            Gestión total del sistema: usuarios, oferta académica, docentes y auditoría de calificaciones.
                        </p>
                    </div>
                    <div class="text-md-end">
                        <span class="badge bg-white text-dark border px-3 py-2 shadow-sm rounded-pill">
                            <i class="bi bi-calendar3 me-1 text-primary"></i> <?php echo date("d/m/Y"); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- TARJETAS DE MÉTRICAS (KPIs) -->
            <div class="row g-3 mb-4">
                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Alumnos</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_alumnos; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Profesores</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_profesores; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Materias</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_materias; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-book-half"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Asignaciones</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_asignaciones; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-diagram-3-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-4 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Calificaciones</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_calificaciones; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-award-fill"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE MÓDULOS DE GESTIÓN TOTAL -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0" style="color: var(--app-dark);">
                    <i class="bi bi-grid-fill me-2" style="color: var(--app-primary);"></i>Módulos de Gestión
                </h5>
                <small class="text-muted">Accede a las herramientas de control institucional</small>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="module-card p-4">
                        <div class="module-icon-circle"><i class="bi bi-mortarboard"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--app-dark);">Alumnos</h5>
                        <p class="text-muted small flex-grow-1">
                            Alta de estudiantes, asignación automática de correo y consulta de matrículas.
                        </p>
                        <a href="alumnos.php" class="btn btn-app-outline w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <span>Gestionar Alumnos</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="module-card p-4">
                        <div class="module-icon-circle"><i class="bi bi-person-workspace"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--app-dark);">Profesores</h5>
                        <p class="text-muted small flex-grow-1">
                            Registro de docentes, administración de número de empleado y generación de cuentas.
                        </p>
                        <a href="profesores.php" class="btn btn-app-outline w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <span>Gestionar Docentes</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="module-card p-4">
                        <div class="module-icon-circle"><i class="bi bi-journal-text"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--app-dark);">Materias</h5>
                        <p class="text-muted small flex-grow-1">
                            Catálogo general de asignaturas, créditos curriculares y plan de estudio escolar.
                        </p>
                        <a href="materias.php" class="btn btn-app-outline w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <span>Gestionar Materias</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="module-card p-4">
                        <div class="module-icon-circle"><i class="bi bi-link-45deg"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--app-dark);">Asignaciones</h5>
                        <p class="text-muted small flex-grow-1">
                            Vinculación entre docentes y materias para asignar cátedras de evaluación.
                        </p>
                        <a href="asignaciones.php" class="btn btn-app-outline w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <span>Gestionar Cursos</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 col-xl">
                    <div class="module-card p-4">
                        <div class="module-icon-circle"><i class="bi bi-card-checklist"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--app-dark);">Calificaciones</h5>
                        <p class="text-muted small flex-grow-1">
                            Supervisión y captura de notas académicas, auditoría de evaluaciones y kardex.
                        </p>
                        <a href="calificaciones.php" class="btn btn-app-primary w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                            <span>Historial y Captura</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- REGISTRO RECIENTE -->
            <div class="card card-table-custom bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0" style="color: var(--app-dark);">Últimas Calificaciones Asentadas</h6>
                    </div>
                    <a href="calificaciones.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--app-primary);">Ver todas &rarr;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-custom">
                            <thead>
                                <tr>
                                    <th class="ps-4">Estudiante</th>
                                    <th>Matrícula</th>
                                    <th>Materia</th>
                                    <th>Profesor</th>
                                    <th class="text-center">Calificación</th>
                                    <th class="pe-4 text-end">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($ultimas_calificaciones && $ultimas_calificaciones->num_rows > 0) { ?>
                                    <?php while($row = $ultimas_calificaciones->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark">
                                                <i class="bi bi-person me-1 text-secondary"></i>
                                                <?php echo htmlspecialchars($row['AlumnoNom'] . ' ' . $row['AlumnoApe']); ?>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($row['Matricula']); ?></code></td>
                                            <td>
                                                <span class="badge border" style="background-color: var(--app-light); color: var(--app-primary);">
                                                    <?php echo htmlspecialchars($row['NombreMateria']); ?>
                                                </span>
                                            </td>
                                            <td class="text-secondary small">
                                                <?php echo htmlspecialchars($row['ProfeNom'] . ' ' . $row['ProfeApe']); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                    $nota = floatval($row['Calificacion']);
                                                    $claseNota = ($nota >= 70) ? 'badge-nota-aprobada' : 'badge-nota-reprobada';
                                                ?>
                                                <span class="badge <?php echo $claseNota; ?> px-2 py-1">
                                                    <?php echo $nota; ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end small text-muted">
                                                <?php echo date("d/m/Y H:i", strtotime($row['FechaRegistro'])); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted small">
                                            <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                            Aún no hay calificaciones registradas en el sistema.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php } else if ($rol == 'Profesor') { ?>

            <!-- ============================================== -->
            <!-- 2. DASHBOARD DEL PROFESOR: MÓDULOS EVALUACIÓN  -->
            <!-- ============================================== -->

            <!-- HERO DEL DOCENTE -->
            <div class="hero-banner p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon-circle mb-0" style="width: 60px; height: 60px;">
                            <i class="bi bi-person-video3 fs-2"></i>
                        </div>
                        <div>
                            <span class="badge badge-app-pill mb-1">Docente</span>
                            <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">
                                ¡Bienvenido, <?php echo htmlspecialchars($nombre_profesor); ?>!
                            </h2>
                            <small class="text-muted">No. Empleado: <strong><?php echo htmlspecialchars($num_empleado); ?></strong> | <?php echo htmlspecialchars($correo); ?></small>
                        </div>
                    </div>
                    <div>
                        <a href="calificaciones.php" class="btn btn-app-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm">
                            <i class="bi bi-pencil-square"></i>
                            <span>Evaluar Alumnos</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- TARJETAS DE MÉTRICAS DEL PROFESOR -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Materias a tu Cargo</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_mis_materias; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-journal-check"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Evaluaciones Registradas</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $total_mis_evaluados; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-card-checklist"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Promedio Grupal Asentado</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $promedio_grupo; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- LISTA DE MATERIAS ASIGNADAS -->
                <div class="col-lg-6">
                    <div class="card card-table-custom bg-white h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-book-half fs-5" style="color: var(--app-primary);"></i>
                                <h6 class="fw-bold mb-0" style="color: var(--app-dark);">Tus Materias Asignadas</h6>
                            </div>
                            <span class="badge badge-app-pill"><?php echo $total_mis_materias; ?> asignadas</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-custom">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Materia</th>
                                            <th class="text-center">Créditos</th>
                                            <th class="text-center">Evaluados</th>
                                            <th class="pe-4 text-end">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($materias_asignadas && $materias_asignadas->num_rows > 0) { ?>
                                            <?php while($m = $materias_asignadas->fetch_assoc()) { ?>
                                                <tr>
                                                    <td class="ps-4 fw-semibold text-dark">
                                                        <?php echo htmlspecialchars($m['NombreMateria']); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($m['Creditos']); ?> pts</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-app-pill"><?php echo $m['Evaluados']; ?> notas</span>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <a href="calificaciones.php?filtro_materia=<?php echo $m['IdMateria']; ?>" class="btn btn-sm btn-app-outline rounded-pill px-3">
                                                            Evaluar
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted small">
                                                    No tienes materias asignadas actualmente. Pide al Administrador que vincule tus asignaturas en el módulo de Asignaciones.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ÚLTIMAS EVALUACIONES DEL PROFESOR -->
                <div class="col-lg-6">
                    <div class="card card-table-custom bg-white h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history fs-5" style="color: var(--app-primary);"></i>
                                <h6 class="fw-bold mb-0" style="color: var(--app-dark);">Últimas Notas Asentadas</h6>
                            </div>
                            <a href="calificaciones.php" class="btn btn-sm btn-link text-decoration-none" style="color: var(--app-primary);">Ver todas &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-custom">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Alumno</th>
                                            <th>Materia</th>
                                            <th class="text-center">Nota</th>
                                            <th class="pe-4 text-end">Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($ultimas_evaluaciones_profe && $ultimas_evaluaciones_profe->num_rows > 0) { ?>
                                            <?php while($row_p = $ultimas_evaluaciones_profe->fetch_assoc()) { ?>
                                                <tr>
                                                    <td class="ps-4 fw-semibold text-dark">
                                                        <?php echo htmlspecialchars($row_p['AlumnoNom'] . ' ' . $row_p['AlumnoApe']); ?>
                                                        <div class="small text-muted font-monospace"><?php echo htmlspecialchars($row_p['Matricula']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge border" style="background-color: var(--app-light); color: var(--app-primary);">
                                                            <?php echo htmlspecialchars($row_p['NombreMateria']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php 
                                                            $cal = floatval($row_p['Calificacion']);
                                                            $cls = ($cal >= 70) ? 'badge-nota-aprobada' : 'badge-nota-reprobada';
                                                        ?>
                                                        <span class="badge <?php echo $cls; ?> px-2 py-1"><?php echo $cal; ?></span>
                                                    </td>
                                                    <td class="pe-4 text-end small text-muted">
                                                        <?php echo date("d/m/Y", strtotime($row_p['FechaRegistro'])); ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted small">
                                                    Aún no has registrado calificaciones este semestre.
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

        <?php } else if ($rol == 'Alumno') { ?>

            <!-- ============================================== -->
            <!-- 3. DASHBOARD DEL ALUMNO: BOLETAS Y KARDEX      -->
            <!-- ============================================== -->

            <!-- HERO DEL ALUMNO -->
            <div class="hero-banner p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon-circle mb-0" style="width: 60px; height: 60px;">
                            <i class="bi bi-mortarboard fs-2"></i>
                        </div>
                        <div>
                            <span class="badge badge-app-pill mb-1">Estudiante</span>
                            <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">
                                ¡Bienvenido, <?php echo htmlspecialchars($nombre_alumno); ?>!
                            </h2>
                            <small class="text-muted">Matrícula: <strong><?php echo htmlspecialchars($matricula); ?></strong> | <?php echo htmlspecialchars($correo); ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="boleta.php" target="_blank" class="btn btn-app-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <span>Descargar Boleta Oficial (PDF)</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- TARJETAS DE MÉTRICAS DEL ESTUDIANTE -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Promedio General</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-primary);"><?php echo $promedio_alumno; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Materias Evaluadas</span>
                                <h3 class="fw-bold mb-0 mt-1" style="color: var(--app-dark);"><?php echo $materias_cursadas; ?></h3>
                            </div>
                            <div class="stat-icon"><i class="bi bi-book-half"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Materias Aprobadas</span>
                                <h3 class="fw-bold mb-0 mt-1 text-success"><?php echo $materias_aprobadas; ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: #d1e7dd; color: #0f5132;"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">Por Acreditar</span>
                                <h3 class="fw-bold mb-0 mt-1 text-danger"><?php echo $materias_reprobadas; ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: #f8d7da; color: #842029;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DEL KARDEX ESCOLAR EN VIVO -->
            <div class="card card-table-custom bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-mortarboard-fill fs-5" style="color: var(--app-primary);"></i>
                        <h6 class="fw-bold mb-0" style="color: var(--app-dark);">Historial Académico (Kardex)</h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="boleta.php" target="_blank" class="btn btn-sm btn-app-outline rounded-pill px-3">
                            <i class="bi bi-printer me-1"></i> Imprimir Boleta
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-custom">
                            <thead>
                                <tr>
                                    <th class="ps-4">Materia</th>
                                    <th class="text-center">Créditos</th>
                                    <th>Docente Evaluador</th>
                                    <th class="text-center">Calificación</th>
                                    <th class="text-center">Estado</th>
                                    <th class="pe-4 text-end">Fecha de Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($kardex && $kardex->num_rows > 0) { ?>
                                    <?php while($k = $kardex->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark">
                                                <i class="bi bi-journal-check me-2" style="color: var(--app-accent);"></i>
                                                <?php echo htmlspecialchars($k['NombreMateria']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($k['Creditos']); ?> pts</span>
                                            </td>
                                            <td class="text-secondary small">
                                                <?php echo htmlspecialchars($k['ProfeNom'] . ' ' . $k['ProfeApe']); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                    $nota_k = floatval($k['Calificacion']);
                                                    $cls_k = ($nota_k >= 70) ? 'badge-nota-aprobada' : 'badge-nota-reprobada';
                                                ?>
                                                <span class="badge <?php echo $cls_k; ?> px-2 py-1 fs-6">
                                                    <?php echo $nota_k; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($nota_k >= 70) { ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Acreditada</span>
                                                <?php } else { ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">No Acreditada</span>
                                                <?php } ?>
                                            </td>
                                            <td class="pe-4 text-end small text-muted">
                                                <?php echo date("d/m/Y H:i", strtotime($k['FechaRegistro'])); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-file-earmark-x fs-2 d-block mb-2 text-secondary"></i>
                                            Aún no cuentas con calificaciones asentadas en tu historial.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php } ?>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
