<?php

namespace App\Http\Controllers;

use App\Models\Dialogo;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DialogoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dialogos",
     *     summary="Listar diálogos",
     *     description="Obtiene una lista paginada de diálogos disponibles para el usuario",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="buscar",
     *         in="query",
     *         description="Término de búsqueda",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado",
     *         @OA\Schema(type="string", enum={"borrador", "activo", "archivado"})
     *     ),
     *     @OA\Parameter(
     *         name="publico",
     *         in="query",
     *         description="Filtrar por visibilidad",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogos obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $query = Dialogo::with(['creador', 'nodos.rol'])
                ->disponiblesParaUsuario($user);

            // Filtros opcionales
            if ($request->has('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('nombre', 'like', '%' . $buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $buscar . '%');
                });
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('publico')) {
                $query->where('publico', $request->boolean('publico'));
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'updated_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 20);
            $dialogos = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $dialogos,
                'message' => 'Diálogos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los diálogos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/dialogos",
     *     summary="Crear diálogo",
     *     description="Crea un nuevo diálogo ramificado (solo admin e instructor)",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Juicio Civil - Contrato"),
     *             @OA\Property(property="descripcion", type="string", example="Simulación de juicio civil sobre incumplimiento de contrato"),
     *             @OA\Property(property="plantilla_id", type="integer", example=1),
     *             @OA\Property(property="publico", type="boolean", example=false),
     *             @OA\Property(property="configuracion", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Diálogo creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Crear diálogo - inicio', [
                'usuario_id' => auth()->id(),
                'payload' => $request->all()
            ]);
            
            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
                'plantilla_id' => 'nullable|exists:plantillas_sesiones,id',
                'publico' => 'boolean',
                'configuracion' => 'nullable|array',
                'nodos' => 'nullable|array',
                'conexiones' => 'nullable|array',
            ]);

            $validated['creado_por'] = auth()->id();
            $validated['estado'] = 'borrador';

            $dialogo = Dialogo::create($validated);
            
            // Crear nodos si se proporcionan
            if (isset($validated['nodos']) && is_array($validated['nodos'])) {
                foreach ($validated['nodos'] as $nodoData) {
                    $nodo = $dialogo->nodos()->create([
                        'titulo' => $nodoData['titulo'] ?? 'Sin título',
                        'contenido' => $nodoData['contenido'] ?? '',
                        'tipo' => $nodoData['tipo'] ?? 'desarrollo',
                        'rol_id' => $nodoData['rol_id'] ?? null,
                        'es_inicial' => $nodoData['es_inicial'] ?? false,
                        'es_final' => $nodoData['es_final'] ?? false,
                        'orden' => $nodoData['orden'] ?? 0,
                        'metadata' => json_encode([
                            'x' => $nodoData['posicion']['x'] ?? 0,
                            'y' => $nodoData['posicion']['y'] ?? 0
                        ])
                    ]);
                    
                    // Crear respuestas si se proporcionan
                    if (isset($nodoData['respuestas']) && is_array($nodoData['respuestas'])) {
                        foreach ($nodoData['respuestas'] as $respuestaData) {
                            $nodo->respuestas()->create([
                                'texto' => $respuestaData['texto'] ?? 'Sin texto',
                                'descripcion' => $respuestaData['descripcion'] ?? '',
                                'orden' => $respuestaData['orden'] ?? 1,
                                'puntuacion' => $respuestaData['puntuacion'] ?? 0,
                                'color' => $respuestaData['color'] ?? '#007bff',
                                'activo' => $respuestaData['activo'] ?? true
                            ]);
                        }
                    }
                }
            }
            
            // Crear conexiones si se proporcionan
            if (isset($validated['conexiones']) && is_array($validated['conexiones'])) {
                foreach ($validated['conexiones'] as $conexionData) {
                    $dialogo->conexiones()->create([
                        'desde' => $conexionData['desde'],
                        'hacia' => $conexionData['hacia'],
                        'desde_punto' => $conexionData['desde_punto'] ?? 0,
                        'hacia_punto' => $conexionData['hacia_punto'] ?? 0,
                        'texto' => $conexionData['texto'] ?? '',
                        'color' => $conexionData['color'] ?? '#007bff',
                        'puntuacion' => $conexionData['puntuacion'] ?? 0
                    ]);
                }
            }
            
            $dialogo->load(['creador', 'nodos.respuestas']);

            Log::info('Crear diálogo - ok', [
                'dialogo_id' => $dialogo->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo creado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Crear diálogo - error de validación', [
                'errores' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Crear diálogo - excepción', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/dialogos/{dialogo}",
     *     summary="Mostrar diálogo",
     *     description="Obtiene un diálogo específico con todos sus nodos y respuestas",
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
     *         description="Diálogo obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function show(Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este diálogo'
                ], 403);
            }

            $dialogo->load([
                'creador',
                'plantilla',
                'nodos.rol',
                'nodos.respuestas.nodoSiguiente',
                'sesiones'
            ]);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/dialogos/{dialogo}",
     *     summary="Actualizar diálogo",
     *     description="Actualiza un diálogo existente (solo admin e instructor)",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nombre", type="string", example="Juicio Civil - Contrato Actualizado"),
     *             @OA\Property(property="descripcion", type="string", example="Simulación actualizada de juicio civil"),
     *             @OA\Property(property="publico", type="boolean", example=true),
     *             @OA\Property(property="configuracion", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function update(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este diálogo'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'sometimes|required|string|max:200',
                'descripcion' => 'nullable|string',
                'publico' => 'boolean',
                'configuracion' => 'nullable|array',
                'nodos' => 'nullable|array',
                'conexiones' => 'nullable|array',
            ]);

            // Actualizar datos básicos del diálogo
            $dialogo->update([
                'nombre' => $validated['nombre'] ?? $dialogo->nombre,
                'descripcion' => $validated['descripcion'] ?? $dialogo->descripcion,
                'publico' => $validated['publico'] ?? $dialogo->publico,
                'configuracion' => $validated['configuracion'] ?? $dialogo->configuracion,
            ]);
            
            // Actualizar nodos si se proporcionan
            if (isset($validated['nodos']) && is_array($validated['nodos'])) {
                // Eliminar nodos existentes
                $dialogo->nodos()->delete();
                
                // Crear nuevos nodos
                foreach ($validated['nodos'] as $nodoData) {
                    $nodo = $dialogo->nodos()->create([
                        'titulo' => $nodoData['titulo'] ?? 'Sin título',
                        'contenido' => $nodoData['contenido'] ?? '',
                        'tipo' => $nodoData['tipo'] ?? 'desarrollo',
                        'rol_id' => $nodoData['rol_id'] ?? null,
                        'es_inicial' => $nodoData['es_inicial'] ?? false,
                        'es_final' => $nodoData['es_final'] ?? false,
                        'orden' => $nodoData['orden'] ?? 0,
                        'metadata' => json_encode([
                            'x' => $nodoData['posicion']['x'] ?? 0,
                            'y' => $nodoData['posicion']['y'] ?? 0
                        ])
                    ]);
                    
                    // Crear respuestas si se proporcionan
                    if (isset($nodoData['respuestas']) && is_array($nodoData['respuestas'])) {
                        foreach ($nodoData['respuestas'] as $respuestaData) {
                            $nodo->respuestas()->create([
                                'texto' => $respuestaData['texto'] ?? 'Sin texto',
                                'descripcion' => $respuestaData['descripcion'] ?? '',
                                'orden' => $respuestaData['orden'] ?? 1,
                                'puntuacion' => $respuestaData['puntuacion'] ?? 0,
                                'color' => $respuestaData['color'] ?? '#007bff',
                                'activo' => $respuestaData['activo'] ?? true
                            ]);
                        }
                    }
                }
            }
            
            // Actualizar conexiones si se proporcionan
            if (isset($validated['conexiones']) && is_array($validated['conexiones'])) {
                // Eliminar conexiones existentes
                $dialogo->conexiones()->delete();
                
                // Crear nuevas conexiones
                foreach ($validated['conexiones'] as $conexionData) {
                    $dialogo->conexiones()->create([
                        'desde' => $conexionData['desde'],
                        'hacia' => $conexionData['hacia'],
                        'desde_punto' => $conexionData['desde_punto'] ?? 0,
                        'hacia_punto' => $conexionData['hacia_punto'] ?? 0,
                        'texto' => $conexionData['texto'] ?? '',
                        'color' => $conexionData['color'] ?? '#007bff',
                        'puntuacion' => $conexionData['puntuacion'] ?? 0
                    ]);
                }
            }
            
            $dialogo->load(['creador', 'nodos.respuestas']);

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo actualizado exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/dialogos/{dialogo}",
     *     summary="Eliminar diálogo",
     *     description="Elimina un diálogo (solo admin e instructor)",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo eliminado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function destroy(Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este diálogo'
                ], 403);
            }

            // Verificar si el diálogo está siendo usado
            if ($dialogo->sesiones()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el diálogo porque está siendo usado en sesiones'
                ], 409);
            }

            $dialogo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Diálogo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/dialogos/{dialogo}/activar",
     *     summary="Activar diálogo",
     *     description="Activa un diálogo para que esté disponible para uso (solo admin e instructor)",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo activado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function activar(Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para activar este diálogo'
                ], 403);
            }

            // Verificar que tenga al menos un nodo inicial
            if (!$dialogo->nodo_inicial) {
                return response()->json([
                    'success' => false,
                    'message' => 'El diálogo debe tener al menos un nodo inicial para ser activado'
                ], 400);
            }

            $dialogo->activar();

            return response()->json([
                'success' => true,
                'data' => $dialogo,
                'message' => 'Diálogo activado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/dialogos/{dialogo}/copiar",
     *     summary="Copiar diálogo",
     *     description="Crea una copia de un diálogo existente",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre"},
     *             @OA\Property(property="nombre", type="string", example="Copia de Juicio Civil"),
     *             @OA\Property(property="descripcion", type="string", example="Copia del diálogo original")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Diálogo copiado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function copiar(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este diálogo'
                ], 403);
            }

            $validated = $request->validate([
                'nombre' => 'required|string|max:200',
                'descripcion' => 'nullable|string',
            ]);

            $nuevoDialogo = $dialogo->crearCopia($validated['nombre'], $user->id);
            
            if (isset($validated['descripcion'])) {
                $nuevoDialogo->update(['descripcion' => $validated['descripcion']]);
            }

            $nuevoDialogo->load(['creador', 'nodos.rol']);

            return response()->json([
                'success' => true,
                'data' => $nuevoDialogo,
                'message' => 'Diálogo copiado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al copiar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/dialogos/{dialogo}/posiciones",
     *     summary="Actualizar posiciones de nodos",
     *     description="Actualiza las posiciones de los nodos en el editor",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="posiciones", type="object", description="Posiciones de los nodos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Posiciones actualizadas exitosamente"
     *     )
     * )
     */
    public function actualizarPosiciones(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este diálogo'
                ], 403);
            }

            $validated = $request->validate([
                'posiciones' => 'required|array',
                'posiciones.*.x' => 'required|numeric',
                'posiciones.*.y' => 'required|numeric',
            ]);

            $dialogo->actualizarPosicionesNodos($validated['posiciones']);

            return response()->json([
                'success' => true,
                'message' => 'Posiciones actualizadas exitosamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar posiciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/dialogos/{dialogo}/estructura",
     *     summary="Obtener estructura del diálogo",
     *     description="Obtiene la estructura completa del diálogo con nodos y conexiones",
     *     tags={"Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estructura obtenida exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function estructura(Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$dialogo->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este diálogo'
                ], 403);
            }

            $estructura = $dialogo->obtenerEstructuraGrafo();
            $estructura['dialogo'] = $dialogo;
            $estructura['roles_disponibles'] = RolDisponible::activos()->get();
            $estructura['estadisticas'] = [
                'total_nodos' => $dialogo->total_nodos,
                'nodos_iniciales' => $dialogo->nodos()->iniciales()->count(),
                'nodos_finales' => $dialogo->nodos()->finales()->count(),
                'total_respuestas' => $dialogo->nodos()->withCount('respuestas')->get()->sum('respuestas_count'),
            ];

            return response()->json([
                'success' => true,
                'data' => $estructura,
                'message' => 'Estructura del diálogo obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estructura: ' . $e->getMessage()
            ], 500);
        }
    }

    // Métodos para vistas web
    public function indexWeb()
    {
        return view('dialogos.index');
    }
    
    public function createWeb()
    {
        return view('dialogos.editor');
    }
    
    public function showWeb(Dialogo $dialogo)
    {
        return view('dialogos.editor', compact('dialogo'));
    }
    
    public function editWeb(Dialogo $dialogo)
    {
        return view('dialogos.editor', compact('dialogo'));
    }
}