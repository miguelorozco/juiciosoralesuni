<?php

/**
 * Script de Análisis de Datos del Sistema de Diálogos Actual
 * 
 * Este script analiza los datos existentes en el sistema de diálogos
 * para preparar la migración a la versión 2.
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: require 'database/scripts/analizar-datos-dialogos.php';
 */

use App\Models\Dialogo;
use App\Models\NodoDialogo;
use App\Models\RespuestaDialogo;
use App\Models\SesionDialogo;
use App\Models\DecisionSesion;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "ANÁLISIS DE DATOS - SISTEMA DE DIÁLOGOS\n";
echo "========================================\n\n";

// 1. Contar registros por tabla
echo "1. CONTEO DE REGISTROS POR TABLA\n";
echo "--------------------------------\n";

$conteos = [
    'dialogos' => DB::table('dialogos')->count(),
    'nodos_dialogo' => DB::table('nodos_dialogo')->count(),
    'respuestas_dialogo' => DB::table('respuestas_dialogo')->count(),
    'sesiones_dialogos' => DB::table('sesiones_dialogos')->count(),
    'decisiones_sesion' => DB::table('decisiones_sesion')->count(),
];

foreach ($conteos as $tabla => $total) {
    echo sprintf("  %-25s: %d registros\n", $tabla, $total);
}

echo "\n";

// 2. Análisis de diálogos
echo "2. ANÁLISIS DE DIÁLOGOS\n";
echo "-----------------------\n";

$dialogos = Dialogo::withCount('nodos')->get();

echo sprintf("  Total diálogos: %d\n", $dialogos->count());
echo sprintf("  Diálogos activos: %d\n", $dialogos->where('estado', 'activo')->count());
echo sprintf("  Diálogos públicos: %d\n", $dialogos->where('publico', true)->count());
echo sprintf("  Diálogos borradores: %d\n", $dialogos->where('estado', 'borrador')->count());

$dialogosConNodos = $dialogos->filter(fn($d) => $d->nodos_count > 0);
echo sprintf("  Diálogos con nodos: %d\n", $dialogosConNodos->count());

if ($dialogosConNodos->count() > 0) {
    $maxNodos = $dialogosConNodos->max('nodos_count');
    $minNodos = $dialogosConNodos->min('nodos_count');
    $avgNodos = round($dialogosConNodos->avg('nodos_count'), 2);
    
    echo sprintf("  Máximo nodos por diálogo: %d\n", $maxNodos);
    echo sprintf("  Mínimo nodos por diálogo: %d\n", $minNodos);
    echo sprintf("  Promedio nodos por diálogo: %.2f\n", $avgNodos);
}

echo "\n";

// 3. Análisis de nodos
echo "3. ANÁLISIS DE NODOS\n";
echo "--------------------\n";

$nodos = NodoDialogo::all();

echo sprintf("  Total nodos: %d\n", $nodos->count());

$tiposNodos = $nodos->groupBy('tipo')->map->count();
foreach ($tiposNodos as $tipo => $cantidad) {
    echo sprintf("  Nodos tipo '%s': %d\n", $tipo, $cantidad);
}

$nodosIniciales = $nodos->where('es_inicial', true)->count();
$nodosFinales = $nodos->where('es_final', true)->count();

echo sprintf("  Nodos iniciales: %d\n", $nodosIniciales);
echo sprintf("  Nodos finales: %d\n", $nodosFinales);

// Nodos con posiciones
$nodosConPosicion = $nodos->filter(function($nodo) {
    $metadata = $nodo->metadata;
    return $metadata && isset($metadata['posicion']);
})->count();

echo sprintf("  Nodos con posición definida: %d\n", $nodosConPosicion);
echo sprintf("  Nodos sin posición: %d\n", $nodos->count() - $nodosConPosicion);

// Nodos con respuestas
$nodosConRespuestas = $nodos->filter(fn($n) => $n->respuestas()->count() > 0)->count();
echo sprintf("  Nodos con respuestas: %d\n", $nodosConRespuestas);

echo "\n";

// 4. Análisis de respuestas
echo "4. ANÁLISIS DE RESPUESTAS\n";
echo "-------------------------\n";

$respuestas = RespuestaDialogo::all();

echo sprintf("  Total respuestas: %d\n", $respuestas->count());
echo sprintf("  Respuestas activas: %d\n", $respuestas->where('activo', true)->count());
echo sprintf("  Respuestas inactivas: %d\n", $respuestas->where('activo', false)->count());

$respuestasConDestino = $respuestas->whereNotNull('nodo_siguiente_id')->count();
$respuestasFinales = $respuestas->whereNull('nodo_siguiente_id')->count();

echo sprintf("  Respuestas con destino: %d\n", $respuestasConDestino);
echo sprintf("  Respuestas finales (sin destino): %d\n", $respuestasFinales);

$respuestasConPuntuacion = $respuestas->filter(fn($r) => $r->puntuacion > 0)->count();
echo sprintf("  Respuestas con puntuación > 0: %d\n", $respuestasConPuntuacion);

if ($respuestasConPuntuacion > 0) {
    $maxPuntuacion = $respuestas->max('puntuacion');
    $minPuntuacion = $respuestas->where('puntuacion', '>', 0)->min('puntuacion');
    $avgPuntuacion = round($respuestas->where('puntuacion', '>', 0)->avg('puntuacion'), 2);
    
    echo sprintf("  Puntuación máxima: %d\n", $maxPuntuacion);
    echo sprintf("  Puntuación mínima: %d\n", $minPuntuacion);
    echo sprintf("  Puntuación promedio: %.2f\n", $avgPuntuacion);
}

echo "\n";

// 5. Análisis de sesiones de diálogos
echo "5. ANÁLISIS DE SESIONES DE DIÁLOGOS\n";
echo "------------------------------------\n";

$sesionesDialogos = SesionDialogo::all();

echo sprintf("  Total sesiones de diálogos: %d\n", $sesionesDialogos->count());

$estados = $sesionesDialogos->groupBy('estado')->map->count();
foreach ($estados as $estado => $cantidad) {
    echo sprintf("  Sesiones en estado '%s': %d\n", $estado, $cantidad);
}

$sesionesActivas = $sesionesDialogos->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])->count();
echo sprintf("  Sesiones activas: %d\n", $sesionesActivas);

$sesionesConNodoActual = $sesionesDialogos->whereNotNull('nodo_actual_id')->count();
echo sprintf("  Sesiones con nodo actual: %d\n", $sesionesConNodoActual);

echo "\n";

// 6. Análisis de decisiones
echo "6. ANÁLISIS DE DECISIONES\n";
echo "-------------------------\n";

$decisiones = DecisionSesion::all();

echo sprintf("  Total decisiones: %d\n", $decisiones->count());

$decisionesConRespuesta = $decisiones->whereNotNull('respuesta_id')->count();
echo sprintf("  Decisiones con respuesta: %d\n", $decisionesConRespuesta);

$decisionesConTiempo = $decisiones->whereNotNull('tiempo_respuesta')->count();
echo sprintf("  Decisiones con tiempo registrado: %d\n", $decisionesConTiempo);

if ($decisionesConTiempo > 0) {
    $tiempoPromedio = round($decisiones->whereNotNull('tiempo_respuesta')->avg('tiempo_respuesta'), 2);
    $tiempoMinimo = $decisiones->whereNotNull('tiempo_respuesta')->min('tiempo_respuesta');
    $tiempoMaximo = $decisiones->whereNotNull('tiempo_respuesta')->max('tiempo_respuesta');
    
    echo sprintf("  Tiempo promedio de respuesta: %.2f segundos\n", $tiempoPromedio);
    echo sprintf("  Tiempo mínimo: %d segundos\n", $tiempoMinimo);
    echo sprintf("  Tiempo máximo: %d segundos\n", $tiempoMaximo);
}

// Decisiones por rol
$decisionesPorRol = $decisiones->groupBy('rol_id')->map->count();
echo sprintf("  Decisiones por rol: %d roles diferentes\n", $decisionesPorRol->count());

echo "\n";

// 7. Análisis de integridad
echo "7. ANÁLISIS DE INTEGRIDAD\n";
echo "-------------------------\n";

// Diálogos sin nodos
$dialogosSinNodos = Dialogo::doesntHave('nodos')->count();
echo sprintf("  Diálogos sin nodos: %d\n", $dialogosSinNodos);

// Diálogos sin nodo inicial
$dialogosSinInicial = Dialogo::whereDoesntHave('nodos', function($q) {
    $q->where('es_inicial', true);
})->count();
echo sprintf("  Diálogos sin nodo inicial: %d\n", $dialogosSinInicial);

// Diálogos sin nodos finales
$dialogosSinFinal = Dialogo::whereDoesntHave('nodos', function($q) {
    $q->where('es_final', true);
})->count();
echo sprintf("  Diálogos sin nodos finales: %d\n", $dialogosSinFinal);

// Nodos huérfanos (sin conexiones)
$nodosHuerfanos = NodoDialogo::whereDoesntHave('respuestas')
    ->whereDoesntHave('respuestasEntrantes')
    ->where('es_inicial', false)
    ->count();
echo sprintf("  Nodos huérfanos (sin conexiones): %d\n", $nodosHuerfanos);

// Respuestas con nodo siguiente inválido
$respuestasInvalidas = RespuestaDialogo::whereNotNull('nodo_siguiente_id')
    ->whereDoesntHave('nodoSiguiente')
    ->count();
echo sprintf("  Respuestas con destino inválido: %d\n", $respuestasInvalidas);

echo "\n";

// 8. Resumen de datos para migración
echo "8. RESUMEN PARA MIGRACIÓN\n";
echo "-------------------------\n";

$totalRegistros = array_sum($conteos);
echo sprintf("  Total de registros a migrar: %d\n", $totalRegistros);

$datosCriticos = [
    'Diálogos activos' => $dialogos->where('estado', 'activo')->count(),
    'Nodos con posiciones' => $nodosConPosicion,
    'Sesiones activas' => $sesionesActivas,
    'Decisiones históricas' => $decisiones->count(),
];

echo "\n  Datos críticos:\n";
foreach ($datosCriticos as $tipo => $cantidad) {
    echo sprintf("    - %s: %d\n", $tipo, $cantidad);
}

echo "\n";
echo "========================================\n";
echo "ANÁLISIS COMPLETADO\n";
echo "========================================\n\n";

// Retornar datos para uso programático
return [
    'conteos' => $conteos,
    'dialogos' => [
        'total' => $dialogos->count(),
        'activos' => $dialogos->where('estado', 'activo')->count(),
        'con_nodos' => $dialogosConNodos->count(),
    ],
    'nodos' => [
        'total' => $nodos->count(),
        'con_posicion' => $nodosConPosicion,
        'iniciales' => $nodosIniciales,
        'finales' => $nodosFinales,
    ],
    'respuestas' => [
        'total' => $respuestas->count(),
        'activas' => $respuestas->where('activo', true)->count(),
        'con_destino' => $respuestasConDestino,
    ],
    'sesiones' => [
        'total' => $sesionesDialogos->count(),
        'activas' => $sesionesActivas,
    ],
    'decisiones' => [
        'total' => $decisiones->count(),
        'con_tiempo' => $decisionesConTiempo,
    ],
    'problemas' => [
        'dialogos_sin_nodos' => $dialogosSinNodos,
        'dialogos_sin_inicial' => $dialogosSinInicial,
        'dialogos_sin_final' => $dialogosSinFinal,
        'nodos_huerfanos' => $nodosHuerfanos,
        'respuestas_invalidas' => $respuestasInvalidas,
    ],
];
