<?php

/**
 * Script de Migración de Datos del Sistema de Diálogos v1 a v2
 * 
 * Este script migra todos los datos del sistema actual (v1) al nuevo sistema (v2).
 * 
 * IMPORTANTE: Ejecutar DESPUÉS de crear las tablas v2 y ANTES de eliminar las tablas v1.
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: require 'database/scripts/migrar-datos-dialogos-v2.php';
 * 
 * O crear un comando artisan:
 * php artisan dialogos:migrate-to-v2
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

echo "\n";
echo "========================================\n";
echo "MIGRACIÓN DE DATOS - DIÁLOGOS v1 → v2\n";
echo "========================================\n\n";

// Verificar que las tablas v2 existan
$tablasV2 = ['dialogos_v2', 'nodos_dialogo_v2', 'respuestas_dialogo_v2', 'sesiones_dialogos_v2', 'decisiones_dialogo_v2'];
foreach ($tablasV2 as $tabla) {
    if (!Schema::hasTable($tabla)) {
        echo "❌ ERROR: La tabla {$tabla} no existe. Ejecuta las migraciones primero.\n";
        exit(1);
    }
}

echo "✓ Todas las tablas v2 existen\n\n";

// Iniciar transacción
DB::beginTransaction();

try {
    $stats = [
        'dialogos' => 0,
        'nodos' => 0,
        'respuestas' => 0,
        'sesiones' => 0,
        'decisiones' => 0,
        'errores' => 0,
    ];

    // 1. Migrar diálogos
    echo "1. Migrando diálogos...\n";
    $dialogos = DB::table('dialogos')->get();
    
    foreach ($dialogos as $dialogo) {
        $nuevoDialogo = [
            'id' => $dialogo->id,
            'nombre' => $dialogo->nombre,
            'descripcion' => $dialogo->descripcion,
            'creado_por' => $dialogo->creado_por,
            'plantilla_id' => $dialogo->plantilla_id,
            'publico' => $dialogo->publico,
            'estado' => $dialogo->estado,
            'version' => '1.0.0', // Versión inicial
            'configuracion' => $dialogo->configuracion,
            'metadata_unity' => null, // Se llenará después si es necesario
            'deleted_at' => $dialogo->deleted_at,
            'created_at' => $dialogo->created_at,
            'updated_at' => $dialogo->updated_at,
        ];
        
        DB::table('dialogos_v2')->insert($nuevoDialogo);
        $stats['dialogos']++;
    }
    echo "   ✓ {$stats['dialogos']} diálogos migrados\n\n";

    // 2. Migrar nodos
    echo "2. Migrando nodos...\n";
    $nodos = DB::table('nodos_dialogo')->get();
    
    foreach ($nodos as $nodo) {
        // Extraer posiciones de metadata
        $metadata = $nodo->metadata ? json_decode($nodo->metadata, true) : [];
        $posicion = $metadata['posicion'] ?? ['x' => 0, 'y' => 0];
        
        $nuevoNodo = [
            'id' => $nodo->id,
            'dialogo_id' => $nodo->dialogo_id,
            'rol_id' => $nodo->rol_id,
            'titulo' => $nodo->titulo,
            'contenido' => $nodo->contenido,
            'instrucciones' => $nodo->instrucciones,
            'tipo' => $nodo->tipo,
            'posicion_x' => (int)($posicion['x'] ?? 0),
            'posicion_y' => (int)($posicion['y'] ?? 0),
            'es_inicial' => $nodo->es_inicial,
            'es_final' => $nodo->es_final,
            'condiciones' => $nodo->condiciones,
            'consecuencias' => null, // No existe en v1, se agregará después si es necesario
            'metadata' => $nodo->metadata,
            'orden' => $nodo->orden,
            'activo' => true, // Por defecto activo
            'created_at' => $nodo->created_at,
            'updated_at' => $nodo->updated_at,
        ];
        
        DB::table('nodos_dialogo_v2')->insert($nuevoNodo);
        $stats['nodos']++;
    }
    echo "   ✓ {$stats['nodos']} nodos migrados\n";
    echo "   ✓ Posiciones extraídas de metadata JSON\n\n";

    // 3. Migrar respuestas
    echo "3. Migrando respuestas...\n";
    $respuestas = DB::table('respuestas_dialogo')->get();
    
    foreach ($respuestas as $respuesta) {
        $nuevaRespuesta = [
            'id' => $respuesta->id,
            'nodo_padre_id' => $respuesta->nodo_padre_id,
            'nodo_siguiente_id' => $respuesta->nodo_siguiente_id,
            'texto' => $respuesta->texto,
            'descripcion' => $respuesta->descripcion,
            'orden' => $respuesta->orden,
            'puntuacion' => $respuesta->puntuacion,
            'color' => $respuesta->color,
            'condiciones' => $respuesta->condiciones,
            'consecuencias' => $respuesta->consecuencias,
            'requiere_usuario_registrado' => false, // Por defecto, todas disponibles
            'es_opcion_por_defecto' => false, // Se configurará después si es necesario
            'requiere_rol' => null, // Se configurará después si es necesario
            'activo' => $respuesta->activo,
            'created_at' => $respuesta->created_at,
            'updated_at' => $respuesta->updated_at,
        ];
        
        DB::table('respuestas_dialogo_v2')->insert($nuevaRespuesta);
        $stats['respuestas']++;
    }
    echo "   ✓ {$stats['respuestas']} respuestas migradas\n\n";

    // 4. Migrar sesiones de diálogos
    echo "4. Migrando sesiones de diálogos...\n";
    $sesionesDialogos = DB::table('sesiones_dialogos')->get();
    
    foreach ($sesionesDialogos as $sesionDialogo) {
        $nuevaSesion = [
            'id' => $sesionDialogo->id,
            'sesion_id' => $sesionDialogo->sesion_id,
            'dialogo_id' => $sesionDialogo->dialogo_id,
            'nodo_actual_id' => $sesionDialogo->nodo_actual_id,
            'estado' => $sesionDialogo->estado,
            'fecha_inicio' => $sesionDialogo->fecha_inicio,
            'fecha_fin' => $sesionDialogo->fecha_fin,
            'variables' => $sesionDialogo->variables,
            'configuracion' => $sesionDialogo->configuracion,
            'historial_nodos' => [], // Inicializar como array vacío
            'audio_mp3_completo' => null, // Campos de audio - inicialmente NULL
            'audio_duracion_completo' => null,
            'audio_grabado_en' => null,
            'audio_procesado' => false,
            'audio_habilitado' => false,
            'created_at' => $sesionDialogo->created_at,
            'updated_at' => $sesionDialogo->updated_at,
        ];
        
        DB::table('sesiones_dialogos_v2')->insert($nuevaSesion);
        $stats['sesiones']++;
    }
    echo "   ✓ {$stats['sesiones']} sesiones migradas\n\n";

    // 5. Migrar decisiones
    echo "5. Migrando decisiones...\n";
    $decisiones = DB::table('decisiones_sesion')->get();
    
    // Necesitamos mapear sesion_id a sesion_dialogo_id
    $mapaSesiones = DB::table('sesiones_dialogos_v2')
        ->pluck('id', 'sesion_id')
        ->toArray();
    
    foreach ($decisiones as $decision) {
        // Buscar sesion_dialogo_id correspondiente
        $sesionDialogoId = $mapaSesiones[$decision->sesion_id] ?? null;
        
        if (!$sesionDialogoId) {
            echo "   ⚠ Advertencia: No se encontró sesion_dialogo_id para sesion_id {$decision->sesion_id}\n";
            $stats['errores']++;
            continue;
        }
        
        $nuevaDecision = [
            'id' => $decision->id,
            'sesion_dialogo_id' => $sesionDialogoId,
            'nodo_dialogo_id' => $decision->nodo_dialogo_id,
            'respuesta_id' => $decision->respuesta_id,
            'usuario_id' => $decision->usuario_id,
            'rol_id' => $decision->rol_id,
            'texto_respuesta' => $decision->decision_texto,
            'puntuacion_obtenida' => 0, // Se calculará después si es necesario
            'calificacion_profesor' => null, // Campos de evaluación - inicialmente NULL
            'notas_profesor' => null,
            'evaluado_por' => null,
            'fecha_evaluacion' => null,
            'estado_evaluacion' => 'pendiente', // Todas las decisiones migradas como pendientes
            'justificacion_estudiante' => null,
            'retroalimentacion' => null,
            'audio_mp3' => null, // Campos de audio - inicialmente NULL
            'audio_duracion' => null,
            'audio_grabado_en' => null,
            'audio_procesado' => false,
            'tiempo_respuesta' => $decision->tiempo_respuesta,
            'fue_opcion_por_defecto' => false, // No existe en v1
            'usuario_registrado' => $decision->usuario_id !== null, // Determinar si estaba registrado
            'metadata' => $decision->metadata,
            'created_at' => $decision->fecha_decision ?? $decision->created_at,
            'updated_at' => $decision->updated_at,
        ];
        
        // Calcular puntuación si hay respuesta
        if ($decision->respuesta_id) {
            $respuesta = DB::table('respuestas_dialogo_v2')->find($decision->respuesta_id);
            if ($respuesta) {
                $nuevaDecision['puntuacion_obtenida'] = $respuesta->puntuacion;
            }
        }
        
        DB::table('decisiones_dialogo_v2')->insert($nuevaDecision);
        $stats['decisiones']++;
    }
    echo "   ✓ {$stats['decisiones']} decisiones migradas\n";
    if ($stats['errores'] > 0) {
        echo "   ⚠ {$stats['errores']} decisiones con errores\n";
    }
    echo "\n";

    // 6. Validar integridad
    echo "6. Validando integridad de datos...\n";
    
    $errores = [];
    
    // Verificar que todos los diálogos tienen nodos
    $dialogosSinNodos = DB::table('dialogos_v2')
        ->whereNotIn('id', function($query) {
            $query->select('dialogo_id')->from('nodos_dialogo_v2');
        })
        ->count();
    
    if ($dialogosSinNodos > 0) {
        $errores[] = "{$dialogosSinNodos} diálogos sin nodos";
    }
    
    // Verificar que todos los nodos tienen diálogo válido
    $nodosInvalidos = DB::table('nodos_dialogo_v2')
        ->whereNotIn('dialogo_id', function($query) {
            $query->select('id')->from('dialogos_v2');
        })
        ->count();
    
    if ($nodosInvalidos > 0) {
        $errores[] = "{$nodosInvalidos} nodos con diálogo inválido";
    }
    
    // Verificar que todas las respuestas tienen nodo padre válido
    $respuestasInvalidas = DB::table('respuestas_dialogo_v2')
        ->whereNotIn('nodo_padre_id', function($query) {
            $query->select('id')->from('nodos_dialogo_v2');
        })
        ->count();
    
    if ($respuestasInvalidas > 0) {
        $errores[] = "{$respuestasInvalidas} respuestas con nodo padre inválido";
    }
    
    if (empty($errores)) {
        echo "   ✓ Integridad validada correctamente\n\n";
    } else {
        echo "   ⚠ Errores encontrados:\n";
        foreach ($errores as $error) {
            echo "      - {$error}\n";
        }
        echo "\n";
    }

    // Confirmar transacción
    DB::commit();
    
    echo "========================================\n";
    echo "MIGRACIÓN COMPLETADA\n";
    echo "========================================\n\n";
    echo "Resumen:\n";
    echo "  - Diálogos: {$stats['dialogos']}\n";
    echo "  - Nodos: {$stats['nodos']}\n";
    echo "  - Respuestas: {$stats['respuestas']}\n";
    echo "  - Sesiones: {$stats['sesiones']}\n";
    echo "  - Decisiones: {$stats['decisiones']}\n";
    if ($stats['errores'] > 0) {
        echo "  - Errores: {$stats['errores']}\n";
    }
    echo "\n";
    echo "✓ Todos los datos han sido migrados a las tablas v2\n";
    echo "✓ Puedes verificar los datos antes de eliminar las tablas v1\n";
    echo "\n";

    return $stats;

} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n";
    echo "========================================\n";
    echo "ERROR EN LA MIGRACIÓN\n";
    echo "========================================\n\n";
    echo "Error: {$e->getMessage()}\n";
    echo "Archivo: {$e->getFile()}\n";
    echo "Línea: {$e->getLine()}\n";
    echo "\n";
    echo "La transacción ha sido revertida. No se han realizado cambios.\n";
    echo "\n";
    
    throw $e;
}
