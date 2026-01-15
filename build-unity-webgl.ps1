# Script PowerShell para compilar Unity WebGL y colocar en storage/unity-build/
# Uso: .\build-unity-webgl.ps1

param(
    [string]$UnityVersion = "2022.3.15f1",
    [string]$BuildPath = "storage\unity-build",
    [switch]$SkipCopy = $false
)

$ErrorActionPreference = "Stop"

Write-Host "Iniciando build de Unity WebGL..." -ForegroundColor Cyan
Write-Host ""

# Obtener la ruta del script actual
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = $ScriptDir
$UnityProjectPath = Join-Path $ProjectRoot "unity-integration\unity-project"
$FinalBuildPath = Join-Path $ProjectRoot $BuildPath
$TempBuildPath = Join-Path $ProjectRoot "temp-unity-build"

# Verificar que el proyecto Unity existe
if (-not (Test-Path $UnityProjectPath)) {
    Write-Host "ERROR: No se encontro el proyecto Unity en: $UnityProjectPath" -ForegroundColor Red
    exit 1
}

Write-Host "Proyecto Unity: $UnityProjectPath" -ForegroundColor Green
Write-Host "Build temporal: $TempBuildPath" -ForegroundColor Green
Write-Host "Build final: $FinalBuildPath" -ForegroundColor Green
Write-Host ""

# Buscar Unity Editor
Write-Host "Buscando Unity Editor..." -ForegroundColor Yellow

$UnityPaths = @(
    "C:\Program Files\Unity\Hub\Editor\$UnityVersion\Editor\Unity.exe",
    "C:\Program Files (x86)\Unity\Hub\Editor\$UnityVersion\Editor\Unity.exe",
    "$env:ProgramFiles\Unity\Hub\Editor\$UnityVersion\Editor\Unity.exe",
    "${env:ProgramFiles(x86)}\Unity\Hub\Editor\$UnityVersion\Editor\Unity.exe"
)

$UnityExe = $null
foreach ($path in $UnityPaths) {
    if (Test-Path $path) {
        $UnityExe = $path
        break
    }
}

# Si no se encuentra en las rutas comunes, buscar en Program Files
if ($null -eq $UnityExe) {
    $ProgramFiles = $env:ProgramFiles
    if (Test-Path "$ProgramFiles\Unity\Hub\Editor") {
        $UnityVersions = Get-ChildItem "$ProgramFiles\Unity\Hub\Editor" -Directory | Sort-Object Name -Descending
        if ($UnityVersions.Count -gt 0) {
            $LatestVersion = $UnityVersions[0].Name
            $UnityExe = "$ProgramFiles\Unity\Hub\Editor\$LatestVersion\Editor\Unity.exe"
            if (Test-Path $UnityExe) {
                Write-Host "OK: Encontrado Unity: $LatestVersion" -ForegroundColor Green
            } else {
                $UnityExe = $null
            }
        }
    }
}

if ($null -eq $UnityExe -or -not (Test-Path $UnityExe)) {
    Write-Host "ERROR: No se encontro Unity Editor" -ForegroundColor Red
    Write-Host "   Buscado en:" -ForegroundColor Yellow
    foreach ($path in $UnityPaths) {
        Write-Host "   - $path" -ForegroundColor Gray
    }
    Write-Host ""
    Write-Host "Instala Unity Hub y Unity $UnityVersion o superior" -ForegroundColor Yellow
    exit 1
}

Write-Host "OK: Unity encontrado: $UnityExe" -ForegroundColor Green
Write-Host ""

# Crear directorio temporal de build
if (Test-Path $TempBuildPath) {
    Write-Host "Limpiando build temporal anterior..." -ForegroundColor Yellow
    Remove-Item $TempBuildPath -Recurse -Force
}
New-Item -ItemType Directory -Path $TempBuildPath -Force | Out-Null

# Convertir rutas a formato absoluto
$UnityProjectPath = Resolve-Path $UnityProjectPath
$TempBuildPath = Resolve-Path $TempBuildPath

Write-Host "Compilando Unity WebGL..." -ForegroundColor Cyan
Write-Host "   Proyecto: $UnityProjectPath" -ForegroundColor Gray
Write-Host "   Destino: $TempBuildPath" -ForegroundColor Gray
Write-Host ""

# Crear archivo de log antes de iniciar
$LogFile = "$TempBuildPath\build.log"

# Ejecutar build de Unity en segundo plano
$BuildArgs = @(
    "-batchmode",
    "-quit",
    "-projectPath", "`"$UnityProjectPath`"",
    "-buildTarget", "WebGL",
    "-executeMethod", "BuildScript.BuildWebGL",
    "-buildPath", "`"$TempBuildPath`"",
    "-logFile", "`"$LogFile`""
)

# Iniciar proceso de Unity
$Process = Start-Process -FilePath $UnityExe -ArgumentList $BuildArgs -PassThru -WindowStyle Hidden

# Función para mostrar barra de progreso
function Show-ProgressBar {
    param(
        [int]$Percent,
        [string]$Status,
        [string]$Activity = "Compilando Unity WebGL"
    )
    
    Write-Progress -Activity $Activity -Status $Status -PercentComplete $Percent
}

# Función para leer progreso del log
function Get-BuildProgress {
    param([string]$LogPath)
    
    if (-not (Test-Path $LogPath)) {
        return @{ Percent = 0; Status = "Iniciando compilacion..." }
    }
    
    $logContent = Get-Content $LogPath -Tail 50 -ErrorAction SilentlyContinue
    $lastLine = $logContent[-1] -join "`n"
    
    # Detectar fases de compilación
    $percent = 0
    $status = "Compilando..."
    
    if ($lastLine -match "Building Library") {
        $percent = 10
        $status = "Construyendo biblioteca..."
    }
    elseif ($lastLine -match "Building Player") {
        $percent = 30
        $status = "Construyendo player..."
    }
    elseif ($lastLine -match "Compiling scripts") {
        $percent = 50
        $status = "Compilando scripts..."
    }
    elseif ($lastLine -match "Building Asset Bundles") {
        $percent = 70
        $status = "Construyendo asset bundles..."
    }
    elseif ($lastLine -match "Building WebGL") {
        $percent = 85
        $status = "Construyendo WebGL..."
    }
    elseif ($lastLine -match "Build succeeded|Build completed") {
        $percent = 100
        $status = "Build completado!"
    }
    elseif ($lastLine -match "error|Error|ERROR") {
        $percent = 0
        $status = "Error detectado en el log"
    }
    
    return @{ Percent = $percent; Status = $status }
}

# Monitorear progreso mientras Unity compila
$startTime = Get-Date
$lastPercent = 0
$spinner = @('|', '/', '-', '\')
$spinnerIndex = 0

Write-Host "Iniciando compilacion..." -ForegroundColor Yellow
Write-Host ""

while (-not $Process.HasExited) {
    # Obtener progreso del log
    $progress = Get-BuildProgress -LogPath $LogFile
    
    # Calcular progreso basado en tiempo transcurrido si no hay información del log
    $elapsed = (Get-Date) - $startTime
    $timeBasedPercent = [math]::Min(90, [int]($elapsed.TotalSeconds / 2))
    
    # Usar el mayor entre el progreso del log y el basado en tiempo
    $currentPercent = [math]::Max($progress.Percent, $timeBasedPercent)
    
    # Actualizar barra de progreso
    $spinnerChar = $spinner[$spinnerIndex % $spinner.Count]
    $statusText = "$spinnerChar $($progress.Status) (Tiempo: $([math]::Round($elapsed.TotalSeconds))s)"
    Show-ProgressBar -Percent $currentPercent -Status $statusText
    
    # Rotar spinner
    $spinnerIndex++
    
    # Esperar un poco antes de la siguiente actualización
    Start-Sleep -Milliseconds 500
}

# Limpiar barra de progreso
Write-Progress -Activity "Compilando Unity WebGL" -Completed

# Esperar a que el proceso termine completamente
$Process.WaitForExit()

# Verificar resultado del build
if ($Process.ExitCode -ne 0) {
    Write-Host "ERROR: Build fallo con codigo de salida: $($Process.ExitCode)" -ForegroundColor Red
    if (Test-Path "$TempBuildPath\build.log") {
        Write-Host ""
        Write-Host "Ultimas lineas del log:" -ForegroundColor Yellow
        Get-Content "$TempBuildPath\build.log" -Tail 20 | ForEach-Object {
            Write-Host $_ -ForegroundColor Gray
        }
    }
    exit 1
}

# Verificar que el build se creo correctamente
if (-not (Test-Path $TempBuildPath) -or (Get-ChildItem $TempBuildPath -ErrorAction SilentlyContinue).Count -eq 0) {
    Write-Host "ERROR: Build fallo - directorio de build vacio o no existe" -ForegroundColor Red
    exit 1
}

Write-Host "OK: Build completado exitosamente!" -ForegroundColor Green
Write-Host ""

# Copiar archivos al directorio final
if (-not $SkipCopy) {
    Write-Host "Copiando archivos a $FinalBuildPath..." -ForegroundColor Cyan
    
    # Crear directorio final si no existe
    if (-not (Test-Path $FinalBuildPath)) {
        New-Item -ItemType Directory -Path $FinalBuildPath -Force | Out-Null
    }
    
    # Hacer backup del build anterior si existe
    $BackupPath = "$FinalBuildPath.backup"
    if (Test-Path $FinalBuildPath) {
        Write-Host "Creando backup del build anterior..." -ForegroundColor Yellow
        if (Test-Path $BackupPath) {
            Remove-Item $BackupPath -Recurse -Force
        }
        Move-Item $FinalBuildPath $BackupPath -Force
    }
    
    # Copiar todos los archivos
    Write-Host "Copiando archivos..." -ForegroundColor Yellow
    Copy-Item -Path "$TempBuildPath\*" -Destination $FinalBuildPath -Recurse -Force
    
    Write-Host "OK: Archivos copiados exitosamente!" -ForegroundColor Green
    Write-Host ""
    
    # Mostrar información del build
    $BuildSize = (Get-ChildItem $FinalBuildPath -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Host "Informacion del build:" -ForegroundColor Cyan
    Write-Host "   Ubicacion: $FinalBuildPath" -ForegroundColor Gray
    Write-Host "   Tamano: $([math]::Round($BuildSize, 2)) MB" -ForegroundColor Gray
    Write-Host "   Archivos:" -ForegroundColor Gray
    Get-ChildItem $FinalBuildPath -Recurse -File | ForEach-Object {
        $Size = $_.Length / 1KB
        Write-Host "      - $($_.Name) ($([math]::Round($Size, 2)) KB)" -ForegroundColor DarkGray
    }
    Write-Host ""
    
    # Verificar archivos críticos
    $MissingFiles = @()
    
    # Verificar index.html
    $IndexPath = Join-Path $FinalBuildPath "index.html"
    if (-not (Test-Path $IndexPath)) {
        $MissingFiles += "index.html"
    }
    
    # Verificar archivos en Build/
    $BuildDirPath = Join-Path $FinalBuildPath "Build"
    if (Test-Path $BuildDirPath) {
        $LoaderFiles = Get-ChildItem $BuildDirPath -Filter "*.loader.js" -ErrorAction SilentlyContinue
        $DataFiles = Get-ChildItem $BuildDirPath -Filter "*.data.br" -ErrorAction SilentlyContinue
        $FrameworkFiles = Get-ChildItem $BuildDirPath -Filter "*.framework.js.br" -ErrorAction SilentlyContinue
        $WasmFiles = Get-ChildItem $BuildDirPath -Filter "*.wasm.br" -ErrorAction SilentlyContinue
        
        if ($LoaderFiles.Count -eq 0) {
            $MissingFiles += "Build\*.loader.js"
        }
        if ($DataFiles.Count -eq 0) {
            $MissingFiles += "Build\*.data.br"
        }
        if ($FrameworkFiles.Count -eq 0) {
            $MissingFiles += "Build\*.framework.js.br"
        }
        if ($WasmFiles.Count -eq 0) {
            $MissingFiles += "Build\*.wasm.br"
        }
    } else {
        $MissingFiles += "Build\ (directorio no existe)"
    }
    
    if ($MissingFiles.Count -gt 0) {
        Write-Host "ADVERTENCIA: Algunos archivos criticos no se encontraron:" -ForegroundColor Yellow
        foreach ($File in $MissingFiles) {
            Write-Host "   - $File" -ForegroundColor Yellow
        }
        Write-Host ""
    } else {
        Write-Host "OK: Todos los archivos criticos presentes" -ForegroundColor Green
        Write-Host ""
    }
}

# Limpiar build temporal
Write-Host "Limpiando archivos temporales..." -ForegroundColor Yellow
Remove-Item $TempBuildPath -Recurse -Force -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "Build completado exitosamente!" -ForegroundColor Green
Write-Host "Build disponible en: $FinalBuildPath" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para probar el build:" -ForegroundColor Yellow
Write-Host "   1. Inicia Laravel: php artisan serve" -ForegroundColor Gray
Write-Host "   2. Visita: http://localhost:8000/unity-game" -ForegroundColor Gray
Write-Host ""
