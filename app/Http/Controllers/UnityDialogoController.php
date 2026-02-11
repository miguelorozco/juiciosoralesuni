<?php

namespace App\Http\Controllers;

/**
 * @deprecated Este controlador usa modelos antiguos (SesionDialogo v1).
 * Se mantiene temporalmente para compatibilidad con Unity.
 * TODO: Refactorizar para usar SesionDialogoV2 después de migración completa.
 */

use App\Models\SesionDialogoV2 as SesionDialogo;
use App\Models\SesionJuicio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UnityDialogoController extends Controller
{
    /**
     * Iniciar diálogo en sesión (pasar estado de iniciado a en_curso).
     * POST /api/unity/sesion/{sesionJuicio}/iniciar-dialogo
     * Requiere unity.auth. Solo instructor o admin de la sesión.
     */
    public function iniciarDialogo(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        $sesion = $sesionJuicio;
        try {
            // Usuario inyectado por UnityAuthMiddleware (token JWT o unity_entry)
            $user = $request->get('unity_user');
            if (!$user) {
                $user = JWTAuth::parseToken()->authenticate();
            }

            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para iniciar el diálogo en esta sesión',
                ], 403);
            }

            Log::info('Unity iniciar-dialogo: sesión consultada', [
                'sesion_id' => $sesion->id,
                'sesion_nombre' => $sesion->nombre,
            ]);

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->where('estado', 'iniciado')
                ->with(['dialogo', 'nodoActual.rol'])
                ->first();

            if (!$sesionDialogo) {
                $activo = SesionDialogo::where('sesion_id', $sesion->id)
                    ->whereIn('estado', ['en_curso', 'pausado'])
                    ->first();
                if ($activo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya hay un diálogo en curso en esta sesión',
                    ], 400);
                }

                // Sin diálogo en 'iniciado': buscar uno finalizado para reiniciar, o ninguno
                $cualquiera = SesionDialogo::where('sesion_id', $sesion->id)
                    ->with(['dialogo'])
                    ->first();

                if ($cualquiera && $cualquiera->estado === 'finalizado') {
                    // Reiniciar: dejar en 'iniciado' con nodo inicial para que iniciar() funcione
                    $nodoInicial = $cualquiera->dialogo->nodo_inicial
                        ?? $cualquiera->dialogo->nodos()->orderBy('orden')->first();
                    if ($nodoInicial) {
                        $cualquiera->update([
                            'estado' => 'iniciado',
                            'nodo_actual_id' => $nodoInicial->id,
                            'fecha_inicio' => null,
                            'fecha_fin' => null,
                            'historial_nodos' => [],
                            'variables' => [],
                            'configuracion' => $cualquiera->configuracion ?? ['modo_automatico' => true, 'tiempo_respuesta' => 30, 'permite_pausa' => true],
                        ]);
                        $sesionDialogo = $cualquiera->fresh(['dialogo', 'nodoActual.rol']);
                        Log::info('Unity iniciar-dialogo: diálogo reiniciado para sesión', ['sesion_id' => $sesion->id, 'sesion_dialogo_id' => $cualquiera->id]);
                    } else {
                        Log::warning('Unity iniciar-dialogo: diálogo sin nodo inicial', ['sesion_id' => $sesion->id, 'dialogo_id' => $cualquiera->dialogo_id]);
                        return response()->json([
                            'success' => false,
                            'message' => 'El diálogo no tiene nodo inicial. Revisa la configuración del diálogo en la web.',
                        ], 400);
                    }
                } else {
                    // No hay ningún registro de diálogo para esta sesión
                    $editUrl = url('/sesiones/' . $sesion->id . '/edit');
                    Log::warning('Unity iniciar-dialogo: no hay SesionDialogo para sesión', ['sesion_id' => $sesion->id, 'edit_url' => $editUrl]);
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay un diálogo configurado para esta sesión. En la web ve a Sesiones → Editar esta sesión, elige un "Diálogo a utilizar" y guarda.',
                        'edit_url' => $editUrl,
                    ], 400);
                }
            }

            if (!$sesionDialogo->iniciar()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo iniciar el diálogo (por ejemplo, sin nodo inicial)',
                ], 400);
            }

            $sesionDialogo->load(['nodoActual.rol']);

            return response()->json([
                'success' => true,
                'data' => [
                    'estado' => $sesionDialogo->estado,
                    'nodo_actual' => $sesionDialogo->nodoActual ? [
                        'id' => $sesionDialogo->nodoActual->id,
                        'titulo' => $sesionDialogo->nodoActual->titulo,
                        'contenido' => $sesionDialogo->nodoActual->contenido,
                        'rol_hablando' => $sesionDialogo->nodoActual->rol ? [
                            'id' => $sesionDialogo->nodoActual->rol->id,
                            'nombre' => $sesionDialogo->nodoActual->rol->nombre,
                            'color' => $sesionDialogo->nodoActual->rol->color,
                            'icono' => $sesionDialogo->nodoActual->rol->icono,
                        ] : null,
                        'es_final' => $sesionDialogo->nodoActual->es_final,
                    ] : null,
                ],
                'message' => 'Diálogo iniciado correctamente',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el diálogo: ' . $e->getMessage(),
            ], 500);
        }
    }

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
    public function obtenerEstadoDialogo(SesionJuicio $sesionJuicio): JsonResponse
    {
        $sesion = $sesionJuicio;
        try {
            Log::info('Unity dialogo-estado: sesión consultada', [
                'sesion_id' => $sesion->id,
                'sesion_nombre' => $sesion->nombre,
            ]);

            $sesionDialogo = SesionDialogo::where('sesion_id', $sesion->id)
                ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                ->with(['nodoActual.rol', 'dialogo'])
                ->first();

            // Fallback: si Eloquent no encuentra pero la BD sí tiene fila (p. ej. otra conexión), usar consulta directa
            if (!$sesionDialogo) {
                $row = \DB::table('sesiones_dialogos_v2')
                    ->where('sesion_id', $sesion->id)
                    ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                    ->first();
                if ($row) {
                    Log::warning('Unity dialogo-estado: Eloquent no encontró fila pero DB sí; recargando por id', ['row_id' => $row->id]);
                    $sesionDialogo = SesionDialogo::with(['nodoActual.rol', 'dialogo'])->find($row->id);
                }
            }

            if (!$sesionDialogo) {
                // Diagnóstico: contar filas en BD para esta sesión
                $countActivos = \DB::table('sesiones_dialogos_v2')
                    ->where('sesion_id', $sesion->id)
                    ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
                    ->count();
                $cualquieraRow = \DB::table('sesiones_dialogos_v2')->where('sesion_id', $sesion->id)->first();
                Log::warning('Unity dialogo-estado: no hay SesionDialogo activo', [
                    'sesion_id' => $sesion->id,
                    'db_count_activos' => $countActivos,
                    'db_any_row' => $cualquieraRow ? ['id' => $cualquieraRow->id, 'estado' => $cualquieraRow->estado, 'dialogo_id' => $cualquieraRow->dialogo_id] : null,
                ]);
                // Info de debug: diálogo configurado para esta sesión (si existe)
                $cualquiera = SesionDialogo::where('sesion_id', $sesion->id)->with('dialogo')->first();
                $tieneDialogoAsignado = $cualquiera && $cualquiera->dialogo_id;
                $data = [
                    'dialogo_activo' => false,
                    'estado' => 'sin_dialogo',
                    'dialogo_configurado_nombre' => $cualquiera?->dialogo?->nombre,
                    'dialogo_configurado_id' => $cualquiera?->dialogo_id ?? 0,
                    'edit_url' => url('/sesiones/' . $sesion->id . '/edit'),
                ];
                $message = $tieneDialogoAsignado
                    ? 'No hay un diálogo activo (configura uno en la web y pulsa "Iniciar diálogo" en Unity).'
                    : 'Esta sesión no tiene un diálogo asignado. Ve a la web → Sesiones → Editar esta sesión → elige "Diálogo a utilizar" y guarda.';
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => $data,
                ], 400);
            }

            // Obtener participantes activos
            $participantes = $sesion->asignaciones()
                ->with(['usuario', 'rol'])
                ->where('confirmado', true)
                ->get();

            $estadoUnity = [
                'dialogo_activo' => true,
                'estado' => $sesionDialogo->estado,
                'dialogo_nombre' => $sesionDialogo->dialogo?->nombre,
                'dialogo_id' => $sesionDialogo->dialogo_id ?? 0,
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
                'participantes' => $participantes->map(function ($asignacion) use ($sesionDialogo) {
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
                'progreso' => $this->normalizarProgresoParaUnity($sesionDialogo->configuracion['progreso'] ?? null),
                'tiempo_transcurrido' => $sesionDialogo->tiempo_transcurrido ?? 0,
                'variables' => $this->normalizarVariablesParaUnity($sesionDialogo->variables ?? []),
                'audio_habilitado' => $sesionDialogo->audio_habilitado,
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
    public function obtenerRespuestasUsuario(SesionJuicio $sesionJuicio, $usuarioId): JsonResponse
    {
        $sesion = $sesionJuicio;
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
    public function enviarDecision(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        $sesion = $sesionJuicio;
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
                    'progreso' => $this->normalizarProgresoParaUnity($sesionDialogo->configuracion['progreso'] ?? null),
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
    public function notificarHablando(Request $request, SesionJuicio $sesionJuicio): JsonResponse
    {
        $sesion = $sesionJuicio;
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
    public function obtenerMovimientosPersonajes(SesionJuicio $sesionJuicio): JsonResponse
    {
        $sesion = $sesionJuicio;
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
     * Normaliza progreso para Unity: siempre devuelve un float 0..1 (nunca null).
     * Unity deserializa a float y falla si recibe null.
     */
    private function normalizarProgresoParaUnity($progreso): float
    {
        if ($progreso === null) {
            return 0.0;
        }
        if (is_array($progreso) && isset($progreso['porcentaje'])) {
            $p = (float) $progreso['porcentaje'];
            return min(1.0, max(0.0, $p / 100.0));
        }
        if (is_numeric($progreso)) {
            $v = (float) $progreso;
            return $v > 1.0 ? min(1.0, max(0.0, $v / 100.0)) : min(1.0, max(0.0, $v));
        }
        return 0.0;
    }

    /**
     * Normaliza variables para Unity: siempre devuelve un valor que se serializa como JSON object ({}).
     * Unity espera Dictionary<string, object> y falla si recibe un array JSON ([]).
     */
    private function normalizarVariablesParaUnity($variables)
    {
        if ($variables === null || $variables === []) {
            return (object) [];
        }
        $arr = is_array($variables) ? $variables : [];
        // Si es lista (índices numéricos 0,1,2...), Unity no puede mapearlo a Dictionary; devolver objeto vacío
        if (array_is_list($arr)) {
            return (object) [];
        }
        return $arr;
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
        Log::info('Unity Event', [
            'sesion_id' => $sesion->id,
            'room_id' => $sesion->unity_room_id,
            'evento' => $evento
        ]);
    }
}
