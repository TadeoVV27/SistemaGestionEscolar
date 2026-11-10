<?php
// config.php - Cargador nativo y seguro de variables de entorno (.env)
// Permite leer claves secretas y credenciales sin exponerlas en el código fuente.

if (!function_exists('cargar_env')) {
    function cargar_env($archivo = __DIR__ . '/.env') {
        if (!file_exists($archivo)) {
            return false;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            // Ignorar comentarios y líneas vacías
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            if (str_contains($linea, '=')) {
                list($clave, $valor) = explode('=', $linea, 2);
                $clave = trim($clave);
                $valor = trim($valor);

                // Quitar comillas si están presentes
                $valor = trim($valor, '"\'');

                $_ENV[$clave] = $valor;
                $_SERVER[$clave] = $valor;
                putenv("$clave=$valor");
            }
        }
        return true;
    }
}

// Cargar el archivo .env si existe
cargar_env();
?>
