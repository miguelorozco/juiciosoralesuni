# Arranca el servidor Laravel para debug. Opcionalmente puede lanzar Vite
# (npm run dev) si se establece $env:WITH_VITE=1.
$ROOT_DIR = Split-Path -Parent $PSScriptRoot
$SERVER_HOST = if ($env:HOST) { $env:HOST } else { "127.0.0.1" }
$PORT = if ($env:PORT) { $env:PORT } else { "8000" }
$WITH_VITE = if ($env:WITH_VITE) { $env:WITH_VITE } else { "0" }

Set-Location $ROOT_DIR

# Verificar binarios requeridos
$binaries = @("php", "composer")
foreach ($bin in $binaries) {
    $command = Get-Command $bin -ErrorAction SilentlyContinue
    if (-not $command) {
        Write-Host "Falta el binario requerido: $bin" -ForegroundColor Red
        exit 1
    }
}

# Verificar archivo .env
if (-not (Test-Path ".env")) {
    Write-Host "Falta .env. Copia .env.example y genera APP_KEY antes de continuar." -ForegroundColor Red
    exit 1
}

# Instalar dependencias PHP si es necesario
if (-not (Test-Path "vendor")) {
    Write-Host "Instalando dependencias PHP (composer install)..." -ForegroundColor Yellow
    composer install
}

$NPM_PROCESS = $null

# Manejar Vite si está habilitado
if ($WITH_VITE -ne "0") {
    $npmCmd = Get-Command npm -ErrorAction SilentlyContinue
    if (-not $npmCmd) {
        Write-Host "WITH_VITE=1 requiere npm instalado." -ForegroundColor Red
        exit 1
    }
    
    if (-not (Test-Path "node_modules")) {
        Write-Host "Instalando dependencias JS (npm install)..." -ForegroundColor Yellow
        npm install
    }
    
    Write-Host "Arrancando Vite (npm run dev) en segundo plano..." -ForegroundColor Yellow
    $NPM_PROCESS = Start-Process -FilePath "npm" -ArgumentList "run", "dev", "--", "--host", "--clearScreen=false" -PassThru -NoNewWindow
}

# Función de limpieza
function Cleanup {
    if ($NPM_PROCESS -and -not $NPM_PROCESS.HasExited) {
        Write-Host "`nDeteniendo proceso de Vite..." -ForegroundColor Yellow
        Stop-Process -Id $NPM_PROCESS.Id -Force -ErrorAction SilentlyContinue
    }
}

# Registrar limpieza al salir
$null = Register-EngineEvent PowerShell.Exiting -Action { Cleanup }

Write-Host "Arrancando servidor Laravel en http://${SERVER_HOST}:${PORT}" -ForegroundColor Green
Write-Host "Presiona Ctrl+C para detener el servidor" -ForegroundColor Gray

try {
    php artisan serve --host="$SERVER_HOST" --port="$PORT"
} catch {
    Write-Host "`nError al ejecutar el servidor: $_" -ForegroundColor Red
} finally {
    Cleanup
}

