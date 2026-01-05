<?php

namespace App\Http\Controllers;

/**
 * @deprecated Este controlador usa modelos antiguos (Dialogo v1).
 * Se mantiene temporalmente para compatibilidad.
 * TODO: Refactorizar completamente para usar DialogoV2 después de migración completa.
 */

use App\Models\DialogoV2 as Dialogo;
use App\Models\NodoDialogoV2 as NodoDialogo;
use App\Models\RespuestaDialogoV2 as RespuestaDialogo;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DialogoImportController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/dialogos/import",
     *     summary="Importar diálogo desde JSON",
     *     description="Importa un diálogo completo con nodos, conexiones y configuraciones desde un archivo JSON",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="dialogo", type="object", description="Datos del diálogo"),
     *             @OA\Property(property="nodos", type="array", description="Array de nodos"),
     *             @OA\Property(property="conexiones", type="array", description="Array de conexiones")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo importado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function importar(Request $request): JsonResponse
    {
        // Forzar escritura inmediata de logs
        Log::getLogger()->pushHandler(new \Monolog\Handler\StreamHandler(storage_path('logs/laravel.log'), \Monolog\Logger::DEBUG));
        
        try {
            $user = auth()->user();
            Log::info('=== INICIO IMPORTACIÓN DIÁLOGO ===', [
                'usuario_id' => optional($user)->id,
                'usuario_email' => optional($user)->email,
                'usuario_tipo' => optional($user)->tipo,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'preview_keys' => array_keys($request->all()),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            if (!in_array($user->tipo, ['admin', 'instructor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para importar diálogos'
                ], 403);
            }

            $validated = $request->validate([
                'dialogo' => 'required|array',
                'dialogo.nombre' => 'required|string|max:200',
                'dialogo.descripcion' => 'required|string',
                'dialogo.publico' => 'boolean',
                'nodos' => 'required|array',
                'nodos.*.id' => 'required|string',
                'nodos.*.titulo' => 'required|string|max:200',
                'nodos.*.contenido' => 'required|string',
                'nodos.*.rol_nombre' => 'required|string',
                'nodos.*.tipo' => 'required|in:inicio,desarrollo,decision,final',
                'nodos.*.es_inicial' => 'boolean',
                'nodos.*.es_final' => 'boolean',
                'nodos.*.posicion' => 'required|array',
                'nodos.*.posicion.x' => 'required|numeric',
                'nodos.*.posicion.y' => 'required|numeric',
                'conexiones' => 'array',
                'conexiones.*.desde' => 'required|string',
                'conexiones.*.hacia' => 'required|string',
                'conexiones.*.texto' => 'required|string',
                'conexiones.*.color' => 'string',
                'conexiones.*.puntuacion' => 'numeric'
            ]);

            DB::beginTransaction();
            Log::info('=== VALIDACIÓN IMPORTACIÓN OK ===', [
                'dialogo_nombre' => $request->input('dialogo.nombre'),
                'dialogo_descripcion' => substr($request->input('dialogo.descripcion', ''), 0, 100) . '...',
                'dialogo_publico' => $request->input('dialogo.publico'),
                'nodos_count' => count($request->input('nodos', [])),
                'conexiones_count' => count($request->input('conexiones', [])),
                'timestamp' => now()->toDateTimeString()
            ]);

            try {
                // Crear el diálogo
                $dialogo = Dialogo::create([
                    'nombre' => $validated['dialogo']['nombre'],
                    'descripcion' => $validated['dialogo']['descripcion'],
                    'publico' => $validated['dialogo']['publico'] ?? false,
                    'estado' => 'borrador',
                    'version' => '1.0.0',
                    'creado_por' => $user->id
                ]);
                
                Log::info('=== DIÁLOGO CREADO ===', [
                    'dialogo_id' => $dialogo->id,
                    'nombre' => $dialogo->nombre,
                    'usuario_id' => $user->id,
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Mapeo de IDs temporales a IDs reales
                $nodoIdMap = [];
                
                // Crear nodos
                Log::info('=== INICIANDO CREACIÓN DE NODOS ===', [
                    'total_nodos' => count($validated['nodos']),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                foreach ($validated['nodos'] as $index => $nodoData) {
                    Log::info("=== PROCESANDO NODO {$index} ===", [
                        'temp_id' => $nodoData['id'],
                        'titulo' => $nodoData['titulo'],
                        'tipo' => $nodoData['tipo'],
                        'rol_nombre' => $nodoData['rol_nombre'],
                        'posicion' => $nodoData['posicion'] ?? null
                    ]);
                    
                    // Buscar o crear el rol
                    $rol = RolDisponible::where('nombre', $nodoData['rol_nombre'])->first();
                    if (!$rol) {
                        $rol = RolDisponible::create([
                            'nombre' => $nodoData['rol_nombre'],
                            'descripcion' => 'Rol importado automáticamente',
                            'color' => '#007bff',
                            'icono' => 'bi-person',
                            'activo' => true,
                            'orden' => 0
                        ]);
                        Log::info("=== ROL CREADO ===", [
                            'rol_id' => $rol->id,
                            'rol_nombre' => $rol->nombre
                        ]);
                    } else {
                        Log::info("=== ROL EXISTENTE ===", [
                            'rol_id' => $rol->id,
                            'rol_nombre' => $rol->nombre
                        ]);
                    }

                    $nodo = NodoDialogo::create([
                        'dialogo_id' => $dialogo->id,
                        'rol_id' => $rol->id,
                        'titulo' => $nodoData['titulo'],
                        'contenido' => $nodoData['contenido'],
                        'instrucciones' => $nodoData['instrucciones'] ?? null,
                        'tipo' => $nodoData['tipo'],
                        'posicion_x' => $nodoData['posicion']['x'] ?? 0,
                        'posicion_y' => $nodoData['posicion']['y'] ?? 0,
                        'es_inicial' => $nodoData['es_inicial'] ?? false,
                        'es_final' => $nodoData['es_final'] ?? false,
                        'orden' => $index + 1,
                        'activo' => true,
                        'metadata' => []
                    ]);

                    $nodoIdMap[$nodoData['id']] = $nodo->id;
                    Log::info("=== NODO CREADO EXITOSAMENTE ===", [
                        'dialogo_id' => $dialogo->id,
                        'nodo_id' => $nodo->id,
                        'temp_id' => $nodoData['id'],
                        'titulo' => $nodo->titulo,
                        'tipo' => $nodo->tipo,
                        'rol_id' => $rol->id,
                        'posicion' => $nodo->metadata['posicion'] ?? null,
                        'es_inicial' => $nodo->es_inicial,
                        'es_final' => $nodo->es_final,
                        'timestamp' => now()->toDateTimeString()
                    ]);
                }

                // Crear conexiones
                Log::info('=== INICIANDO CREACIÓN DE CONEXIONES ===', [
                    'total_conexiones' => count($validated['conexiones'] ?? []),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                foreach ($validated['conexiones'] ?? [] as $index => $conexionData) {
                    Log::info("=== PROCESANDO CONEXIÓN {$index} ===", [
                        'desde_temp' => $conexionData['desde'],
                        'hacia_temp' => $conexionData['hacia'],
                        'texto' => $conexionData['texto']
                    ]);
                    
                    $nodoOrigenId = $nodoIdMap[$conexionData['desde']] ?? null;
                    $nodoDestinoId = $nodoIdMap[$conexionData['hacia']] ?? null;

                    if ($nodoOrigenId && $nodoDestinoId) {
                        $respuesta = RespuestaDialogo::create([
                            'nodo_padre_id' => $nodoOrigenId,
                            'nodo_siguiente_id' => $nodoDestinoId,
                            'texto' => $conexionData['texto'],
                            'descripcion' => $conexionData['descripcion'] ?? null,
                            'color' => $conexionData['color'] ?? '#007bff',
                            'puntuacion' => $conexionData['puntuacion'] ?? 0,
                            'activo' => true,
                            'orden' => 1
                        ]);
                        Log::info("=== CONEXIÓN CREADA EXITOSAMENTE ===", [
                            'respuesta_id' => $respuesta->id,
                            'desde_temp' => $conexionData['desde'],
                            'hacia_temp' => $conexionData['hacia'],
                            'desde_real' => $nodoOrigenId,
                            'hacia_real' => $nodoDestinoId,
                            'texto' => $conexionData['texto'],
                            'timestamp' => now()->toDateTimeString()
                        ]);
                    } else {
                        Log::warning("=== CONEXIÓN OMITIDA - REFERENCIA INVÁLIDA ===", [
                            'desde_temp' => $conexionData['desde'] ?? null,
                            'hacia_temp' => $conexionData['hacia'] ?? null,
                            'desde_encontrado' => $nodoOrigenId !== null,
                            'hacia_encontrado' => $nodoDestinoId !== null,
                            'timestamp' => now()->toDateTimeString()
                        ]);
                    }
                }

                DB::commit();
                Log::info('=== IMPORTACIÓN COMPLETADA EXITOSAMENTE ===', [
                    'dialogo_id' => $dialogo->id,
                    'nodos_creados' => count($validated['nodos']),
                    'conexiones_creadas' => count($validated['conexiones'] ?? []),
                    'timestamp' => now()->toDateTimeString()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Diálogo importado exitosamente',
                    'data' => [
                        'dialogo_id' => $dialogo->id,
                        'nodos_creados' => count($validated['nodos']),
                        'conexiones_creadas' => count($validated['conexiones'] ?? [])
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            Log::error('=== ERROR DE VALIDACIÓN EN IMPORTACIÓN ===', [
                'errores' => $e->errors(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('=== ERROR CRÍTICO EN IMPORTACIÓN ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al importar diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/dialogos/export/{dialogo}",
     *     summary="Exportar diálogo a JSON",
     *     description="Exporta un diálogo completo con nodos, conexiones y configuraciones a formato JSON",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="dialogo",
     *         in="path",
     *         description="ID del diálogo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo exportado exitosamente"
     *     )
     * )
     */
    public function exportar(Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para exportar este diálogo'
                ], 403);
            }

            $nodos = $dialogo->nodos()->with(['rol', 'respuestas.nodoSiguiente'])->get();
            
            $exportData = [
                'dialogo' => [
                    'nombre' => $dialogo->nombre,
                    'descripcion' => $dialogo->descripcion,
                    'publico' => $dialogo->publico,
                    'activo' => $dialogo->activo
                ],
                'nodos' => $nodos->map(function($nodo) {
                    return [
                        'id' => 'nodo_' . $nodo->id,
                        'titulo' => $nodo->titulo,
                        'contenido' => $nodo->contenido,
                        'instrucciones' => $nodo->instrucciones,
                        'rol_nombre' => $nodo->rol->nombre ?? 'Sin rol',
                        'tipo' => $nodo->tipo,
                        'es_inicial' => $nodo->es_inicial,
                        'es_final' => $nodo->es_final,
                        'posicion' => ['x' => $nodo->posicion_x, 'y' => $nodo->posicion_y]
                    ];
                })->toArray(),
                'conexiones' => $nodos->flatMap(function($nodo) {
                    return $nodo->respuestas->map(function($respuesta) use ($nodo) {
                        return [
                            'desde' => 'nodo_' . $nodo->id,
                            'hacia' => $respuesta->nodo_siguiente_id ? 'nodo_' . $respuesta->nodo_siguiente_id : null,
                            'texto' => $respuesta->texto,
                            'descripcion' => $respuesta->descripcion,
                            'color' => $respuesta->color,
                            'puntuacion' => $respuesta->puntuacion
                        ];
                    })->filter(function($conexion) {
                        return $conexion['hacia'] !== null;
                    });
                })->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'message' => 'Diálogo exportado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar diálogo: ' . $e->getMessage()
            ], 500);
        }
    }
}
