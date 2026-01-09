# Verifica la conexión a MySQL usando las credenciales del archivo .env
$ROOT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $ROOT_DIR

Write-Host "=== Verificación de conexión MySQL ===" -ForegroundColor Cyan
Write-Host ""

# Verificar que existe el archivo .env
if (-not (Test-Path ".env")) {
    Write-Host "✘ Error: No se encuentra el archivo .env" -ForegroundColor Red
    Write-Host "Ejecuta primero: Copy-Item .env.example .env" -ForegroundColor Yellow
    exit 1
}

# Cargar variables del archivo .env
$envVars = @{}
Get-Content .env | ForEach-Object {
    if ($_ -match '^\s*([^#][^=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()
        $envVars[$key] = $value
    }
}

# Obtener valores de configuración
$dbConnection = $envVars['DB_CONNECTION']
$dbHost = $envVars['DB_HOST']
$dbPort = $envVars['DB_PORT']
$dbDatabase = $envVars['DB_DATABASE']
$dbUsername = $envVars['DB_USERNAME']
$dbPassword = $envVars['DB_PASSWORD']

Write-Host "Configuración encontrada:" -ForegroundColor Yellow
Write-Host "  DB_CONNECTION: $dbConnection" -ForegroundColor White
Write-Host "  DB_HOST: $dbHost" -ForegroundColor White
Write-Host "  DB_PORT: $dbPort" -ForegroundColor White
Write-Host "  DB_DATABASE: $dbDatabase" -ForegroundColor White
Write-Host "  DB_USERNAME: $dbUsername" -ForegroundColor White
Write-Host "  DB_PASSWORD: $(if ($dbPassword) { '***' } else { '(vacío)' })" -ForegroundColor White
Write-Host ""

# Verificar que MySQL está configurado
if ($dbConnection -ne "mysql") {
    Write-Host 'Advertencia: DB_CONNECTION no esta configurado como mysql' -ForegroundColor Yellow
    Write-Host "  Valor actual: $dbConnection" -ForegroundColor White
    Write-Host ""
}

# Verificar que MySQL está instalado y disponible
$mysqlCmd = Get-Command mysql -ErrorAction SilentlyContinue
if (-not $mysqlCmd) {
    Write-Host 'Advertencia: El comando mysql no esta disponible en el PATH' -ForegroundColor Yellow
    Write-Host '  Esto puede significar que MySQL no esta instalado o no esta en el PATH' -ForegroundColor White
    Write-Host ""
} else {
    Write-Host 'Comando mysql encontrado' -ForegroundColor Green
    Write-Host ""
}

# Verificar conexión usando PHP/Artisan
Write-Host 'Verificando conexion a MySQL...' -ForegroundColor Cyan

$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCmd) {
    Write-Host 'Error: PHP no esta instalado o no esta en el PATH' -ForegroundColor Red
    exit 1
}

# Usar artisan db:show para verificar la conexión
try {
    $output = php artisan db:show 2>&1
    $exitCode = $LASTEXITCODE
    
    if ($exitCode -eq 0) {
        Write-Host $output -ForegroundColor Green
        Write-Host ''
        Write-Host '=== Resumen ===' -ForegroundColor Cyan
        Write-Host 'La conexion a MySQL esta configurada correctamente' -ForegroundColor Green
        Write-Host 'Las credenciales son validas' -ForegroundColor Green
        Write-Host 'La base de datos esta accesible' -ForegroundColor Green
    } else {
        # Si db:show no funciona, intentar con una consulta simple
        Write-Host 'Intentando conexion directa...' -ForegroundColor Yellow
        $tinkerCmd = 'DB::connection()->getPdo()'
        $testQuery = php artisan tinker --execute=$tinkerCmd 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host $testQuery -ForegroundColor Green
            Write-Host ""
            Write-Host '=== Resumen ===' -ForegroundColor Cyan
            Write-Host 'La conexion a MySQL esta configurada correctamente' -ForegroundColor Green
            Write-Host 'Las credenciales son validas' -ForegroundColor Green
            Write-Host 'La base de datos esta accesible' -ForegroundColor Green
        } else {
            Write-Host $output -ForegroundColor Red
            Write-Host $testQuery -ForegroundColor Red
            Write-Host ''
            Write-Host '=== Resumen ===' -ForegroundColor Cyan
            Write-Host 'Error al conectar a MySQL' -ForegroundColor Red
            Write-Host ''
            Write-Host 'Posibles soluciones:' -ForegroundColor Yellow
            Write-Host '1. Verifica que MySQL este corriendo' -ForegroundColor White
            Write-Host '2. Verifica las credenciales en el archivo .env' -ForegroundColor White
            $msg3 = '3. Verifica que la base de datos ' + $dbDatabase + ' exista'
            Write-Host $msg3 -ForegroundColor White
            $msg4 = '4. Verifica que el usuario ' + $dbUsername + ' tenga permisos'
            Write-Host $msg4 -ForegroundColor White
            exit 1
        }
    }
} catch {
    $errorMsg = $_.Exception.Message
    Write-Host 'Error al ejecutar verificacion:' $errorMsg -ForegroundColor Red
    exit 1
}
