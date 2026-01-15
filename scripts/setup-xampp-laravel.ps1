<#
Setup helper for XAMPP + Laravel on Windows.

Usage:
  - Run in PowerShell as Administrator if you need to modify hosts or restart services.
  - By default it uses C:\xampp\php\php.exe. If your XAMPP is elsewhere, pass -XamppPhp.

Examples:
  .\scripts\setup-xampp-laravel.ps1
  .\scripts\setup-xampp-laravel.ps1 -XamppPhp "D:\xampp\php\php.exe" -RunComposer
#>

param(
    [string]$XamppPhp = 'C:\xampp\php\php.exe',
    [switch]$RunComposer
)

function Write-Info($msg){ Write-Host "[INFO] $msg" -ForegroundColor Cyan }
function Write-Warn($msg){ Write-Host "[WARN] $msg" -ForegroundColor Yellow }
function Write-Err($msg){ Write-Host "[ERROR] $msg" -ForegroundColor Red }

Write-Info "Using PHP: $XamppPhp"

if (-not (Test-Path $XamppPhp)){
    Write-Err "PHP executable not found at $XamppPhp. Install XAMPP or pass -XamppPhp path." 
    exit 1
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Definition | Split-Path -Parent
Set-Location $root

Write-Info "Project root: $root"

$cacheFiles = @(
    'bootstrap\cache\services.php',
    'bootstrap\cache\packages.php',
    'bootstrap\cache\config.php'
)

foreach($f in $cacheFiles){
    if(Test-Path $f){
        Write-Info "Removing $f"
        Remove-Item $f -Force -ErrorAction SilentlyContinue
    }
}

Write-Info "Running Artisan cache clear commands using XAMPP PHP..."
& $XamppPhp 'artisan' 'cache:clear'
& $XamppPhp 'artisan' 'config:clear'
& $XamppPhp 'artisan' 'route:clear'
& $XamppPhp 'artisan' 'view:clear'
& $XamppPhp 'artisan' 'optimize:clear'

if($RunComposer -or (Get-Command composer -ErrorAction SilentlyContinue)){
    Write-Info "Running composer dump-autoload (composer available in PATH)..."
    composer dump-autoload
} elseif (Test-Path "$root\composer.phar"){
    Write-Info "Running composer.phar with XAMPP PHP..."
    & $XamppPhp 'composer.phar' 'dump-autoload'
} else {
    Write-Warn "Composer not found in PATH and composer.phar not present. Skipping dump-autoload. Run composer manually if needed."
}

Write-Info "Ensure Apache (XAMPP) is restarted so PHP/OPcache uses new files. Restart via XAMPP Control Panel."
Write-Info "If you still get 'A facade root has not been set', open a shell using the same PHP binary and run the above artisan commands interactively."

Write-Info "Done."
