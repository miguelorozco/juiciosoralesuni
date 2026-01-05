<?php

namespace App\Http\Controllers;

/**
 * @deprecated Este controlador usa modelos antiguos (SesionDialogo v1).
 * Se mantiene temporalmente para compatibilidad.
 * TODO: Refactorizar completamente para usar SesionDialogoV2 después de migración completa.
 */

use App\Models\SesionDialogoV2 as SesionDialogo;
use App\Models\DecisionDialogoV2 as DecisionSesion;
use App\Models\NodoDialogoV2 as NodoDialogo;
use App\Models\RespuestaDialogoV2 as RespuestaDialogo;
use App\Models\SesionJuicio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DialogoFlujoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/sesiones/{sesion}/iniciar-dialogo",
     *     summary="Iniciar diálogo en sesión",
     *     description="Inicia un diálogo específico en una sesión de juicio",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sesion",
     *         in="path",
     *         description="ID de la sesión",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dialogo_id"},
     *             @OA\Property(property="dialogo_id", type="integer", example=1),
     *             @OA\Property(property="configuracion", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo iniciado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function iniciarDialogo(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Verificar permisos
            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar esta sesión'
                ], 403);
            }

            $validated = $request->validate([
                'dialogo_id' => 'required|exists:dialogos_v2,id',
                'configuracion' => 'nullable|array',
            ]);

            // Verificar que no haya un diálogo activo
            $dialogoActivo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->first();

            if ($dialogoActivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya hay un diálogo activo en esta sesión'
                ], 400);
            }

            // Crear sesión de diálogo
            $sesionDialogo = SesionDialogo::create([
                'sesion_id' => $sesion->id,
                'dialogo_id' => $validated['dialogo_id'],
                'estado' => 'iniciado',
                'configuracion' => $validated['configuracion'] ?? [],
                'variables' => [],
                'historial_nodos' => [],
            ]);

            // Iniciar el diálogo
            $sesionDialogo->iniciar();
            $sesionDialogo->load(['dialogo', 'nodoActual.rol', 'nodoActual.respuestas']);

            return response()->json([
                'success' => true,
                'data' => $sesionDialogo,
                'message' => 'Diálogo iniciado exitosamente'
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
                'message' => 'Error al iniciar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sesiones/{sesion}/dialogo-actual",
     *     summary="Obtener estado actual del diálogo",
     *     description="Obtiene el estado actual del diálogo en una sesión",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado del diálogo obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerEstadoActual(SesionJuicio $sesion): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->with(['dialogo', 'nodoActual.rol', 'nodoActual.respuestas'])
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            $estado = [
                'sesion_dialogo' => $sesionDialogo,
                'nodo_actual' => $sesionDialogo->nodoActual,
                'progreso' => $sesionDialogo->configuracion['progreso'] ?? null,
                'tiempo_transcurrido' => $sesionDialogo->tiempo_transcurrido,
                'variables' => $sesionDialogo->variables,
            ];

            return response()->json([
                'success' => true,
                'data' => $estado,
                'message' => 'Estado del diálogo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado del diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sesiones/{sesion}/respuestas-disponibles/{usuario}",
     *     summary="Obtener respuestas disponibles",
     *     description="Obtiene las respuestas disponibles para un usuario específico en el nodo actual",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="sesion",
     *         in="path",
     *         description="ID de la sesión",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="usuario",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Respuestas obtenidas exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerRespuestasDisponibles(SesionJuicio $sesion, $usuarioId): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->where('estado', 'en_curso')
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            // Obtener asignación del usuario
            $asignacion = $sesion->obtenerParticipantePorUsuario($usuarioId);
            
            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está asignado a esta sesión'
                ], 404);
            }

            $respuestas = $sesionDialogo->obtenerRespuestasDisponibles($usuarioId, $asignacion->rol_id, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'respuestas' => $respuestas,
                    'nodo_actual' => $sesionDialogo->nodoActual,
                    'rol_usuario' => $asignacion->rol,
                ],
                'message' => 'Respuestas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las respuestas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sesiones/{sesion}/procesar-decision",
     *     summary="Procesar decisión del usuario",
     *     description="Procesa una decisión tomada por un usuario en el diálogo",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"usuario_id", "respuesta_id"},
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="respuesta_id", type="integer", example=1),
     *             @OA\Property(property="decision_texto", type="string", example="Texto adicional de la decisión"),
     *             @OA\Property(property="tiempo_respuesta", type="integer", example=45)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Decisión procesada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function procesarDecision(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|exists:users,id',
                'respuesta_id' => 'required|exists:respuestas_dialogo_v2,id',
                'decision_texto' => 'nullable|string',
                'tiempo_respuesta' => 'nullable|integer|min:0',
            ]);

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->where('estado', 'en_curso')
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            // Obtener asignación del usuario
            $asignacion = $sesion->obtenerParticipantePorUsuario($validated['usuario_id']);
            
            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está asignado a esta sesión'
                ], 404);
            }

            // Procesar la decisión
            $decision = $sesionDialogo->procesarDecision(
                $validated['usuario_id'],
                $asignacion->rol_id,
                $validated['respuesta_id'],
                $validated['decision_texto'] ?? null,
                $validated['tiempo_respuesta'] ?? null
            );

            if (!$decision) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar la decisión'
                ], 400);
            }

            // Recargar el estado actual
            $sesionDialogo->refresh();
            $sesionDialogo->load(['nodoActual.rol', 'nodoActual.respuestas']);

            return response()->json([
                'success' => true,
                'data' => [
                    'decision' => $decision,
                    'estado_actual' => $sesionDialogo,
                    'progreso' => $sesionDialogo->configuracion['progreso'] ?? null,
                ],
                'message' => 'Decisión procesada exitosamente'
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
                'message' => 'Error al procesar la decisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sesiones/{sesion}/avanzar-dialogo",
     *     summary="Avanzar diálogo manualmente",
     *     description="Permite al instructor avanzar manualmente el diálogo (solo admin e instructor)",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nodo_id"},
     *             @OA\Property(property="nodo_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo avanzado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function avanzarDialogo(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Verificar permisos
            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar esta sesión'
                ], 403);
            }

            $validated = $request->validate([
                'nodo_id' => 'required|exists:nodos_dialogo_v2,id',
            ]);

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            $resultado = $sesionDialogo->avanzarANodo($validated['nodo_id']);

            if (!$resultado) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo avanzar al nodo especificado'
                ], 400);
            }

            $sesionDialogo->load(['nodoActual.rol', 'nodoActual.respuestas']);

            return response()->json([
                'success' => true,
                'data' => $sesionDialogo,
                'message' => 'Diálogo avanzado exitosamente'
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
                'message' => 'Error al avanzar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sesiones/{sesion}/pausar-dialogo",
     *     summary="Pausar diálogo",
     *     description="Pausa el diálogo actual (solo admin e instructor)",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo pausado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function pausarDialogo(SesionJuicio $sesion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar esta sesión'
                ], 403);
            }

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->where('estado', 'en_curso')
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            $sesionDialogo->pausar();

            return response()->json([
                'success' => true,
                'data' => $sesionDialogo,
                'message' => 'Diálogo pausado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al pausar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sesiones/{sesion}/finalizar-dialogo",
     *     summary="Finalizar diálogo",
     *     description="Finaliza el diálogo actual (solo admin e instructor)",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Diálogo finalizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function finalizarDialogo(SesionJuicio $sesion): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar esta sesión'
                ], 403);
            }

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo en esta sesión'
                ], 404);
            }

            $sesionDialogo->finalizar();

            // Obtener estadísticas finales
            $estadisticas = $sesionDialogo->obtenerEstadisticasPorRol();
            $flujoCompleto = $sesionDialogo->obtenerFlujoCompleto();

            return response()->json([
                'success' => true,
                'data' => [
                    'sesion_dialogo' => $sesionDialogo,
                    'estadisticas' => $estadisticas,
                    'flujo_completo' => $flujoCompleto,
                ],
                'message' => 'Diálogo finalizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el diálogo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sesiones/{sesion}/historial-decisiones",
     *     summary="Obtener historial de decisiones",
     *     description="Obtiene el historial completo de decisiones tomadas en el diálogo",
     *     tags={"Flujo de Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Historial obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerHistorialDecisiones(SesionJuicio $sesion): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo en esta sesión'
                ], 404);
            }

            $historial = $sesionDialogo->obtenerHistorialDecisiones();
            $estadisticas = $sesionDialogo->obtenerEstadisticasPorRol();

            return response()->json([
                'success' => true,
                'data' => [
                    'historial' => $historial,
                    'estadisticas' => $estadisticas,
                    'progreso' => $sesionDialogo->configuracion['progreso'] ?? null,
                ],
                'message' => 'Historial de decisiones obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
