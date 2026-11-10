<?php
header('Content-Type: application/json');
require_once("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pregunta_usuario = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

    if (empty($pregunta_usuario)) {
        echo json_encode(["respuesta" => "Por favor escribe una pregunta."]);
        exit();
    }

    // Obtener la API Key protegida desde el entorno (.env)
    $api_key = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?? '';

    if (empty($api_key)) {
        echo json_encode(["respuesta" => "Error: La clave de la API de Gemini no está configurada en el archivo .env."]);
        exit();
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=" . $api_key;

    // Estructura oficial de Gemini separando la directiva de sistema (System Instruction)
    $data = [
        "system_instruction" => [
            "parts" => [
                ["text" => "Eres el asistente virtual del Sistema de Gestión Escolar. Tu única función es ayudar a alumnos y profesores con las reglas de ESTE sistema específico. Reglas absolutas: 1. Si preguntan por contraseñas, diles que la contraseña genérica de acceso inicial es '1234' o que deben pedirle al Administrador que se las restablezca. No menciones plataformas externas como Facebook o Google. 2. Recuerda que los alumnos solo consultan calificaciones y boletas, y los profesores solo evalúan las materias que el Administrador les asignó."]
            ]
        ],
        "contents" => [
            [
                "parts" => [
                    ["text" => $pregunta_usuario]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $resultado = json_decode($response, true);
        if (isset($resultado['candidates'][0]['content']['parts'][0]['text'])) {
            echo json_encode(["respuesta" => $resultado['candidates'][0]['content']['parts'][0]['text']]);
            exit();
        }
    }

    echo json_encode(["respuesta" => "Lo siento, la contraseña genérica de acceso es '1234'."]);
}
?>
