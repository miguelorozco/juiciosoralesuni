<?php

/**
 * Script de Backup de Datos del Sistema de Diálogos
 * 
 * Este script crea un backup completo de todos los datos relacionados
 * con diálogos antes de la migración a la versión 2.
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: require 'database/scripts/backup-datos-dialogos.php';
 * 
 * O ejecutar directamente:
 * php artisan db:backup-dialogos
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

$timestamp = Carbon::now()->format('Y-m-d_H-i-s');
$backupDir = storage_path("app/backups/dialogos/{$timestamp}");

// Crear directorio de backup
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "\n";
echo "========================================\n";
echo "BACKUP DE DATOS - SISTEMA DE DIÁLOGOS\n";
echo "========================================\n\n";
echo "Directorio de backup: {$backupDir}\n\n";

// 1. Backup de diálogos
echo "1. Respaldando diálogos...\n";
$dialogos = DB::table('dialogos')->get();
$dialogosJson = json_encode($dialogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/dialogos.json", $dialogosJson);
echo "   ✓ {$dialogos->count()} diálogos respaldados\n";

// 2. Backup de nodos
echo "2. Respaldando nodos...\n";
$nodos = DB::table('nodos_dialogo')->get();
$nodosJson = json_encode($nodos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/nodos_dialogo.json", $nodosJson);
echo "   ✓ {$nodos->count()} nodos respaldados\n";

// 3. Backup de respuestas
echo "3. Respaldando respuestas...\n";
$respuestas = DB::table('respuestas_dialogo')->get();
$respuestasJson = json_encode($respuestas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/respuestas_dialogo.json", $respuestasJson);
echo "   ✓ {$respuestas->count()} respuestas respaldadas\n";

// 4. Backup de sesiones de diálogos
echo "4. Respaldando sesiones de diálogos...\n";
$sesionesDialogos = DB::table('sesiones_dialogos')->get();
$sesionesJson = json_encode($sesionesDialogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/sesiones_dialogos.json", $sesionesJson);
echo "   ✓ {$sesionesDialogos->count()} sesiones respaldadas\n";

// 5. Backup de decisiones
echo "5. Respaldando decisiones...\n";
$decisiones = DB::table('decisiones_sesion')->get();
$decisionesJson = json_encode($decisiones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/decisiones_sesion.json", $decisionesJson);
echo "   ✓ {$decisiones->count()} decisiones respaldadas\n";

// 6. Crear archivo de información del backup
echo "6. Creando archivo de información...\n";
$info = [
    'fecha_backup' => Carbon::now()->toDateTimeString(),
    'version_sistema' => '1.0',
    'version_futura' => '2.0',
    'conteos' => [
        'dialogos' => $dialogos->count(),
        'nodos' => $nodos->count(),
        'respuestas' => $respuestas->count(),
        'sesiones_dialogos' => $sesionesDialogos->count(),
        'decisiones' => $decisiones->count(),
    ],
    'archivos' => [
        'dialogos.json',
        'nodos_dialogo.json',
        'respuestas_dialogo.json',
        'sesiones_dialogos.json',
        'decisiones_sesion.json',
    ],
];

$infoJson = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("{$backupDir}/backup_info.json", $infoJson);
echo "   ✓ Información del backup creada\n";

// 7. Crear SQL dump (opcional, requiere mysqldump)
echo "7. Creando dump SQL...\n";
$dbName = DB::connection()->getDatabaseName();
$sqlFile = "{$backupDir}/dialogos_dump.sql";

// Intentar crear dump SQL
$tables = ['dialogos', 'nodos_dialogo', 'respuestas_dialogo', 'sesiones_dialogos', 'decisiones_sesion'];
$sqlContent = "-- Backup SQL de tablas de diálogos\n";
$sqlContent .= "-- Fecha: " . Carbon::now()->toDateTimeString() . "\n\n";

foreach ($tables as $table) {
    $sqlContent .= "-- Tabla: {$table}\n";
    $rows = DB::table($table)->get();
    
    if ($rows->count() > 0) {
        $sqlContent .= "INSERT INTO `{$table}` VALUES\n";
        $values = [];
        
        foreach ($rows as $row) {
            $rowArray = (array) $row;
            $escapedValues = array_map(function($value) {
                if (is_null($value)) {
                    return 'NULL';
                }
                if (is_numeric($value)) {
                    return $value;
                }
                return "'" . addslashes($value) . "'";
            }, $rowArray);
            
            $values[] = "(" . implode(", ", $escapedValues) . ")";
        }
        
        $sqlContent .= implode(",\n", $values) . ";\n\n";
    }
}

file_put_contents($sqlFile, $sqlContent);
echo "   ✓ Dump SQL creado\n";

// 8. Crear archivo comprimido (opcional)
echo "8. Comprimiendo backup...\n";
$zipFile = storage_path("app/backups/dialogos/backup_dialogos_{$timestamp}.zip");
$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    $files = [
        "{$backupDir}/dialogos.json",
        "{$backupDir}/nodos_dialogo.json",
        "{$backupDir}/respuestas_dialogo.json",
        "{$backupDir}/sesiones_dialogos.json",
        "{$backupDir}/decisiones_sesion.json",
        "{$backupDir}/backup_info.json",
        "{$backupDir}/dialogos_dump.sql",
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $zip->addFile($file, basename($file));
        }
    }
    
    $zip->close();
    echo "   ✓ Backup comprimido: {$zipFile}\n";
} else {
    echo "   ⚠ No se pudo crear archivo ZIP\n";
}

echo "\n";
echo "========================================\n";
echo "BACKUP COMPLETADO\n";
echo "========================================\n\n";
echo "Ubicación del backup:\n";
echo "  - Directorio: {$backupDir}\n";
if (file_exists($zipFile)) {
    echo "  - ZIP: {$zipFile}\n";
}
echo "\n";
echo "Archivos creados:\n";
echo "  - dialogos.json\n";
echo "  - nodos_dialogo.json\n";
echo "  - respuestas_dialogo.json\n";
echo "  - sesiones_dialogos.json\n";
echo "  - decisiones_sesion.json\n";
echo "  - backup_info.json\n";
echo "  - dialogos_dump.sql\n";
echo "\n";

return [
    'backup_dir' => $backupDir,
    'zip_file' => file_exists($zipFile) ? $zipFile : null,
    'timestamp' => $timestamp,
    'conteos' => $info['conteos'],
];
