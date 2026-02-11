<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use App\Models\SesionDialogoV2;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class UnityEntryController extends Controller
{
    /**
     * Generar enlace de entrada directa a Unity para un usuario
     */
    public function generateUnityEntryLink(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'session_id' => 'required|integer|exists:sesiones_juicios,id'
            ]);

            $userId = $request->user_id;
            $sessionId = $request->session_id;

            // Verificar que el usuario tiene una asignación en la sesión
            $assignment = AsignacionRol::where('usuario_id', $userId)
                ->where('sesion_id', $sessionId)
                ->with(['sesion', 'rolDisponible', 'usuario'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene una asignación en esta sesión',
                    'error_code' => 'NO_ASSIGNMENT'
                ], 404);
            }

            // Verificar que la asignación tiene todos los datos necesarios
            if (!$assignment->sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión asociada no existe',
                    'error_code' => 'SESSION_NOT_FOUND'
                ], 404);
            }

            if (!$assignment->usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario asociado no existe',
                    'error_code' => 'USER_NOT_FOUND'
                ], 404);
            }

            if (!$assignment->rolDisponible) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol asignado no existe',
                    'error_code' => 'ROLE_NOT_FOUND'
                ], 404);
            }

            // Verificar que la sesión está activa
            if (!in_array($assignment->sesion->estado, ['programada', 'en_curso'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'La sesión no está activa',
                    'error_code' => 'SESSION_NOT_ACTIVE'
                ], 400);
            }

            // Generar token de acceso temporal
            $accessToken = $this->generateAccessToken($userId, $sessionId);

            // Generar URL de entrada a Unity
            $unityEntryUrl = $this->generateUnityEntryUrl($accessToken, $sessionId);

            Log::info("Enlace de entrada a Unity generado", [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'role' => $assignment->rolDisponible ? $assignment->rolDisponible->nombre : 'Sin rol'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'unity_entry_url' => $unityEntryUrl,
                    'access_token' => $accessToken,
                    'session' => [
                        'id' => $assignment->sesion->id,
                        'nombre' => $assignment->sesion->nombre,
                        'estado' => $assignment->sesion->estado
                    ],
                    'user' => [
                        'id' => $assignment->usuario->id,
                        'name' => $assignment->usuario->name,
                        'email' => $assignment->usuario->email
                    ],
                    'role' => [
                        'id' => $assignment->rolDisponible->id,
                        'nombre' => $assignment->rolDisponible->nombre,
                        'descripcion' => $assignment->rolDisponible->descripcion,
                        'color' => $assignment->rolDisponible->color,
                        'icono' => $assignment->rolDisponible->icono
                    ],
                    'assignment' => [
                        'id' => $assignment->id,
                        'confirmado' => $assignment->confirmado,
                        'fecha_asignacion' => $assignment->fecha_asignacion
                    ]
                ],
                'instructions' => [
                    'step_1' => 'Hacer clic en el enlace de Unity',
                    'step_2' => 'Unity se abrirá automáticamente',
                    'step_3' => 'El usuario será autenticado automáticamente',
                    'step_4' => 'Se cargará la sesión y el rol asignado',
                    'step_5' => 'El diálogo comenzará automáticamente'
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error("Error generando enlace de entrada a Unity", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Generar token de acceso temporal
     */
    private function generateAccessToken(int $userId, int $sessionId): string
    {
        // Crear un token temporal que expire en 1 hora
        $payload = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'expires_at' => now()->addHour()->timestamp,
            'type' => 'unity_entry'
        ];

        // Codificar el payload (en producción usar JWT o similar)
        return base64_encode(json_encode($payload));
    }

    /**
     * Base URL para enlaces Unity/LiveKit: siempre localhost (requerido por LiveKit).
     */
    private function unityBaseUrl(): string
    {
        $url = rtrim(config('app.url'), '/');
        $url = preg_replace('#^https?://127\.0\.0\.1(:\d+)?#', 'http://localhost', $url);
        $url = preg_replace('#^https?://localhost(:\d+)?#', 'http://localhost', $url);
        return $url ?: 'http://localhost';
    }

    /**
     * Generar URL de entrada a Unity
     */
    private function generateUnityEntryUrl(string $accessToken, int $sessionId): string
    {
        $baseUrl = $this->unityBaseUrl();
        $unityUrl = $baseUrl . '/unity-entry';
        return $unityUrl . '?token=' . urlencode($accessToken) . '&session=' . $sessionId;
    }

    /**
     * Página de entrada a Unity (para mostrar instrucciones)
     */
    public function unityEntryPage(Request $request)
    {
        $token = $request->get('token');
        $sessionId = $request->get('session');

        if (!$token || !$sessionId) {
            return view('unity.entry-error', [
                'error' => 'Token o sesión no proporcionados'
            ]);
        }

        try {
            // Decodificar token
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || $payload['expires_at'] < time()) {
                return view('unity.entry-error', [
                    'error' => 'Token expirado o inválido'
                ]);
            }

            // Obtener información de la sesión
            $assignment = AsignacionRol::where('usuario_id', $payload['user_id'])
                ->where('sesion_id', $sessionId)
                ->with(['sesion', 'rolDisponible', 'usuario'])
                ->first();

            if (!$assignment) {
                return view('unity.entry-error', [
                    'error' => 'Asignación no encontrada'
                ]);
            }

            // Título del diálogo (debug): diálogo configurado para esta sesión
            $sesionDialogo = SesionDialogoV2::where('sesion_id', $assignment->sesion_id)
                ->with('dialogo')
                ->first();
            $dialogoTitulo = $sesionDialogo?->dialogo?->nombre;

            return view('unity.entry-page', [
                'assignment' => $assignment,
                'unityUrl' => $this->generateUnityWebGLUrl($token, $sessionId),
                'token' => $token,
                'dialogoTitulo' => $dialogoTitulo,
            ]);

        } catch (\Exception $e) {
            Log::error("Error en página de entrada a Unity", [
                'error' => $e->getMessage()
            ]);

            return view('unity.entry-error', [
                'error' => 'Error procesando la entrada'
            ]);
        }
    }

    /**
     * Generar URL de Unity WebGL (siempre en localhost para LiveKit)
     */
    private function generateUnityWebGLUrl(string $token, int $sessionId): string
    {
        $baseUrl = $this->unityBaseUrl();
        $unityGameUrl = $baseUrl . '/unity-game';
        return $unityGameUrl . '?token=' . urlencode($token) . '&session=' . $sessionId;
    }

    /**
     * Obtener información de entrada para Unity
     */
    public function getUnityEntryInfo(Request $request): JsonResponse
    {
        try {
            $token = $request->get('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no proporcionado'
                ], 400);
            }

            // Decodificar token
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || $payload['expires_at'] < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expirado o inválido'
                ], 401);
            }

            // Obtener información completa
            $assignment = AsignacionRol::where('usuario_id', $payload['user_id'])
                ->where('sesion_id', $payload['session_id'])
                ->with(['sesion', 'rolDisponible', 'usuario'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $assignment->usuario->id,
                        'name' => $assignment->usuario->name,
                        'email' => $assignment->usuario->email
                    ],
                    'session' => [
                        'id' => $assignment->sesion->id,
                        'nombre' => $assignment->sesion->nombre,
                        'estado' => $assignment->sesion->estado
                    ],
                    'role' => [
                        'id' => $assignment->rolDisponible->id,
                        'nombre' => $assignment->rolDisponible->nombre,
                        'descripcion' => $assignment->rolDisponible->descripcion,
                        'color' => $assignment->rolDisponible->color,
                        'icono' => $assignment->rolDisponible->icono
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo información de entrada a Unity", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
