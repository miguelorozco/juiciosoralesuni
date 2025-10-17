<?php

namespace App\Http\Controllers;

use App\Models\Dialogo;
use App\Models\NodoDialogo;
use App\Models\RespuestaDialogo;
use App\Models\RolDisponible;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class NodoDialogoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/dialogos/{dialogo}/nodos",
     *     summary="Crear nodo de diálogo",
     *     description="Crea un nuevo nodo en un diálogo (solo admin e instructor)",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rol_id", "contenido"},
     *             @OA\Property(property="rol_id", type="integer", example=1),
     *             @OA\Property(property="titulo", type="string", example="Apertura del juicio"),
     *             @OA\Property(property="contenido", type="string", example="Buenos días, damas y caballeros del jurado..."),
     *             @OA\Property(property="instrucciones", type="string", example="El juez debe iniciar el juicio"),
     *             @OA\Property(property="tipo", type="string", enum={"inicio", "desarrollo", "decision", "final"}, example="inicio"),
     *             @OA\Property(property="condiciones", type="object"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nodo creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function store(Request $request, Dialogo $dialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            Log::info('Crear nodo - inicio', [
                'usuario_id' => optional($user)->id,
                'dialogo_id' => $dialogo->id,
                'payload' => $request->all()
            ]);
            
            if (!$dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este diálogo'
                ], 403);
            }

            $validated = $request->validate([
                'rol_id' => 'required|exists:roles_disponibles,id',
                'titulo' => 'nullable|string|max:200',
                'contenido' => 'required|string',
                'instrucciones' => 'nullable|string',
                'tipo' => 'sometimes|in:inicio,desarrollo,decision,final',
                'condiciones' => 'nullable|array',
                'metadata' => 'nullable|array',
                'es_inicial' => 'boolean',
                'es_final' => 'boolean',
            ]);

            // Calcular orden
            $ultimoOrden = $dialogo->nodos()->max('orden') ?? 0;
            $validated['orden'] = $ultimoOrden + 1;

            $nodo = $dialogo->nodos()->create($validated);
            $nodo->load(['rol']);

            Log::info('Crear nodo - ok', [
                'dialogo_id' => $dialogo->id,
                'nodo_id' => $nodo->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $nodo,
                'message' => 'Nodo creado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Crear nodo - error de validación', [
                'dialogo_id' => $dialogo->id,
                'errores' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Crear nodo - excepción', [
                'dialogo_id' => $dialogo->id,
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el nodo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/nodos/{nodoDialogo}",
     *     summary="Actualizar nodo de diálogo",
     *     description="Actualiza un nodo de diálogo existente",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nodo actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function update(Request $request, NodoDialogo $nodoDialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$nodoDialogo->dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este nodo'
                ], 403);
            }

            $validated = $request->validate([
                'rol_id' => 'sometimes|required|exists:roles_disponibles,id',
                'titulo' => 'nullable|string|max:200',
                'contenido' => 'sometimes|required|string',
                'instrucciones' => 'nullable|string',
                'tipo' => 'sometimes|in:inicio,desarrollo,decision,final',
                'condiciones' => 'nullable|array',
                'metadata' => 'nullable|array',
                'es_inicial' => 'boolean',
                'es_final' => 'boolean',
            ]);

            $nodoDialogo->update($validated);
            $nodoDialogo->load(['rol', 'respuestas']);

            return response()->json([
                'success' => true,
                'data' => $nodoDialogo,
                'message' => 'Nodo actualizado exitosamente'
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
                'message' => 'Error al actualizar el nodo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/nodos/{nodoDialogo}",
     *     summary="Eliminar nodo de diálogo",
     *     description="Elimina un nodo de diálogo",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nodo eliminado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function destroy(NodoDialogo $nodoDialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$nodoDialogo->dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este nodo'
                ], 403);
            }

            $nodoDialogo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nodo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el nodo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/nodos/{nodoDialogo}/marcar-inicial",
     *     summary="Marcar nodo como inicial",
     *     description="Marca un nodo como el nodo inicial del diálogo",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Nodo marcado como inicial exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function marcarComoInicial(NodoDialogo $nodoDialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$nodoDialogo->dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este nodo'
                ], 403);
            }

            $nodoDialogo->marcarComoInicial();

            return response()->json([
                'success' => true,
                'data' => $nodoDialogo,
                'message' => 'Nodo marcado como inicial exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el nodo como inicial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/nodos/{nodoDialogo}/respuestas",
     *     summary="Agregar respuesta al nodo",
     *     description="Agrega una nueva respuesta a un nodo de diálogo",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"texto"},
     *             @OA\Property(property="texto", type="string", example="Sí, estoy de acuerdo"),
     *             @OA\Property(property="descripcion", type="string", example="El acusado acepta los cargos"),
     *             @OA\Property(property="nodo_siguiente_id", type="integer", example=2),
     *             @OA\Property(property="condiciones", type="object"),
     *             @OA\Property(property="consecuencias", type="object"),
     *             @OA\Property(property="puntuacion", type="integer", example=10),
     *             @OA\Property(property="color", type="string", example="#28a745")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Respuesta agregada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function agregarRespuesta(Request $request, NodoDialogo $nodoDialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$nodoDialogo->dialogo->puedeSerEditadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este nodo'
                ], 403);
            }

            $validated = $request->validate([
                'texto' => 'required|string|max:500',
                'descripcion' => 'nullable|string',
                'nodo_siguiente_id' => 'nullable|exists:nodos_dialogo,id',
                'condiciones' => 'nullable|array',
                'consecuencias' => 'nullable|array',
                'puntuacion' => 'nullable|integer',
                'color' => 'nullable|string|max:7',
            ]);

            $respuesta = $nodoDialogo->agregarRespuesta($validated);
            $respuesta->load(['nodoSiguiente']);

            return response()->json([
                'success' => true,
                'data' => $respuesta,
                'message' => 'Respuesta agregada exitosamente'
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
                'message' => 'Error al agregar la respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/nodos/{nodoDialogo}/respuestas",
     *     summary="Obtener respuestas del nodo",
     *     description="Obtiene todas las respuestas de un nodo",
     *     tags={"Nodos de Diálogo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Respuestas obtenidas exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerRespuestas(NodoDialogo $nodoDialogo): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$nodoDialogo->dialogo->puedeSerUsadoPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este nodo'
                ], 403);
            }

            $respuestas = $nodoDialogo->respuestas()
                ->with(['nodoSiguiente'])
                ->orderBy('orden')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $respuestas,
                'message' => 'Respuestas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las respuestas: ' . $e->getMessage()
            ], 500);
        }
    }
}
