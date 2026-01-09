<?php

namespace App\Http\Controllers;

use App\Models\DialogoV2;
use App\Models\NodoDialogoV2;
use App\Models\RespuestaDialogoV2;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador para el editor visual de diálogos v2
 */
class DialogoV2EditorController extends Controller
{
    /**
     * Listado de diálogos v2
     */
    public function index()
    {
        $dialogos = DialogoV2::withCount(['nodos'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('dialogos.v2.index', compact('dialogos'));
    }

    /**
     * Mostrar el editor visual
     */
    public function show($id)
    {
        $dialogo = DialogoV2::with(['nodos.respuestas'])->findOrFail($id);
        $rolesDisponibles = RolDisponible::activos()->ordenados()->get();
        
        return view('dialogos.v2.editor', [
            'dialogo' => $dialogo,
            'rolesDisponibles' => $rolesDisponibles
        ]);
    }

    /**
     * Crear nuevo diálogo y mostrar editor
     */
    public function create()
    {
        $rolesDisponibles = RolDisponible::activos()->ordenados()->get();
        
        return view('dialogos.v2.editor', [
            'dialogo' => null,
            'rolesDisponibles' => $rolesDisponibles
        ]);
    }

    /**
     * Obtener diálogo completo con estructura
     */
    public function getDialogo($id): JsonResponse
    {
        try {
            $dialogo = DialogoV2::with(['nodos.respuestas'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'dialogo' => $dialogo,
                    'nodos' => $dialogo->nodos->map(function($nodo) {
                        return [
                            'id' => $nodo->id,
                            'dialogo_id' => $nodo->dialogo_id,
                            'tipo' => $nodo->tipo,
                            'titulo' => $nodo->titulo,
                            'contenido' => $nodo->contenido,
                            'menu_text' => $nodo->menu_text,
                            'rol_id' => $nodo->rol_id,
                            'conversant_id' => $nodo->conversant_id,
                            'posicion_x' => $nodo->posicion_x,
                            'posicion_y' => $nodo->posicion_y,
                            'es_inicial' => $nodo->es_inicial,
                            'es_final' => $nodo->es_final,
                            'instrucciones' => $nodo->instrucciones,
                            'activo' => $nodo->activo,
                            'condiciones' => $nodo->condiciones,
                            'consecuencias' => $nodo->consecuencias,
                            'respuestas' => $nodo->respuestas->map(function($respuesta) {
                                return [
                                    'id' => $respuesta->id,
                                    'nodo_padre_id' => $respuesta->nodo_padre_id,
                                    'nodo_siguiente_id' => $respuesta->nodo_siguiente_id,
                                    'texto' => $respuesta->texto,
                                    'orden' => $respuesta->orden,
                                    'puntuacion' => $respuesta->puntuacion,
                                    'color' => $respuesta->color,
                                    'requiere_usuario_registrado' => $respuesta->requiere_usuario_registrado,
                                    'es_opcion_por_defecto' => $respuesta->es_opcion_por_defecto,
                                    'requiere_rol' => $respuesta->requiere_rol,
                                    'condiciones' => $respuesta->condiciones,
                                    'consecuencias' => $respuesta->consecuencias,
                                ];
                            })
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener diálogo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar o actualizar diálogo
     */
    public function save(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:dialogos_v2,id',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'version' => 'nullable|string|max:20',
            'publico' => 'boolean',
            'estado' => 'required|in:borrador,activo,archivado',
            'configuracion' => 'nullable|array',
            'metadata_unity' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($request->has('id') && $request->id) {
                // Actualizar diálogo existente
                $dialogo = DialogoV2::findOrFail($request->id);
                $dialogo->update($request->only([
                    'nombre', 'descripcion', 'version', 'publico', 'estado',
                    'configuracion', 'metadata_unity'
                ]));
            } else {
                // Crear nuevo diálogo
                $dialogo = DialogoV2::create([
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion ?? '',
                    'version' => $request->version ?? '1.0.0',
                    'publico' => $request->publico ?? false,
                    'estado' => $request->estado ?? 'borrador',
                    'configuracion' => $request->configuracion ?? [],
                    'metadata_unity' => $request->metadata_unity ?? [],
                    'creado_por' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Diálogo guardado exitosamente',
                'data' => ['dialogo' => $dialogo]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar diálogo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear o actualizar nodo
     */
    public function saveNodo(Request $request, $dialogoId): JsonResponse
    {
        Log::info('=== SAVE NODO ===');
        Log::info('Dialogo ID: ' . $dialogoId);
        Log::info('Request method: ' . $request->method());
        Log::info('Auth::check(): ' . (Auth::check() ? 'true' : 'false'));
        Log::info('Auth::user(): ' . (Auth::user() ? Auth::user()->email : 'null'));
        Log::info('Request data: ' . json_encode($request->all()));
        
        // Preparar reglas de validación
        $rules = [
            'tipo' => 'required|in:inicio,desarrollo,decision,final,agrupacion,respuesta',
            'titulo' => 'required|string|max:200',
            'contenido' => 'nullable|string',
            'menu_text' => 'nullable|string|max:200',
            'rol_id' => 'nullable|integer|exists:roles_disponibles,id',
            'conversant_id' => 'nullable|integer',
            'posicion_x' => 'required|integer',
            'posicion_y' => 'required|integer',
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
            'instrucciones' => 'nullable|string',
            'activo' => 'boolean',
            'condiciones' => 'nullable|array',
            'consecuencias' => 'nullable|array',
        ];
        
        // Si el ID no es temporal (no empieza con "temp-"), validar que existe
        if ($request->has('id') && $request->id && !str_starts_with($request->id, 'temp-')) {
            $rules['id'] = 'required|exists:nodos_dialogo_v2,id';
        } else {
            // Si es temporal o no viene, solo validar que es string si viene
            $rules['id'] = 'nullable|string';
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::error('Validación fallida: ' . json_encode($validator->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validar que el diálogo existe
            $dialogo = DialogoV2::findOrFail($dialogoId);

            // Determinar si es un nodo nuevo (ID temporal) o existente
            $isNewNode = !$request->has('id') || !$request->id || str_starts_with($request->id, 'temp-');
            
            // Si es inicial, desmarcar otros nodos iniciales
            if ($request->es_inicial) {
                $query = NodoDialogoV2::where('dialogo_id', $dialogoId);
                if (!$isNewNode) {
                    $query->where('id', '!=', $request->id);
                }
                $query->update(['es_inicial' => false]);
            }

            if (!$isNewNode) {
                // Actualizar nodo existente
                $nodo = NodoDialogoV2::where('dialogo_id', $dialogoId)
                    ->findOrFail($request->id);
                
                $nodo->update([
                    'tipo' => $request->tipo,
                    'titulo' => $request->titulo,
                    'contenido' => $request->contenido ?? '',
                    'menu_text' => $request->menu_text ?? '',
                    'rol_id' => $request->rol_id,
                    'conversant_id' => $request->conversant_id,
                    'posicion_x' => $request->posicion_x,
                    'posicion_y' => $request->posicion_y,
                    'es_inicial' => $request->es_inicial ?? false,
                    'es_final' => $request->es_final ?? false,
                    'instrucciones' => $request->instrucciones ?? '',
                    'activo' => $request->activo ?? true,
                    'condiciones' => $request->condiciones ?? [],
                    'consecuencias' => $request->consecuencias ?? []
                ]);
            } else {
                // Crear nuevo nodo
                $nodo = NodoDialogoV2::create([
                    'dialogo_id' => $dialogoId,
                    'tipo' => $request->tipo,
                    'titulo' => $request->titulo,
                    'contenido' => $request->contenido ?? '',
                    'menu_text' => $request->menu_text ?? '',
                    'rol_id' => $request->rol_id,
                    'conversant_id' => $request->conversant_id,
                    'posicion_x' => $request->posicion_x,
                    'posicion_y' => $request->posicion_y,
                    'es_inicial' => $request->es_inicial ?? false,
                    'es_final' => $request->es_final ?? false,
                    'instrucciones' => $request->instrucciones ?? '',
                    'activo' => $request->activo ?? true,
                    'condiciones' => $request->condiciones ?? [],
                    'consecuencias' => $request->consecuencias ?? [],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nodo guardado exitosamente',
                'data' => ['nodo' => $nodo->load('respuestas')]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar nodo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el nodo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar nodo
     */
    public function deleteNodo($dialogoId, $nodoId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $nodo = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->findOrFail($nodoId);

            // Eliminar respuestas asociadas
            RespuestaDialogoV2::where('nodo_padre_id', $nodoId)->delete();
            RespuestaDialogoV2::where('nodo_siguiente_id', $nodoId)->delete();

            // Eliminar nodo
            $nodo->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nodo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar nodo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el nodo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear o actualizar respuesta
     */
    public function saveRespuesta(Request $request, $dialogoId, $nodoId): JsonResponse
    {
        // Preparar reglas de validación
        $rules = [
            'nodo_siguiente_id' => 'nullable|integer|exists:nodos_dialogo_v2,id',
            'texto' => 'nullable|string|max:500',
            'orden' => 'nullable|integer|min:0',
            'puntuacion' => 'nullable|integer',
            'color' => 'nullable|string|max:7',
            'requiere_usuario_registrado' => 'boolean',
            'es_opcion_por_defecto' => 'boolean',
            'requiere_rol' => 'nullable|array',
            'condiciones' => 'nullable|array',
            'consecuencias' => 'nullable|array',
        ];
        
        // Si el ID no es temporal (no empieza con "temp-"), validar que existe
        if ($request->has('id') && $request->id && !str_starts_with($request->id, 'temp-')) {
            $rules['id'] = 'required|exists:respuestas_dialogo_v2,id';
        } else {
            // Si es temporal o no viene, solo validar que es string si viene
            $rules['id'] = 'nullable';
        }
        
        Log::info('=== SAVE RESPUESTA ===', [
            'dialogoId' => $dialogoId,
            'nodoId' => $nodoId,
            'payload' => $request->all()
        ]);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            Log::error('Validación respuesta fallida', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validar que el nodo pertenece al diálogo
            $nodo = NodoDialogoV2::where('dialogo_id', $dialogoId)
                ->findOrFail($nodoId);

            // Valores por defecto
            $texto = $request->texto ?? 'Continuar';
            $orden = $request->has('orden') ? $request->orden : (RespuestaDialogoV2::where('nodo_padre_id', $nodoId)->max('orden') + 1);
            if ($orden === null) {
                $orden = 0;
            }
            $color = $request->color ?? '#007bff';

            if ($request->has('id') && $request->id) {
                // Actualizar respuesta existente
                $respuesta = RespuestaDialogoV2::where('nodo_padre_id', $nodoId)
                    ->findOrFail($request->id);
                
                $respuesta->update($request->only([
                    'nodo_siguiente_id',
                    'puntuacion',
                    'requiere_usuario_registrado', 'es_opcion_por_defecto', 'requiere_rol',
                    'condiciones', 'consecuencias'
                ]) + [
                    'texto' => $texto,
                    'orden' => $orden,
                    'color' => $color,
                ]);
            } else {
                // Crear nueva respuesta
                $respuesta = RespuestaDialogoV2::create([
                    'nodo_padre_id' => $nodoId,
                    'nodo_siguiente_id' => $request->nodo_siguiente_id,
                    'texto' => $texto,
                    'orden' => $orden,
                    'puntuacion' => $request->puntuacion ?? 0,
                    'color' => $color,
                    'requiere_usuario_registrado' => $request->requiere_usuario_registrado ?? false,
                    'es_opcion_por_defecto' => $request->es_opcion_por_defecto ?? false,
                    'requiere_rol' => $request->requiere_rol ?? [],
                    'condiciones' => $request->condiciones ?? [],
                    'consecuencias' => $request->consecuencias ?? [],
                ]);
            }

            DB::commit();

            Log::info('Respuesta guardada', [
                'id' => $respuesta->id,
                'nodo_padre_id' => $respuesta->nodo_padre_id,
                'nodo_siguiente_id' => $respuesta->nodo_siguiente_id,
                'orden' => $respuesta->orden,
                'texto' => $respuesta->texto
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Respuesta guardada exitosamente',
                'data' => ['respuesta' => $respuesta]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar respuesta', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar respuesta
     */
    public function deleteRespuesta($dialogoId, $nodoId, $respuestaId): JsonResponse
    {
        try {
            $respuesta = RespuestaDialogoV2::where('nodo_padre_id', $nodoId)
                ->find($respuestaId);

            if (!$respuesta) {
                Log::warning('DELETE respuesta no encontrada', [
                    'dialogoId' => $dialogoId,
                    'nodoId' => $nodoId,
                    'respuestaId' => $respuestaId
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Respuesta no encontrada (ya eliminada)'
                ]);
            }

            $respuesta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Respuesta eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar respuesta', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar posiciones de múltiples nodos
     */
    public function updatePosiciones(Request $request, $dialogoId): JsonResponse
    {
        Log::info('=== UPDATE POSICIONES ===', [
            'dialogoId' => $dialogoId,
            'payload' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'nodos' => 'required|array',
            'nodos.*.id' => 'required|exists:nodos_dialogo_v2,id',
            'nodos.*.posicion_x' => 'required|integer',
            'nodos.*.posicion_y' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($request->nodos as $nodoData) {
                NodoDialogoV2::where('dialogo_id', $dialogoId)
                    ->where('id', $nodoData['id'])
                    ->update([
                        'posicion_x' => $nodoData['posicion_x'],
                        'posicion_y' => $nodoData['posicion_y'],
                    ]);
            }

            DB::commit();

            Log::info('Posiciones actualizadas OK', ['dialogoId' => $dialogoId, 'nodos' => count($request->nodos)]);

            return response()->json([
                'success' => true,
                'message' => 'Posiciones actualizadas exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar posiciones', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar las posiciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar estructura del diálogo
     */
    public function validar($id): JsonResponse
    {
        try {
            $dialogo = DialogoV2::with(['nodos.respuestas'])->findOrFail($id);
            
            $errores = [];
            $advertencias = [];

            // Validar que hay al menos un nodo inicial
            $nodosIniciales = $dialogo->nodos->where('es_inicial', true);
            if ($nodosIniciales->count() == 0) {
                $errores[] = 'El diálogo debe tener al menos un nodo inicial.';
            } else if ($nodosIniciales->count() > 1) {
                $advertencias[] = 'El diálogo tiene múltiples nodos iniciales. Solo uno será usado.';
            }

            // Validar que hay al menos un nodo final
            if ($dialogo->nodos->where('es_final', true)->count() == 0) {
                $errores[] = 'El diálogo debe tener al menos un nodo final.';
            }

            // Validar que todos los nodos de decisión tienen respuestas
            foreach ($dialogo->nodos->where('tipo', 'decision') as $nodo) {
                if ($nodo->respuestas->count() == 0) {
                    $errores[] = "El nodo de decisión '{$nodo->titulo}' (ID: {$nodo->id}) no tiene respuestas.";
                }
            }

            // Validar que todas las respuestas apuntan a nodos existentes
            foreach ($dialogo->nodos as $nodo) {
                foreach ($nodo->respuestas as $respuesta) {
                    if ($respuesta->nodo_siguiente_id) {
                        $nodoDestino = $dialogo->nodos->firstWhere('id', $respuesta->nodo_siguiente_id);
                        if (!$nodoDestino) {
                            $errores[] = "La respuesta '{$respuesta->texto}' del nodo '{$nodo->titulo}' apunta a un nodo destino (ID: {$respuesta->nodo_siguiente_id}) que no existe.";
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'valido' => count($errores) == 0,
                    'errores' => $errores,
                    'advertencias' => $advertencias
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al validar diálogo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }
}
