<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Agones\LiveKit\AccessToken;
use Agones\LiveKit\AccessTokenOptions;
use Agones\LiveKit\VideoGrant;

/**
 * Controller for handling LiveKit room connections and token generation
 */
class LiveKitController extends Controller
{
    /**
     * Generate an access token for a LiveKit room
     */
    public function getToken(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'participant_name' => 'required|string|max:255',
            'participant_identity' => 'nullable|string|max:255',
        ]);

        $roomName = $validated['room_name'];
        $participantName = $validated['participant_name'];
        $participantIdentity = $validated['participant_identity'] ?? uniqid('participant_');

        try {
            $apiKey = config('livekit.api_key');
            $apiSecret = config('livekit.api_secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'error' => 'LiveKit not configured properly',
                ], 500);
            }

            $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($participantIdentity)
                ->setName($participantName);

            $videoGrant = (new VideoGrant())
                ->setRoomJoin(true)
                ->setRoomName($roomName)
                ->setCanPublish(true)
                ->setCanSubscribe(true);

            $token = (new AccessToken($apiKey, $apiSecret))
                ->init($tokenOptions)
                ->setGrant($videoGrant)
                ->toJwt();

            Log::info('LiveKit token generated', [
                'room' => $roomName,
                'participant' => $participantName,
                'identity' => $participantIdentity,
            ]);

            return response()->json([
                'token' => $token,
                'url' => config('livekit.host'),
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'coturn' => [
                    'urls' => [
                        'stun:' . config('livekit.coturn.host') . ':' . config('livekit.coturn.port'),
                        'turn:' . config('livekit.coturn.host') . ':' . config('livekit.coturn.port'),
                    ],
                    'username' => config('livekit.coturn.username'),
                    'credential' => config('livekit.coturn.password'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating LiveKit token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to generate token',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available rooms
     */
    public function getRooms(Request $request)
    {
        // This would integrate with LiveKit's room service API
        // For now, return a simple response
        return response()->json([
            'rooms' => [],
            'message' => 'LiveKit room listing - to be implemented',
        ]);
    }

    /**
     * Get participants in a room
     */
    public function getParticipants(Request $request, string $roomName)
    {
        // This would integrate with LiveKit's room service API
        // For now, return a simple response
        return response()->json([
            'room' => $roomName,
            'participants' => [],
            'message' => 'LiveKit participant listing - to be implemented',
        ]);
    }
}
