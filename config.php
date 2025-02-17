<?php

// Función para cargar variables de entorno desde un archivo .env
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die("Error: Archivo .env no encontrado.");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignorar comentarios
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}

// Cargar las variables de entorno
loadEnv(__DIR__ . '/.env');