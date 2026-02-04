<?php
/**
 * Script para servir archivos Unity con headers correctos
 * Este script maneja los headers Content-Encoding para archivos Brotli (.br)
 */

// Habilitar logging para debugging (desactivar en producción)
$debug = false; // Cambiar a true para debugging

// Obtener la ruta del archivo solicitado
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Remover /unity-build/serve.php del path
$path = str_replace('/unity-build/serve.php', '', $path);
$path = ltrim($path, '/');

if ($debug) {
    error_log("serve.php - REQUEST_URI: " . $requestUri);
    error_log("serve.php - Parsed path: " . $path);
}

// Si no hay path, redirigir al index.html
if (empty($path) || $path === 'unity-build' || $path === 'unity-build/') {
    $path = 'index.html';
}

// Construir la ruta completa del archivo
$filePath = __DIR__ . '/' . $path;

// Verificar que el archivo existe y está dentro del directorio unity-build
$realPath = realpath($filePath);
$basePath = realpath(__DIR__);

if ($debug) {
    error_log("serve.php - File path: " . $filePath);
    error_log("serve.php - Real path: " . ($realPath ?: 'NOT FOUND'));
    error_log("serve.php - Base path: " . $basePath);
}

if (!$realPath || strpos($realPath, $basePath) !== 0) {
    if ($debug) {
        error_log("serve.php - Security check failed: path outside base directory");
    }
    http_response_code(404);
    header('Content-Type: text/plain');
    die('File not found: Security check failed');
}

if (!file_exists($realPath)) {
    if ($debug) {
        error_log("serve.php - File does not exist: " . $realPath);
    }
    http_response_code(404);
    header('Content-Type: text/plain');
    die('File not found: ' . $path);
}

// Obtener el tipo MIME
$mimeType = mime_content_type($realPath);
if (!$mimeType || $mimeType === 'application/octet-stream') {
    $extension = pathinfo($realPath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'js' => 'application/javascript',
        'wasm' => 'application/wasm',
        'data' => 'application/octet-stream',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'html' => 'text/html',
        'css' => 'text/css',
    ];
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
}

// Detectar archivos comprimidos con Brotli (.br)
if (preg_match('/\.(js|data|wasm)\.br$/', $realPath)) {
    // Establecer Content-Encoding para archivos Brotli
    header('Content-Encoding: br');
    
    // Establecer Content-Type apropiado
    if (str_ends_with($realPath, '.js.br')) {
        header('Content-Type: application/javascript');
    } elseif (str_ends_with($realPath, '.wasm.br')) {
        header('Content-Type: application/wasm');
    } elseif (str_ends_with($realPath, '.data.br')) {
        header('Content-Type: application/octet-stream');
    }
} else {
    // Para archivos no comprimidos, usar el tipo MIME detectado
    header('Content-Type: ' . $mimeType);
}

// Headers CORS para Unity
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Unity-Version, X-Unity-Platform');

// Headers de cache para archivos estáticos
if (preg_match('/\.(js|data|wasm|br)$/', $realPath)) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: public, max-age=3600');
}

// Leer y enviar el archivo
$fileSize = filesize($realPath);
header('Content-Length: ' . $fileSize);

// Leer el archivo en chunks para archivos grandes
$chunkSize = 8192; // 8KB chunks
$handle = fopen($realPath, 'rb');

if ($handle === false) {
    http_response_code(500);
    die('Error reading file');
}

while (!feof($handle)) {
    echo fread($handle, $chunkSize);
    flush();
}

fclose($handle);

