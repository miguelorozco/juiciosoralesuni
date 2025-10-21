<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\StreamedResponse;
use App\Models\SesionJuicio;
use App\Models\SesionDialogo;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UnityRealtimeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/unity/sesion/{sesion}/events",
     *     summary="Server-Sent Events para Unity",
     *     description="Stream de eventos en tiempo real para Unity usando Server-Sent Events",
     *     tags={"Unity - Tiempo Real"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Stream de eventos iniciado",
     *         @OA\MediaType(
     *             mediaType="text/event-stream",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function streamEvents(Request $request, SesionJuicio $sesion): StreamedResponse
    {
        return response()->stream(function () use ($request, $sesion) {
            // Configurar headers para SSE
            echo "data: " . json_encode([
                'type' => 'connection_established',
                'message' => 'Conexión SSE establecida',
                'timestamp' => now()->toISOString(),
                'sesion_id' => $sesion->id,
            ]) . "\n\n";

            // Flush para enviar inmediatamente
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            $lastEventId = $request->header('Last-Event-ID', 0);
            $eventCount = 0;
            $maxEvents = 1000; // Límite de eventos por conexión
            $timeout = 300; // 5 minutos de timeout
            $startTime = time();

            while (true) {
                // Verificar timeout
                if (time() - $startTime > $timeout) {
                    echo "data: " . json_encode([
                        'type' => 'timeout',
                        'message' => 'Conexión cerrada por timeout',
                        'timestamp' => now()->toISOString(),
                    ]) . "\n\n";
                    break;
                }

                // Verificar límite de eventos
                if ($eventCount >= $maxEvents) {
                    echo "data: " . json_encode([
                        'type' => 'max_events_reached',
                        'message' => 'Límite de eventos alcanzado',
                        'timestamp' => now()->toISOString(),
                    ]) . "\n\n";
                    break;
                }

                // Obtener nuevos eventos
                $events = $this->getNewEvents($sesion->id, $lastEventId);
                
                foreach ($events as $event) {
                    echo "id: " . $event['id'] . "\n";
                    echo "event: " . $event['type'] . "\n";
                    echo "data: " . json_encode($event['data']) . "\n\n";
                    
                    $lastEventId = $event['id'];
                    $eventCount++;
                }

                // Flush para enviar eventos
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Esperar antes de la siguiente verificación
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Para Nginx
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/unity/sesion/{sesion}/broadcast",
     *     summary="Broadcast de evento a Unity",
     *     description="Enviar un evento a todos los clientes Unity conectados",
     *     tags={"Unity - Tiempo Real"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "data"},
     *             @OA\Property(property="type", type="string", example="dialogo_actualizado"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="target_users", type="array", @OA\Items(type="integer"), description="IDs de usuarios específicos (opcional)"),
     *             @OA\Property(property="priority", type="string", enum={"low", "normal", "high"}, example="normal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento broadcast exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function broadcastEvent(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|max:100',
                'data' => 'required|array',
                'target_users' => 'nullable|array',
                'target_users.*' => 'integer|exists:users,id',
                'priority' => 'nullable|in:low,normal,high',
            ]);

            $event = [
                'id' => $this->generateEventId(),
                'type' => $validated['type'],
                'data' => $validated['data'],
                'sesion_id' => $sesion->id,
                'timestamp' => now()->toISOString(),
                'priority' => $validated['priority'] ?? 'normal',
                'target_users' => $validated['target_users'] ?? null,
            ];

            // Almacenar evento en cache
            $this->storeEvent($sesion->id, $event);

            // Log del evento
            \Log::info('Unity Event Broadcast', $event);

            return response()->json([
                'success' => true,
                'message' => 'Evento broadcast exitosamente',
                'data' => [
                    'event_id' => $event['id'],
                    'type' => $event['type'],
                    'timestamp' => $event['timestamp'],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al hacer broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/unity/sesion/{sesion}/events/history",
     *     summary="Historial de eventos",
     *     description="Obtener historial de eventos de la sesión",
     *     tags={"Unity - Tiempo Real"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de eventos a retornar",
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
     *         description="Historial obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    public function getEventHistory(Request $request, SesionJuicio $sesion): JsonResponse
    {
        try {
            $limit = $request->query('limit', 50);
            $since = $request->query('since', 0);

            $events = $this->getEventsFromCache($sesion->id, $since, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'events' => $events,
                    'total' => count($events),
                    'sesion_id' => $sesion->id,
                ],
                'message' => 'Historial obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener nuevos eventos desde el último ID
     */
    private function getNewEvents(int $sesionId, int $lastEventId): array
    {
        $events = $this->getEventsFromCache($sesionId, $lastEventId, 10);
        
        // Filtrar solo eventos nuevos
        return array_filter($events, function($event) use ($lastEventId) {
            return $event['id'] > $lastEventId;
        });
    }

    /**
     * Obtener eventos desde cache
     */
    private function getEventsFromCache(int $sesionId, int $since = 0, int $limit = 50): array
    {
        $cacheKey = "unity_events_sesion_{$sesionId}";
        $events = Cache::get($cacheKey, []);
        
        // Filtrar eventos desde el ID especificado
        $filteredEvents = array_filter($events, function($event) use ($since) {
            return $event['id'] > $since;
        });
        
        // Ordenar por ID y limitar
        usort($filteredEvents, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        return array_slice($filteredEvents, 0, $limit);
    }

    /**
     * Almacenar evento en cache
     */
    private function storeEvent(int $sesionId, array $event): void
    {
        $cacheKey = "unity_events_sesion_{$sesionId}";
        $events = Cache::get($cacheKey, []);
        
        $events[] = $event;
        
        // Mantener solo los últimos 1000 eventos
        if (count($events) > 1000) {
            $events = array_slice($events, -1000);
        }
        
        Cache::put($cacheKey, $events, 3600); // 1 hora de TTL
    }

    /**
     * Generar ID único para evento
     */
    private function generateEventId(): int
    {
        return (int) (microtime(true) * 1000000);
    }

    /**
     * Eventos predefinidos para Unity
     */
    public function triggerDialogoEvent(SesionJuicio $sesion, string $eventType, array $data = []): void
    {
        $event = [
            'id' => $this->generateEventId(),
            'type' => $eventType,
            'data' => array_merge($data, [
                'sesion_id' => $sesion->id,
                'timestamp' => now()->toISOString(),
            ]),
            'sesion_id' => $sesion->id,
            'timestamp' => now()->toISOString(),
            'priority' => 'normal',
        ];

        $this->storeEvent($sesion->id, $event);
    }

    /**
     * Eventos específicos para Unity
     */
    public function triggerDialogoActualizado(SesionJuicio $sesion, array $nodoData): void
    {
        $this->triggerDialogoEvent($sesion, 'dialogo_actualizado', [
            'nodo_actual' => $nodoData,
            'tipo' => 'dialogo_actualizado'
        ]);
    }

    public function triggerUsuarioHablando(SesionJuicio $sesion, int $usuarioId, string $estado): void
    {
        $this->triggerDialogoEvent($sesion, 'usuario_hablando', [
            'usuario_id' => $usuarioId,
            'estado' => $estado,
            'tipo' => 'usuario_hablando'
        ]);
    }

    public function triggerDecisionProcesada(SesionJuicio $sesion, int $usuarioId, array $decisionData): void
    {
        $this->triggerDialogoEvent($sesion, 'decision_procesada', [
            'usuario_id' => $usuarioId,
            'decision' => $decisionData,
            'tipo' => 'decision_procesada'
        ]);
    }

    public function triggerSesionFinalizada(SesionJuicio $sesion): void
    {
        $this->triggerDialogoEvent($sesion, 'sesion_finalizada', [
            'tipo' => 'sesion_finalizada',
            'fecha_fin' => now()->toISOString(),
        ]);
    }
}