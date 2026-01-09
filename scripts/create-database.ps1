# Crea la base de datos MySQL si no existe
$ROOT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $ROOT_DIR

Write-Host "=== Crear base de datos MySQL ===" -ForegroundColor Cyan
Write-Host ""

# Cargar variables del archivo .env
$envVars = @{}
Get-Content .env | ForEach-Object {
    if ($_ -match '^\s*([^#][^=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()
        $envVars[$key] = $value
    }
}

$dbHost = $envVars['DB_HOST']
$dbPort = $envVars['DB_PORT']
$dbDatabase = $envVars['DB_DATABASE']
$dbUsername = $envVars['DB_USERNAME']
$dbPassword = $envVars['DB_PASSWORD']

Write-Host "Configuracion:" -ForegroundColor Yellow
Write-Host "  Host: $dbHost" -ForegroundColor White
Write-Host "  Puerto: $dbPort" -ForegroundColor White
Write-Host "  Base de datos: $dbDatabase" -ForegroundColor White
Write-Host "  Usuario: $dbUsername" -ForegroundColor White
Write-Host ""

# Crear script PHP para crear la base de datos
$phpScript = @"
<?php
try {
    `$host = '$dbHost';
    `$port = '$dbPort';
    `$username = '$dbUsername';
    `$password = '$dbPassword';
    `$database = '$dbDatabase';
    
    // Conectar sin especificar base de datos
    `$pdo = new PDO("mysql:host=`$host;port=`$port", `$username, `$password ?: null);
    `$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si la base de datos existe
    `$stmt = `$pdo->query("SHOW DATABASES LIKE '`$database'");
    `$exists = `$stmt->rowCount() > 0;
    
    if (`$exists) {
        echo "La base de datos `$database ya existe.\n";
        exit(0);
    }
    
    // Crear la base de datos
    `$pdo->exec("CREATE DATABASE IF NOT EXISTS `$database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos `$database creada exitosamente.\n";
    
} catch (PDOException `$e) {
    echo "Error: " . `$e->getMessage() . "\n";
    exit(1);
}
"@

$tempScript = Join-Path $env:TEMP "create_db_$(Get-Random).php"
Set-Content -Path $tempScript -Value $phpScript -Encoding UTF8

try {
    $output = php $tempScript 2>&1
    $exitCode = $LASTEXITCODE
    
    if ($exitCode -eq 0) {
        Write-Host $output -ForegroundColor Green
        Write-Host ""
        Write-Host "=== Resumen ===" -ForegroundColor Cyan
        Write-Host "Base de datos lista para usar" -ForegroundColor Green
    } else {
        Write-Host $output -ForegroundColor Red
        Write-Host ""
        Write-Host "=== Resumen ===" -ForegroundColor Cyan
        Write-Host "Error al crear la base de datos" -ForegroundColor Red
        Write-Host ""
        Write-Host "Posibles soluciones:" -ForegroundColor Yellow
        Write-Host "1. Verifica que MySQL este corriendo" -ForegroundColor White
        Write-Host "2. Verifica las credenciales en el archivo .env" -ForegroundColor White
        Write-Host "3. Verifica que el usuario tenga permisos para crear bases de datos" -ForegroundColor White
        exit 1
    }
} finally {
    if (Test-Path $tempScript) {
        Remove-Item $tempScript -Force
    }
}

