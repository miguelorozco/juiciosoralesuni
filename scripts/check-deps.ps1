# Verifica dependencias básicas para desarrollar/levantar el proyecto.
$ROOT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $ROOT_DIR

$MISSING = 0
Write-Host "=== Binarios requeridos ===" -ForegroundColor Cyan

$binaries = @("php", "composer", "npm", "node", "git")
foreach ($bin in $binaries) {
    $command = Get-Command $bin -ErrorAction SilentlyContinue
    if ($command) {
        $versionOutput = $null
        $errorOccurred = $false
        $versionOutput = & $bin --version 2>&1 | Select-Object -First 1
        if ($versionOutput -and -not ($versionOutput -is [System.Management.Automation.ErrorRecord])) {
            $versionText = $versionOutput.ToString().Trim()
            if ($versionText -ne "") {
                Write-Host "[OK] $($bin.PadRight(8)) $versionText" -ForegroundColor Green
            } else {
                Write-Host "[OK] $($bin.PadRight(8)) (instalado)" -ForegroundColor Green
            }
        } else {
            Write-Host "[OK] $($bin.PadRight(8)) (instalado)" -ForegroundColor Green
        }
    } else {
        Write-Host "[X] $($bin.PadRight(8)) (no encontrado)" -ForegroundColor Red
        $MISSING = 1
    }
}

if ($MISSING -ne 0) {
    Write-Host "Instala los binarios faltantes antes de continuar." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Estructura de dependencias ===" -ForegroundColor Cyan

if (Test-Path "vendor") {
    Write-Host "[OK] vendor/ presente" -ForegroundColor Green
} else {
    Write-Host '[X] Falta vendor/ (ejecuta: composer install)' -ForegroundColor Red
}

if (Test-Path "node_modules") {
    Write-Host "[OK] node_modules/ presente" -ForegroundColor Green
} else {
    Write-Host '[X] Falta node_modules/ (ejecuta: npm install)' -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Validaciones rápidas ===" -ForegroundColor Cyan

$composerCmd = Get-Command composer -ErrorAction SilentlyContinue
if ($composerCmd) {
    composer validate --no-check-lock
    composer check-platform-reqs --no-dev
}

$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) {
    php artisan --version
}

Write-Host ""
Write-Host "Listo. Si ves advertencias arriba, corrige antes de continuar." -ForegroundColor Yellow

