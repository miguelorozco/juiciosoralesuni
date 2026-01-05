<?php

/**
 * Script de Validación de Migración de Diálogos v1 → v2
 * 
 * Este script valida que la migración de datos se haya realizado correctamente
 * comparando los conteos entre las tablas v1 y v2.
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: require 'database/scripts/validar-migracion-dialogos.php';
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "========================================\n";
echo "VALIDACIÓN DE MIGRACIÓN - DIÁLOGOS v2\n";
echo "========================================\n\n";

// Verificar que ambas versiones existan
$tablasV1 = ['dialogos', 'nodos_dialogo', 'respuestas_dialogo', 'sesiones_dialogos', 'decisiones_sesion'];
$tablasV2 = ['dialogos_v2', 'nodos_dialogo_v2', 'respuestas_dialogo_v2', 'sesiones_dialogos_v2', 'decisiones_dialogo_v2'];

$todasExisten = true;
foreach ($tablasV1 as $tabla) {
    if (!Schema::hasTable($tabla)) {
        echo "⚠ Advertencia: Tabla v1 {$tabla} no existe\n";
    }
}
foreach ($tablasV2 as $tabla) {
    if (!Schema::hasTable($tabla)) {
        echo "❌ ERROR: Tabla v2 {$tabla} no existe\n";
        $todasExisten = false;
    }
}

if (!$todasExisten) {
    echo "\n❌ No se puede validar. Ejecuta las migraciones primero.\n";
    exit(1);
}

echo "✓ Todas las tablas existen\n\n";

// Comparar conteos
echo "COMPARACIÓN DE CONTEOS\n";
echo "----------------------\n\n";

$comparaciones = [
    ['v1' => 'dialogos', 'v2' => 'dialogos_v2', 'nombre' => 'Diálogos'],
    ['v1' => 'nodos_dialogo', 'v2' => 'nodos_dialogo_v2', 'nombre' => 'Nodos'],
    ['v1' => 'respuestas_dialogo', 'v2' => 'respuestas_dialogo_v2', 'nombre' => 'Respuestas'],
    ['v1' => 'sesiones_dialogos', 'v2' => 'sesiones_dialogos_v2', 'nombre' => 'Sesiones'],
    ['v1' => 'decisiones_sesion', 'v2' => 'decisiones_dialogo_v2', 'nombre' => 'Decisiones'],
];

$todosCorrectos = true;

foreach ($comparaciones as $comp) {
    $countV1 = Schema::hasTable($comp['v1']) ? DB::table($comp['v1'])->count() : 0;
    $countV2 = DB::table($comp['v2'])->count();
    
    $icono = ($countV1 === $countV2) ? '✓' : '❌';
    $estado = ($countV1 === $countV2) ? 'OK' : 'DIFERENCIA';
    
    echo sprintf("  %s %-20s: v1=%d  v2=%d  [%s]\n", 
        $icono, 
        $comp['nombre'], 
        $countV1, 
        $countV2, 
        $estado
    );
    
    if ($countV1 !== $countV2) {
        $todosCorrectos = false;
        $diferencia = abs($countV1 - $countV2);
        echo sprintf("     ⚠ Diferencia: %d registros\n", $diferencia);
    }
}

echo "\n";

// Validaciones específicas
echo "VALIDACIONES ESPECÍFICAS\n";
echo "------------------------\n\n";

// 1. Verificar que todos los diálogos tienen nodo inicial
echo "1. Verificando nodos iniciales...\n";
$dialogosSinInicial = DB::table('dialogos_v2')
    ->whereNotIn('id', function($query) {
        $query->select('dialogo_id')
              ->from('nodos_dialogo_v2')
              ->where('es_inicial', true);
    })
    ->count();

if ($dialogosSinInicial > 0) {
    echo "   ⚠ {$dialogosSinInicial} diálogos sin nodo inicial\n";
    $todosCorrectos = false;
} else {
    echo "   ✓ Todos los diálogos tienen nodo inicial\n";
}

// 2. Verificar que todos los diálogos tienen al menos un nodo final
echo "2. Verificando nodos finales...\n";
$dialogosSinFinal = DB::table('dialogos_v2')
    ->whereNotIn('id', function($query) {
        $query->select('dialogo_id')
              ->from('nodos_dialogo_v2')
              ->where('es_final', true);
    })
    ->count();

if ($dialogosSinFinal > 0) {
    echo "   ⚠ {$dialogosSinFinal} diálogos sin nodos finales\n";
    $todosCorrectos = false;
} else {
    echo "   ✓ Todos los diálogos tienen nodos finales\n";
}

// 3. Verificar posiciones
echo "3. Verificando posiciones...\n";
$nodosSinPosicion = DB::table('nodos_dialogo_v2')
    ->where('posicion_x', 0)
    ->where('posicion_y', 0)
    ->where('es_inicial', false)
    ->count();

if ($nodosSinPosicion > 0) {
    echo "   ⚠ {$nodosSinPosicion} nodos en posición (0,0) (puede ser intencional)\n";
} else {
    echo "   ✓ Todas las posiciones están definidas\n";
}

// 4. Verificar integridad referencial
echo "4. Verificando integridad referencial...\n";
$errores = [];

// Nodos con diálogo inválido
$nodosInvalidos = DB::table('nodos_dialogo_v2')
    ->whereNotIn('dialogo_id', function($query) {
        $query->select('id')->from('dialogos_v2');
    })
    ->count();

if ($nodosInvalidos > 0) {
    $errores[] = "{$nodosInvalidos} nodos con diálogo inválido";
}

// Respuestas con nodo padre inválido
$respuestasInvalidas = DB::table('respuestas_dialogo_v2')
    ->whereNotIn('nodo_padre_id', function($query) {
        $query->select('id')->from('nodos_dialogo_v2');
    })
    ->count();

if ($respuestasInvalidas > 0) {
    $errores[] = "{$respuestasInvalidas} respuestas con nodo padre inválido";
}

// Respuestas con nodo siguiente inválido (si no es null)
$respuestasSiguienteInvalido = DB::table('respuestas_dialogo_v2')
    ->whereNotNull('nodo_siguiente_id')
    ->whereNotIn('nodo_siguiente_id', function($query) {
        $query->select('id')->from('nodos_dialogo_v2');
    })
    ->count();

if ($respuestasSiguienteInvalido > 0) {
    $errores[] = "{$respuestasSiguienteInvalido} respuestas con nodo siguiente inválido";
}

if (empty($errores)) {
    echo "   ✓ Integridad referencial correcta\n";
} else {
    echo "   ❌ Errores de integridad:\n";
    foreach ($errores as $error) {
        echo "      - {$error}\n";
    }
    $todosCorrectos = false;
}

echo "\n";

// Resumen final
echo "========================================\n";
if ($todosCorrectos) {
    echo "✓ VALIDACIÓN EXITOSA\n";
    echo "========================================\n\n";
    echo "Todos los datos han sido migrados correctamente.\n";
    echo "Puedes proceder a eliminar las tablas v1 de forma segura.\n";
    echo "\n";
} else {
    echo "⚠ VALIDACIÓN CON ADVERTENCIAS\n";
    echo "========================================\n\n";
    echo "Se encontraron diferencias o errores en la migración.\n";
    echo "Revisa los detalles arriba antes de eliminar las tablas v1.\n";
    echo "\n";
}

return [
    'valido' => $todosCorrectos,
    'errores' => $errores ?? [],
];
