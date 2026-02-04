<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\SesionJuicio;

/**
 * Controller for handling LiveKit room connections and token generation
 * Replaces PeerJS for real-time audio/video communication
 */
class LiveKitController extends Controller
{
    /**
     * LiveKit API configuration
     */
    private function getApiKey(): ?string
    {
        return config('livekit.api_key');
    }

    private function getApiSecret(): ?string
    {
        return config('livekit.api_secret');
    }

    private function getServerUrl(): string
    {
        return config('livekit.host', 'ws://localhost:7880');
    }

    private function getHttpUrl(): string
    {
        return config('livekit.http_url', 'http://localhost:7880');
    }

    /**
     * Generate JWT token manually (without SDK dependency)
     */
    private function generateJwtToken(string $identity, string $name, string $roomName, array $grants = []): string
    {
        $apiKey = $this->getApiKey();
        $apiSecret = $this->getApiSecret();

        if (!$apiKey || !$apiSecret) {
            throw new \Exception('LiveKit API credentials not configured');
        }

        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Default grants for room participation
        $videoGrant = array_merge([
            'roomJoin' => true,
            'room' => $roomName,
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
        ], $grants);

        // Payload
        $now = time();
        $payload = [
            'iss' => $apiKey,
            'sub' => $identity,
            'name' => $name,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 86400, // 24 hours
            'video' => $videoGrant,
            'metadata' => json_encode(['name' => $name]),
        ];

        // Encode
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        // Signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $apiSecret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Generate an access token for a LiveKit room
     */
    public function getToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'participant_name' => 'required|string|max:255',
            'participant_identity' => 'nullable|string|max:255',
            'can_publish' => 'nullable|boolean',
            'can_subscribe' => 'nullable|boolean',
        ]);

        $roomName = $validated['room_name'];
        $participantName = $validated['participant_name'];
        $participantIdentity = $validated['participant_identity'] ?? 'user_' . uniqid();

        try {
            $grants = [
                'canPublish' => $validated['can_publish'] ?? true,
                'canSubscribe' => $validated['can_subscribe'] ?? true,
            ];

            $token = $this->generateJwtToken($participantIdentity, $participantName, $roomName, $grants);

            Log::info('LiveKit token generated', [
                'room' => $roomName,
                'participant' => $participantName,
                'identity' => $participantIdentity,
            ]);

            // Track participant in cache for room management
            $this->trackParticipant($roomName, $participantIdentity, $participantName);

            return response()->json([
                'success' => true,
                'token' => $token,
                'url' => $this->getServerUrl(),
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'coturn' => $this->getCoturnConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating LiveKit token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate token',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get token for a specific session (Unity integration)
     */
    public function getSessionToken(Request $request, SesionJuicio $sesion): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
            ], 401);
        }

        try {
            $roomName = 'juicio-session-' . $sesion->id;
            $participantName = $user->name;
            $participantIdentity = 'user_' . $user->id;

            // Get user's role in the session
            $asignacion = $sesion->asignaciones()
                ->where('usuario_id', $user->id)
                ->first();

            $grants = [
                'canPublish' => true,
                'canSubscribe' => true,
            ];

            $token = $this->generateJwtToken($participantIdentity, $participantName, $roomName, $grants);

            Log::info('LiveKit session token generated', [
                'session_id' => $sesion->id,
                'user_id' => $user->id,
                'room' => $roomName,
            ]);

            $this->trackParticipant($roomName, $participantIdentity, $participantName);

            return response()->json([
                'success' => true,
                'token' => $token,
                'url' => $this->getServerUrl(),
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'session_id' => $sesion->id,
                'user_role' => $asignacion?->rolDisponible?->nombre ?? 'observador',
                'coturn' => $this->getCoturnConfig(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating session token', [
                'error' => $e->getMessage(),
                'session_id' => $sesion->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate session token',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get COTURN (TURN/STUN) configuration
     */
    private function getCoturnConfig(): array
    {
        $host = config('livekit.coturn.host', 'localhost');
        $port = config('livekit.coturn.port', 3478);

        return [
            'urls' => [
                "stun:{$host}:{$port}",
                "turn:{$host}:{$port}",
            ],
            'username' => config('livekit.coturn.username'),
            'credential' => config('livekit.coturn.password'),
        ];
    }

    /**
     * Track participant in cache for room management
     */
    private function trackParticipant(string $roomName, string $identity, string $name): void
    {
        $cacheKey = "livekit_room_{$roomName}_participants";
        $participants = Cache::get($cacheKey, []);

        $participants[$identity] = [
            'identity' => $identity,
            'name' => $name,
            'joined_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $participants, 3600); // 1 hour TTL
    }

    /**
     * Remove participant from tracking
     */
    private function untrackParticipant(string $roomName, string $identity): void
    {
        $cacheKey = "livekit_room_{$roomName}_participants";
        $participants = Cache::get($cacheKey, []);

        unset($participants[$identity]);

        if (empty($participants)) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $participants, 3600);
        }
    }

    /**
     * Get available rooms (from cache tracking)
     */
    public function getRooms(Request $request): JsonResponse
    {
        try {
            // Get all room keys from cache
            $rooms = [];
            
            // In a production environment, you would query LiveKit's Room Service API
            // For now, we track rooms in cache when tokens are generated
            
            // Get active sessions that have room IDs
            $activeSessions = SesionJuicio::where('estado', 'activa')
                ->orWhere('estado', 'en_progreso')
                ->get();

            foreach ($activeSessions as $session) {
                $roomName = 'juicio-session-' . $session->id;
                $cacheKey = "livekit_room_{$roomName}_participants";
                $participants = Cache::get($cacheKey, []);

                $rooms[] = [
                    'name' => $roomName,
                    'session_id' => $session->id,
                    'session_name' => $session->nombre,
                    'num_participants' => count($participants),
                    'participants' => array_values($participants),
                    'created_at' => $session->created_at->toISOString(),
                ];
            }

            return response()->json([
                'success' => true,
                'rooms' => $rooms,
                'total' => count($rooms),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting rooms', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get rooms',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get participants in a room
     */
    public function getParticipants(Request $request, string $roomName): JsonResponse
    {
        try {
            $cacheKey = "livekit_room_{$roomName}_participants";
            $participants = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'room' => $roomName,
                'participants' => array_values($participants),
                'count' => count($participants),
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting participants', [
                'error' => $e->getMessage(),
                'room' => $roomName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get participants',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notify when a participant leaves (called from client)
     */
    public function participantLeft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_name' => 'required|string',
            'identity' => 'required|string',
        ]);

        $this->untrackParticipant($validated['room_name'], $validated['identity']);

        Log::info('Participant left room', [
            'room' => $validated['room_name'],
            'identity' => $validated['identity'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant removed from tracking',
        ]);
    }

    /**
     * Create a room for a session
     */
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sesion_juicios,id',
            'max_participants' => 'nullable|integer|min:2|max:50',
        ]);

        try {
            $session = SesionJuicio::findOrFail($validated['session_id']);
            $roomName = 'juicio-session-' . $session->id;

            // Initialize room tracking in cache
            $cacheKey = "livekit_room_{$roomName}_participants";
            Cache::put($cacheKey, [], 3600);

            // Store room metadata
            $metadataKey = "livekit_room_{$roomName}_metadata";
            Cache::put($metadataKey, [
                'session_id' => $session->id,
                'created_at' => now()->toISOString(),
                'max_participants' => $validated['max_participants'] ?? 50,
            ], 3600);

            Log::info('LiveKit room created', [
                'room' => $roomName,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'room_name' => $roomName,
                'session_id' => $session->id,
                'server_url' => $this->getServerUrl(),
                'message' => 'Room created successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating room', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create room',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close/delete a room
     */
    public function closeRoom(Request $request, string $roomName): JsonResponse
    {
        try {
            // Clear room data from cache
            Cache::forget("livekit_room_{$roomName}_participants");
            Cache::forget("livekit_room_{$roomName}_metadata");

            Log::info('LiveKit room closed', ['room' => $roomName]);

            return response()->json([
                'success' => true,
                'message' => 'Room closed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error closing room', [
                'error' => $e->getMessage(),
                'room' => $roomName,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to close room',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check for LiveKit service
     */
    public function healthCheck(): JsonResponse
    {
        $status = [
            'livekit' => [
                'configured' => !empty($this->getApiKey()) && !empty($this->getApiSecret()),
                'server_url' => $this->getServerUrl(),
                'http_url' => $this->getHttpUrl(),
            ],
            'coturn' => [
                'host' => config('livekit.coturn.host'),
                'port' => config('livekit.coturn.port'),
                'configured' => !empty(config('livekit.coturn.username')),
            ],
        ];

        $healthy = $status['livekit']['configured'];

        return response()->json([
            'success' => true,
            'healthy' => $healthy,
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ], $healthy ? 200 : 503);
    }
}
