<?php
session_start();
include("conexion.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    // 1. Consulta con SENTENCIA PREPARADA (Blindaje contra Inyección SQL)
    $stmt = $conexion->prepare("SELECT IdUsuario, Correo, Password, Rol FROM Usuarios WHERE Correo = ?");
    if ($stmt) {
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $row = $resultado->fetch_assoc();
            $hash_almacenado = $row['Password'];

            // 2. Verificación de contraseña:
            // Soporta tanto contraseñas encriptadas con password_hash() como texto plano anterior (migración automática)
            $password_valida = false;

            if (password_verify($password, $hash_almacenado)) {
                $password_valida = true;
            } else if ($password === $hash_almacenado) {
                // Si la contraseña aún estaba en texto plano, la validamos y la encriptamos de inmediato
                $password_valida = true;
                $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_up = $conexion->prepare("UPDATE Usuarios SET Password = ? WHERE IdUsuario = ?");
                if ($stmt_up) {
                    $stmt_up->bind_param("si", $nuevo_hash, $row['IdUsuario']);
                    $stmt_up->execute();
                    $stmt_up->close();
                }
            }

            if ($password_valida) {
                $_SESSION['correo'] = $row['Correo'];
                $_SESSION['rol'] = $row['Rol'];

                $stmt->close();
                header("location: panel.php");
                exit();
            } else {
                $error = "Correo o contraseña incorrectos.";
            }
        } else {
            $error = "Correo o contraseña incorrectos.";
        }
        $stmt->close();
    } else {
        $error = "Error al procesar la solicitud.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Gestión Escolar</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PALETA SELECCIONADA -->
    <style>
        :root {
            --app-dark: #29204F;
            --app-primary: #6D4ACF;
            --app-primary-hover: #5939b8;
            --app-accent: #A78BFA;
            --app-light: #EEE8FF;
            --app-white: #FFFFFF;
            --app-bg: #F8F7FD;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--app-bg);
            color: #2D2845;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            border: 1px solid rgba(167, 139, 250, 0.25);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(41, 32, 79, 0.08);
            background: var(--app-white);
        }

        .login-header {
            background-color: var(--app-dark);
            border-bottom: 3px solid var(--app-accent);
            padding: 1.75rem 1.5rem;
            text-align: center;
            color: var(--app-white);
        }

        .btn-app-primary {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
            color: var(--app-white);
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 10px;
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

        /* Widget de IA */
        .card-ia {
            border: 1px solid var(--app-accent);
            border-radius: 14px;
            overflow: hidden;
            background-color: var(--app-white);
        }

        .card-ia-header {
            background-color: var(--app-light);
            color: var(--app-dark);
            font-weight: 600;
            padding: 0.5rem 0.85rem;
        }

        #chat-container {
            max-height: 170px;
            overflow-y: auto;
            background: #FAFAFE;
            font-size: 0.85rem;
        }

        .bubble-user {
            background-color: var(--app-light);
            color: var(--app-dark);
            padding: 6px 12px;
            border-radius: 12px 12px 2px 12px;
            display: inline-block;
            max-width: 85%;
        }

        .bubble-ia {
            background-color: #ffffff;
            border: 1px solid rgba(167, 139, 250, 0.3);
            color: #2D2845;
            padding: 6px 12px;
            border-radius: 12px 12px 12px 2px;
            display: inline-block;
            max-width: 85%;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-mortarboard-fill fs-2 d-block mb-1" style="color: var(--app-accent);"></i>
                        <h4 class="fw-bold mb-0">Sistema de Gestión Escolar</h4>
                        <small style="color: var(--app-accent); font-weight: 500;">Control Académico Institucional</small>
                    </div>

                    <div class="card-body p-4">
                        <?php if(!empty($error)) { ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                        <?php } ?>

                        <form action="index.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Correo Electrónico Institucional</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-1" name="correo" placeholder="ejemplo@escolar.edu" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-1" name="password" placeholder="Tu contraseña" required>
                                </div>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-app-primary">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar al Sistema
                                </button>
                            </div>
                        </form>

                        <hr class="text-muted my-3 opacity-25">

                        <!-- WIDGET DE SOPORTE IA -->
                        <div class="card-ia shadow-sm">
                            <div class="card-ia-header d-flex justify-content-between align-items-center">
                                <small class="d-flex align-items-center gap-1">
                                    <i class="bi bi-robot" style="color: var(--app-primary);"></i>
                                    <span>Asistente de Soporte IA</span>
                                </small>
                                <span class="badge bg-white text-dark border small" style="font-size: 0.7rem;">Gemini</span>
                            </div>
                            <div class="card-body p-2">
                                <div id="chat-container" class="p-2 mb-2 rounded border">
                                    <div class="text-start mb-2">
                                        <div class="bubble-ia">
                                            ¡Hola! Soy tu asistente virtual. ¿Tienes dudas sobre tu acceso o contraseñas? Pregúntame aquí.
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="pregunta-ia" class="form-control" placeholder="Ej. Olvidé mi contraseña..." onkeydown="if(event.key === 'Enter') consultarIA()">
                                    <button class="btn btn-app-primary" type="button" onclick="consultarIA()">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- FIN WIDGET IA -->

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de JavaScript para consultar IA sin recargar -->
    <script>
        function consultarIA() {
            const input = document.getElementById('pregunta-ia');
            const chatBox = document.getElementById('chat-container');
            const pregunta = input.value.trim();

            if (pregunta === "") return;

            chatBox.innerHTML += `
                <div class='text-end mb-2'>
                    <div class='bubble-user text-start'>${pregunta}</div>
                </div>`;
            input.value = "";
            chatBox.scrollTop = chatBox.scrollHeight;

            const loaderId = 'loader-' + Date.now();
            chatBox.innerHTML += `
                <div id='${loaderId}' class='text-start mb-2'>
                    <div class='bubble-ia text-muted fst-italic'>
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Escribiendo respuesta...
                    </div>
                </div>`;
            chatBox.scrollTop = chatBox.scrollHeight;

            const formData = new FormData();
            formData.append('mensaje', pregunta);

            fetch('ia_soporte.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const loader = document.getElementById(loaderId);
                if (loader) loader.remove();
                chatBox.innerHTML += `
                    <div class='text-start mb-2'>
                        <div class='bubble-ia'>${data.respuesta}</div>
                    </div>`;
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                const loader = document.getElementById(loaderId);
                if (loader) loader.remove();
                chatBox.innerHTML += `
                    <div class='text-start mb-2'>
                        <div class='bubble-ia text-danger'>
                            <i class="bi bi-exclamation-circle me-1"></i> No se pudo conectar con el asistente.
                        </div>
                    </div>`;
            });
        }
    </script>
</body>
</html>
