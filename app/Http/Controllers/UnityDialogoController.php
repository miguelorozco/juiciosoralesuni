<?php

namespace App\Http\Controllers;

use App\Models\SesionDialogo;
use App\Models\SesionJuicio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;

class UnityDialogoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/unity/sesion/{sesion}/dialogo-estado",
     *     summary="Estado del diálogo para Unity",
     *     description="Obtiene el estado actual del diálogo optimizado para Unity",
     *     tags={"Unity - Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerEstadoDialogo(SesionJuicio $sesion): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->with(['nodoActual.rol'])
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo',
                    'data' => [
                        'dialogo_activo' => false,
                        'estado' => 'sin_dialogo'
                    ]
                ]);
            }

            // Obtener participantes activos
            $participantes = $sesion->asignaciones()
                ->with(['usuario', 'rol'])
                ->where('confirmado', true)
                ->get();

            $estadoUnity = [
                'dialogo_activo' => true,
                'estado' => $sesionDialogo->estado,
                'nodo_actual' => [
                    'id' => $sesionDialogo->nodoActual->id,
                    'titulo' => $sesionDialogo->nodoActual->titulo,
                    'contenido' => $sesionDialogo->nodoActual->contenido,
                    'rol_hablando' => [
                        'id' => $sesionDialogo->nodoActual->rol->id,
                        'nombre' => $sesionDialogo->nodoActual->rol->nombre,
                        'color' => $sesionDialogo->nodoActual->rol->color,
                        'icono' => $sesionDialogo->nodoActual->rol->icono,
                    ],
                    'tipo' => $sesionDialogo->nodoActual->tipo,
                    'es_final' => $sesionDialogo->nodoActual->es_final,
                ],
                'participantes' => $participantes->map(function($asignacion) {
                    return [
                        'usuario_id' => $asignacion->usuario_id,
                        'nombre' => $asignacion->usuario->name . ' ' . $asignacion->usuario->apellido,
                        'rol' => [
                            'id' => $asignacion->rol->id,
                            'nombre' => $asignacion->rol->nombre,
                            'color' => $asignacion->rol->color,
                            'icono' => $asignacion->rol->icono,
                        ],
                        'es_turno' => $asignacion->rol_id === $sesionDialogo->nodoActual->rol_id,
                    ];
                }),
                'progreso' => $sesionDialogo->progreso,
                'tiempo_transcurrido' => $sesionDialogo->tiempo_transcurrido,
                'variables' => $sesionDialogo->variables,
            ];

            return response()->json([
                'success' => true,
                'data' => $estadoUnity,
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
     *     path="/api/unity/sesion/{sesion}/respuestas-usuario/{usuario}",
     *     summary="Respuestas disponibles para usuario en Unity",
     *     description="Obtiene las respuestas disponibles para un usuario específico",
     *     tags={"Unity - Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Respuestas obtenidas exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerRespuestasUsuario(SesionJuicio $sesion, $usuarioId): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->where('estado', 'en_curso')
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo',
                    'data' => [
                        'respuestas_disponibles' => false,
                        'mensaje' => 'No hay diálogo activo'
                    ]
                ]);
            }

            // Obtener asignación del usuario
            $asignacion = $sesion->obtenerParticipantePorUsuario($usuarioId);
            
            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no asignado a esta sesión',
                    'data' => [
                        'respuestas_disponibles' => false,
                        'mensaje' => 'Usuario no asignado'
                    ]
                ]);
            }

            // Verificar si es el turno del usuario
            $esSuTurno = $sesionDialogo->nodoActual->rol_id === $asignacion->rol_id;
            
            if (!$esSuTurno) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'respuestas_disponibles' => false,
                        'mensaje' => 'No es tu turno',
                        'rol_actual' => $sesionDialogo->nodoActual->rol->nombre,
                        'tu_rol' => $asignacion->rol->nombre,
                    ],
                    'message' => 'No es el turno del usuario'
                ]);
            }

            $respuestas = $sesionDialogo->obtenerRespuestasDisponibles($usuarioId, $asignacion->rol_id);

            $respuestasUnity = $respuestas->map(function($respuesta) {
                return [
                    'id' => $respuesta->id,
                    'texto' => $respuesta->texto,
                    'descripcion' => $respuesta->descripcion,
                    'color' => $respuesta->color,
                    'puntuacion' => $respuesta->puntuacion,
                    'tiene_consecuencias' => $respuesta->tiene_consecuencias,
                    'es_final' => $respuesta->es_respuesta_final,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'respuestas_disponibles' => true,
                    'respuestas' => $respuestasUnity,
                    'nodo_actual' => [
                        'id' => $sesionDialogo->nodoActual->id,
                        'titulo' => $sesionDialogo->nodoActual->titulo,
                        'contenido' => $sesionDialogo->nodoActual->contenido,
                        'instrucciones' => $sesionDialogo->nodoActual->instrucciones,
                    ],
                    'rol_usuario' => [
                        'id' => $asignacion->rol->id,
                        'nombre' => $asignacion->rol->nombre,
                        'color' => $asignacion->rol->color,
                    ],
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
     *     path="/api/unity/sesion/{sesion}/enviar-decision",
     *     summary="Enviar decisión desde Unity",
     *     description="Procesa una decisión enviada desde Unity",
     *     tags={"Unity - Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"usuario_id", "respuesta_id"},
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="respuesta_id", type="integer", example=1),
     *             @OA\Property(property="decision_texto", type="string", example="Texto adicional"),
     *             @OA\Property(property="tiempo_respuesta", type="integer", example=45),
     *             @OA\Property(property="metadata", type="object", description="Datos adicionales de Unity")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Decisión procesada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function enviarDecision(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|exists:users,id',
                'respuesta_id' => 'required|exists:respuestas_dialogo,id',
                'decision_texto' => 'nullable|string',
                'tiempo_respuesta' => 'nullable|integer|min:0',
                'metadata' => 'nullable|array',
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

            // Agregar metadata de Unity si existe
            if (isset($validated['metadata'])) {
                $decision->update(['metadata' => $validated['metadata']]);
            }

            // Recargar el estado actual
            $sesionDialogo->refresh();
            $sesionDialogo->load(['nodoActual.rol']);

            // Preparar respuesta para Unity
            $respuestaUnity = [
                'decision_procesada' => true,
                'decision_id' => $decision->id,
                'puntuacion_obtenida' => $decision->puntuacion_obtenida,
                'nuevo_estado' => [
                    'nodo_actual' => $sesionDialogo->nodoActual ? [
                        'id' => $sesionDialogo->nodoActual->id,
                        'titulo' => $sesionDialogo->nodoActual->titulo,
                        'contenido' => $sesionDialogo->nodoActual->contenido,
                        'rol_hablando' => [
                            'id' => $sesionDialogo->nodoActual->rol->id,
                            'nombre' => $sesionDialogo->nodoActual->rol->nombre,
                            'color' => $sesionDialogo->nodoActual->rol->color,
                        ],
                        'es_final' => $sesionDialogo->nodoActual->es_final,
                    ] : null,
                    'progreso' => $sesionDialogo->progreso,
                    'dialogo_finalizado' => $sesionDialogo->estado === 'finalizado',
                ],
            ];

            // Broadcast del cambio de estado para otros clientes Unity
            $this->broadcastEstadoDialogo($sesion, $respuestaUnity);

            return response()->json([
                'success' => true,
                'data' => $respuestaUnity,
                'message' => 'Decisión procesada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la decisión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/sesion/{sesion}/notificar-hablando",
     *     summary="Notificar que usuario está hablando",
     *     description="Unity notifica que un usuario específico está hablando",
     *     tags={"Unity - Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"usuario_id"},
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="estado", type="string", enum={"hablando", "terminado"}, example="hablando"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado de habla actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function notificarHablando(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|exists:users,id',
                'estado' => 'required|in:hablando,terminado',
                'metadata' => 'nullable|array',
            ]);

            // Obtener información del usuario
            $asignacion = $sesion->obtenerParticipantePorUsuario($validated['usuario_id']);
            
            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no está asignado a esta sesión'
                ], 404);
            }

            $evento = [
                'tipo' => 'usuario_hablando',
                'usuario_id' => $validated['usuario_id'],
                'usuario_nombre' => $asignacion->usuario->name . ' ' . $asignacion->usuario->apellido,
                'rol' => [
                    'id' => $asignacion->rol->id,
                    'nombre' => $asignacion->rol->nombre,
                    'color' => $asignacion->rol->color,
                ],
                'estado' => $validated['estado'],
                'timestamp' => now()->toISOString(),
                'metadata' => $validated['metadata'] ?? [],
            ];

            // Broadcast del evento
            $this->broadcastEventoUnity($sesion, $evento);

            return response()->json([
                'success' => true,
                'data' => $evento,
                'message' => 'Estado de habla actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de habla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/sesion/{sesion}/movimientos-personajes",
     *     summary="Obtener movimientos de personajes",
     *     description="Obtiene datos de animaciones y movimientos para Unity",
     *     tags={"Unity - Diálogos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Movimientos obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function obtenerMovimientosPersonajes(SesionJuicio $sesion): JsonResponse
    {
        try {
            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->with(['nodoActual.rol'])
                ->first();

            if (!$sesionDialogo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un diálogo activo',
                    'data' => ['movimientos' => []]
                ]);
            }

            // Obtener participantes
            $participantes = $sesion->asignaciones()
                ->with(['usuario', 'rol'])
                ->where('confirmado', true)
                ->get();

            $movimientos = $participantes->map(function($asignacion) use ($sesionDialogo) {
                $esTurnoActivo = $asignacion->rol_id === $sesionDialogo->nodoActual->rol_id;
                
                return [
                    'usuario_id' => $asignacion->usuario_id,
                    'rol_id' => $asignacion->rol_id,
                    'posicion' => [
                        'x' => rand(-5, 5), // Posiciones aleatorias por ahora
                        'y' => 0,
                        'z' => rand(-5, 5),
                    ],
                    'animacion' => $esTurnoActivo ? 'hablando' : 'esperando',
                    'estado' => $esTurnoActivo ? 'activo' : 'inactivo',
                    'color' => $asignacion->rol->color,
                    'metadata' => [
                        'es_turno' => $esTurnoActivo,
                        'nodo_actual' => $sesionDialogo->nodoActual->id,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'movimientos' => $movimientos,
                    'nodo_actual' => $sesionDialogo->nodoActual->id,
                    'estado_dialogo' => $sesionDialogo->estado,
                ],
                'message' => 'Movimientos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Broadcast del estado del diálogo a otros clientes Unity
     */
    private function broadcastEstadoDialogo(SesionJuicio $sesion, array $estado)
    {
        $evento = [
            'tipo' => 'estado_dialogo_actualizado',
            'sesion_id' => $sesion->id,
            'estado' => $estado,
            'timestamp' => now()->toISOString(),
        ];

        $this->broadcastEventoUnity($sesion, $evento);
    }

    /**
     * Broadcast de eventos Unity
     */
    private function broadcastEventoUnity(SesionJuicio $sesion, array $evento)
    {
        // Aquí se implementaría el broadcast real usando Laravel Broadcasting
        // Por ejemplo, con Pusher, Redis, o WebSockets
        
        // Ejemplo con Pusher:
        // broadcast(new UnityEvent($sesion->unity_room_id, $evento));
        
        // Por ahora, solo log del evento
        \Log::info('Unity Event', [
            'sesion_id' => $sesion->id,
            'room_id' => $sesion->unity_room_id,
            'evento' => $evento
        ]);
    }
}
