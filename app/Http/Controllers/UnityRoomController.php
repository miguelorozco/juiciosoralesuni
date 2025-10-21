<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UnityRoom;
use App\Models\UnityRoomEvent;
use App\Models\SesionJuicio;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class UnityRoomController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/unity/rooms/create",
     *     summary="Crear sala de Unity",
     *     description="Crear una nueva sala de Unity para una sesión de juicio",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "sesion_juicio_id"},
     *             @OA\Property(property="nombre", type="string", example="Sala de Juicio Civil"),
     *             @OA\Property(property="sesion_juicio_id", type="integer", example=1),
     *             @OA\Property(property="max_participantes", type="integer", example=10),
     *             @OA\Property(property="configuracion", type="object"),
     *             @OA\Property(property="audio_config", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sala creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function createRoom(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'sesion_juicio_id' => 'required|exists:sesiones_juicios,id',
                'max_participantes' => 'nullable|integer|min:1|max:50',
                'configuracion' => 'nullable|array',
                'audio_config' => 'nullable|array',
            ]);

            $sesion = SesionJuicio::findOrFail($validated['sesion_juicio_id']);
            $user = auth()->user();

            // Verificar permisos
            if (!$sesion->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear una sala para esta sesión'
                ], 403);
            }

            // Verificar si ya existe una sala activa para esta sesión
            $existingRoom = UnityRoom::where('sesion_juicio_id', $sesion->id)
                ->whereIn('estado', ['activa', 'pausada'])
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una sala activa para esta sesión',
                    'data' => [
                        'existing_room_id' => $existingRoom->room_id,
                        'existing_room_name' => $existingRoom->nombre
                    ]
                ], 409);
            }

            // Crear sala
            $room = UnityRoom::crearParaSesion($sesion, $user, $validated['configuracion'] ?? []);

            // Actualizar configuración si se proporciona
            if (isset($validated['max_participantes'])) {
                $room->update(['max_participantes' => $validated['max_participantes']]);
            }

            if (isset($validated['audio_config'])) {
                $room->update(['audio_config' => $validated['audio_config']]);
            }

            // Crear evento de sala creada
            UnityRoomEvent::salaEstado($room->room_id, 'creada', [
                'creado_por' => $user->id,
                'sesion_id' => $sesion->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sala creada exitosamente',
                'data' => [
                    'room_id' => $room->room_id,
                    'nombre' => $room->nombre,
                    'max_participantes' => $room->max_participantes,
                    'estado' => $room->estado,
                    'configuracion' => $room->configuracion,
                    'audio_config' => $room->audio_config,
                    'fecha_creacion' => $room->fecha_creacion->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear sala: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/rooms/{roomId}/join",
     *     summary="Unirse a sala de Unity",
     *     description="Unirse a una sala de Unity existente",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unido a sala exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function joinRoom(string $roomId): JsonResponse
    {
        try {
            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar si la sala está activa
            if (!$room->esta_activa) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sala no está activa'
                ], 400);
            }

            // Verificar si hay espacio
            if (!$room->puede_conectar) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sala está llena'
                ], 400);
            }

            // Conectar participante
            $metadata = [
                'unity_version' => request()->header('X-Unity-Version', 'Unknown'),
                'unity_platform' => request()->header('X-Unity-Platform', 'Unknown'),
                'device_id' => request()->header('X-Unity-Device-Id', 'Unknown'),
                'join_timestamp' => now()->toISOString(),
            ];

            if (!$room->conectarParticipante($user->id, $metadata)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar a la sala'
                ], 400);
            }

            // Crear evento de usuario conectado
            UnityRoomEvent::usuarioConectado($room->room_id, $user->id, $metadata);

            return response()->json([
                'success' => true,
                'message' => 'Unido a sala exitosamente',
                'data' => [
                    'room_id' => $room->room_id,
                    'nombre' => $room->nombre,
                    'participantes_conectados' => $room->participantes_conectados,
                    'max_participantes' => $room->max_participantes,
                    'configuracion' => $room->configuracion,
                    'audio_config' => $room->audio_config,
                    'participantes' => $room->obtenerParticipantesConectados(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al unirse a sala: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/rooms/{roomId}/leave",
     *     summary="Salir de sala de Unity",
     *     description="Salir de una sala de Unity",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Salió de sala exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function leaveRoom(string $roomId): JsonResponse
    {
        try {
            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Desconectar participante
            if ($room->desconectarParticipante($user->id)) {
                // Crear evento de usuario desconectado
                UnityRoomEvent::usuarioDesconectado($room->room_id, $user->id, [
                    'leave_timestamp' => now()->toISOString(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Salió de sala exitosamente',
                    'data' => [
                        'room_id' => $room->room_id,
                        'participantes_conectados' => $room->participantes_conectados,
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo salir de la sala'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al salir de sala: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/rooms/{roomId}/state",
     *     summary="Obtener estado de sala",
     *     description="Obtener el estado actual de una sala de Unity",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estado obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function getRoomState(string $roomId): JsonResponse
    {
        try {
            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar si el usuario está en la sala
            $participante = $room->obtenerParticipante($user->id);
            if (!$participante) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás en esta sala'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'room_id' => $room->room_id,
                    'nombre' => $room->nombre,
                    'estado' => $room->estado,
                    'participantes_conectados' => $room->participantes_conectados,
                    'max_participantes' => $room->max_participantes,
                    'configuracion' => $room->configuracion,
                    'audio_config' => $room->audio_config,
                    'participantes' => $room->obtenerParticipantesConectados(),
                    'ultima_actividad' => $room->ultima_actividad?->toISOString(),
                    'tiempo_actividad' => $room->tiempo_actividad,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/rooms/{roomId}/sync-player",
     *     summary="Sincronizar jugador",
     *     description="Sincronizar posición y estado de un jugador",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"usuario_id", "posicion"},
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="posicion", type="array", @OA\Items(type="number"), example={0, 0, 0}),
     *             @OA\Property(property="rotacion", type="array", @OA\Items(type="number"), example={0, 0, 0}),
     *             @OA\Property(property="audio_enabled", type="boolean", example=true),
     *             @OA\Property(property="microfono_activo", type="boolean", example=false),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador sincronizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function syncPlayer(Request $request, string $roomId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|integer',
                'posicion' => 'required|array|size:3',
                'posicion.*' => 'numeric',
                'rotacion' => 'nullable|array|size:3',
                'rotacion.*' => 'numeric',
                'audio_enabled' => 'nullable|boolean',
                'microfono_activo' => 'nullable|boolean',
                'metadata' => 'nullable|array',
            ]);

            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar si el usuario está en la sala
            if (!$room->obtenerParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás en esta sala'
                ], 403);
            }

            // Actualizar posición del participante
            $room->actualizarPosicionParticipante(
                $validated['usuario_id'],
                $validated['posicion'],
                $validated['rotacion'] ?? []
            );

            // Crear evento de posición actualizada
            UnityRoomEvent::posicionActualizada(
                $room->room_id,
                $validated['usuario_id'],
                $validated['posicion'],
                $validated['rotacion'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Jugador sincronizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar jugador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/rooms/{roomId}/audio-state",
     *     summary="Actualizar estado de audio",
     *     description="Actualizar el estado de audio de un jugador",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"usuario_id", "microfono_activo"},
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="microfono_activo", type="boolean", example=true),
     *             @OA\Property(property="audio_enabled", type="boolean", example=true),
     *             @OA\Property(property="volumen", type="number", example=1.0),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado de audio actualizado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function updateAudioState(Request $request, string $roomId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|integer',
                'microfono_activo' => 'required|boolean',
                'audio_enabled' => 'nullable|boolean',
                'volumen' => 'nullable|numeric|min:0|max:1',
                'metadata' => 'nullable|array',
            ]);

            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar si el usuario está en la sala
            if (!$room->obtenerParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás en esta sala'
                ], 403);
            }

            // Actualizar estado de audio del participante
            $room->actualizarAudioParticipante(
                $validated['usuario_id'],
                $validated['microfono_activo'],
                $validated
            );

            // Crear evento de cambio de audio
            UnityRoomEvent::audioCambio(
                $room->room_id,
                $validated['usuario_id'],
                $validated['microfono_activo'],
                $validated
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado de audio actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado de audio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/rooms/{roomId}/events",
     *     summary="Obtener eventos de sala",
     *     description="Obtener eventos recientes de una sala de Unity",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de eventos",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="since",
     *         in="query",
     *         description="ID del evento desde el cual obtener eventos",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Eventos obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function getRoomEvents(string $roomId, Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 50);
            $since = $request->query('since', 0);

            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar si el usuario está en la sala
            if (!$room->obtenerParticipante($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás en esta sala'
                ], 403);
            }

            $events = UnityRoomEvent::porRoom($roomId)
                ->where('id', '>', $since)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total' => $events->count(),
                    'room_id' => $roomId,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener eventos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/unity/rooms/{roomId}/close",
     *     summary="Cerrar sala",
     *     description="Cerrar una sala de Unity (solo creador o admin)",
     *     tags={"Unity - Salas"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sala cerrada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function closeRoom(string $roomId): JsonResponse
    {
        try {
            $room = UnityRoom::where('room_id', $roomId)->firstOrFail();
            $user = auth()->user();

            // Verificar permisos
            if (!$room->puedeSerGestionadaPor($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cerrar esta sala'
                ], 403);
            }

            // Cerrar sala
            $room->cerrar();

            // Crear evento de sala cerrada
            UnityRoomEvent::salaEstado($room->room_id, 'cerrada', [
                'cerrado_por' => $user->id,
                'timestamp' => now()->toISOString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sala cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sala: ' . $e->getMessage()
            ], 500);
        }
    }
}