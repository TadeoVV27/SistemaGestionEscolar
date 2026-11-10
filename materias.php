<?php
session_start();
include("conexion.php");

// Candado: Solo Administrador
if (!isset($_SESSION['correo']) || $_SESSION['rol'] != 'Admin') {
    header("location: panel.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "info";

// PROCESAR ELIMINACIÓN DE MATERIA (SENTENCIAS PREPARADAS)
if (isset($_GET['eliminar_materia'])) {
    $id_mat_del = intval($_GET['eliminar_materia']);

    $stmt_chk = $conexion->prepare("SELECT NombreMateria FROM materias WHERE IdMateria = ?");
    if ($stmt_chk) {
        $stmt_chk->bind_param("i", $id_mat_del);
        $stmt_chk->execute();
        $res_chk = $stmt_chk->get_result();

        if ($res_chk && $res_chk->num_rows > 0) {
            $nombre_mat = $res_chk->fetch_assoc()['NombreMateria'];
            $stmt_chk->close();

            // 1. Eliminar asignaciones vinculadas a esta materia
            $stmt_asig = $conexion->prepare("DELETE FROM asignaciones WHERE IdMateria = ?");
            if ($stmt_asig) {
                $stmt_asig->bind_param("i", $id_mat_del);
                $stmt_asig->execute();
                $stmt_asig->close();
            }

            // 2. Eliminar calificaciones vinculadas a esta materia
            $stmt_cal = $conexion->prepare("DELETE FROM calificaciones WHERE IdMateria = ?");
            if ($stmt_cal) {
                $stmt_cal->bind_param("i", $id_mat_del);
                $stmt_cal->execute();
                $stmt_cal->close();
            }

            // 3. Eliminar la materia
            $stmt_del = $conexion->prepare("DELETE FROM materias WHERE IdMateria = ?");
            if ($stmt_del) {
                $stmt_del->bind_param("i", $id_mat_del);
                if ($stmt_del->execute()) {
                    $mensaje = "La materia <b>$nombre_mat</b> ha sido eliminada del catálogo escolar junto con sus asignaciones y evaluaciones asociadas.";
                    $tipo_alerta = "warning";
                } else {
                    $mensaje = "Error al eliminar materia: " . $stmt_del->error;
                    $tipo_alerta = "danger";
                }
                $stmt_del->close();
            }
        } else {
            $stmt_chk->close();
            $mensaje = "No se encontró la materia especificada para eliminar.";
            $tipo_alerta = "danger";
        }
    }
}

// PROCESAR REGISTRO DE MATERIA (SENTENCIAS PREPARADAS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_materia = trim($_POST['nombre_materia']);
    $creditos = intval($_POST['creditos']);

    $stmt_m = $conexion->prepare("SELECT NombreMateria FROM materias WHERE NombreMateria = ?");
    if ($stmt_m) {
        $stmt_m->bind_param("s", $nombre_materia);
        $stmt_m->execute();
        $res_m = $stmt_m->get_result();

        if ($res_m && $res_m->num_rows > 0) {
            $mensaje = "Ya existe una materia registrada con el nombre <b>$nombre_materia</b>.";
            $tipo_alerta = "warning";
            $stmt_m->close();
        } else {
            $stmt_m->close();

            $stmt_ins = $conexion->prepare("INSERT INTO materias (NombreMateria, Creditos) VALUES (?, ?)");
            if ($stmt_ins) {
                $stmt_ins->bind_param("si", $nombre_materia, $creditos);
                if ($stmt_ins->execute()) {
                    $mensaje = "¡Materia <b>$nombre_materia</b> registrada con éxito en el catálogo curricular!";
                    $tipo_alerta = "success";
                } else {
                    $mensaje = "Error al registrar la materia: " . $stmt_ins->error;
                    $tipo_alerta = "danger";
                }
                $stmt_ins->close();
            }
        }
    }
}

$resultado_materias = $conexion->query("SELECT * FROM materias ORDER BY NombreMateria ASC");
$total_materias = ($resultado_materias) ? $resultado_materias->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Materias - Sistema de Gestión Escolar</title>
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
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold mb-0" style="color: var(--app-dark);">Gestión de Materias</h2>
                        <small class="text-muted">Catálogo oficial de asignaturas curriculares, créditos y bajas protegidas.</small>
                    </div>
                </div>
                <div>
                    <span class="badge badge-app-pill px-3 py-2 fs-6">
                        <i class="bi bi-book me-1"></i> <?php echo $total_materias; ?> Materias en Catálogo
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
                        <i class="bi bi-plus-circle-fill" style="color: var(--app-primary);"></i>
                        <span>Registrar Nueva Materia</span>
                    </div>
                    <div class="card-body p-4">
                        <form action="materias.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Nombre de la Materia</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-book"></i></span>
                                    <input type="text" class="form-control border-start-0" name="nombre_materia" placeholder="Ej. Matemáticas I" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Créditos Curriculares</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-award"></i></span>
                                    <input type="number" class="form-control border-start-0" name="creditos" min="1" max="20" placeholder="Ej. 4" required>
                                </div>
                                <div class="form-text small">Valor crediticio oficial asignado a la asignatura.</div>
                            </div>

                            <button type="submit" class="btn btn-app-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-shield-check"></i>
                                <span>Guardar Materia Segura</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLA DE MATERIAS REGISTRADAS -->
            <div class="col-lg-8">
                <div class="card card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-list-check" style="color: var(--app-primary);"></i>
                            <span>Catálogo Curricular</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Clave ID</th>
                                        <th>Nombre de la Asignatura</th>
                                        <th class="text-center">Créditos</th>
                                        <th class="pe-4 text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($resultado_materias && $resultado_materias->num_rows > 0) { ?>
                                        <?php while($row = $resultado_materias->fetch_assoc()) { ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1">
                                                        #<?php echo htmlspecialchars($row['IdMateria']); ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-dark">
                                                    <i class="bi bi-journal-bookmark me-1" style="color: var(--app-primary);"></i>
                                                    <?php echo htmlspecialchars($row['NombreMateria']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-app-pill px-3 py-1">
                                                        <?php echo htmlspecialchars($row['Creditos']); ?> pts
                                                    </span>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="materias.php?eliminar_materia=<?php echo $row['IdMateria']; ?>" 
                                                       class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                                       onclick="return confirm('¿Confirmas que deseas eliminar la materia <?php echo htmlspecialchars($row['NombreMateria']); ?>? También se eliminarán sus asignaciones y evaluaciones asociadas.');">
                                                        <i class="bi bi-trash3 me-1"></i> Quitar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                No hay materias registradas actualmente.
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
