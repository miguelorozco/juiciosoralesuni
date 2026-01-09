<?php

namespace App\Http\Controllers;

use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UnitySessionController extends Controller
{
    /**
     * Buscar sesión por código o ID
     * GET /api/unity/sesiones/buscar-por-codigo/{codigo}
     */
    public function buscarPorCodigo($codigo): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Intentar buscar por ID primero (si es numérico), luego por código si existe
            $sesion = null;
            if (is_numeric($codigo)) {
                $sesion = SesionJuicio::with(['instructor', 'plantilla'])
                    ->where('id', $codigo)
                    ->first();
            }
            
            // Si no se encontró por ID, intentar por código (si existe el campo)
            if (!$sesion) {
                $sesion = SesionJuicio::with(['instructor', 'plantilla'])
                    ->where('id', $codigo)
                    ->first();
            }

            if (!$sesion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada',
                    'error_code' => 'SESSION_NOT_FOUND'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $sesion->id,
                    'nombre' => $sesion->nombre,
                    'descripcion' => $sesion->descripcion,
                    'estado' => $sesion->estado,
                    'fecha_inicio' => $sesion->fecha_inicio,
                    'fecha_fin' => $sesion->fecha_fin,
                    'max_participantes' => $sesion->max_participantes,
                    'participantes_count' => $sesion->asignaciones()->count(),
                    'instructor' => [
                        'id' => $sesion->instructor->id,
                        'name' => $sesion->instructor->name,
                        'email' => $sesion->instructor->email,
                    ],
                    'plantilla' => $sesion->plantilla ? [
                        'id' => $sesion->plantilla->id,
                        'nombre' => $sesion->plantilla->nombre,
                        'descripcion' => $sesion->plantilla->descripcion,
                    ] : null,
                    'unity_room_id' => $sesion->unity_room_id,
                    'configuracion' => $sesion->configuracion,
                ],
                'server_time' => now()->toISOString(),
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Obtener mi rol en una sesión
     * GET /api/unity/sesiones/{id}/mi-rol
     */
    public function obtenerMiRol($id): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $asignacion = AsignacionRol::with(['rolDisponible', 'usuario', 'sesion'])
                ->where('sesion_id', $id)
                ->where('usuario_id', $user->id)
                ->first();

            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un rol asignado en esta sesión',
                    'error_code' => 'NO_ROLE_ASSIGNED'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $asignacion->id,
                    'sesion_id' => $asignacion->sesion_id,
                    'usuario_id' => $asignacion->usuario_id,
                    'rol_id' => $asignacion->rol_id,
                    'confirmado' => $asignacion->confirmado,
                    'fecha_asignacion' => $asignacion->fecha_asignacion,
                    'notas' => $asignacion->notas,
                    'rol' => [
                        'id' => $asignacion->rolDisponible->id,
                        'nombre' => $asignacion->rolDisponible->nombre,
                        'descripcion' => $asignacion->rolDisponible->descripcion,
                        'color' => $asignacion->rolDisponible->color,
                        'icono' => $asignacion->rolDisponible->icono,
                        'activo' => $asignacion->rolDisponible->activo,
                    ],
                    'usuario' => [
                        'id' => $asignacion->usuario->id,
                        'name' => $asignacion->usuario->name,
                        'email' => $asignacion->usuario->email,
                    ],
                ],
                'server_time' => now()->toISOString(),
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Confirmar rol en una sesión
     * POST /api/unity/sesiones/{id}/confirmar-rol
     */
    public function confirmarRol(Request $request, $id): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $validated = $request->validate([
                'confirmado' => 'required|boolean'
            ]);

            $asignacion = AsignacionRol::with(['rolDisponible', 'usuario', 'sesion'])
                ->where('sesion_id', $id)
                ->where('usuario_id', $user->id)
                ->first();

            if (!$asignacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un rol asignado en esta sesión',
                    'error_code' => 'NO_ROLE_ASSIGNED'
                ], 404);
            }

            $asignacion->confirmado = $validated['confirmado'];
            $asignacion->save();
            
            // Recargar la relación después de guardar
            $asignacion->load('rolDisponible');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $asignacion->id,
                    'sesion_id' => $asignacion->sesion_id,
                    'usuario_id' => $asignacion->usuario_id,
                    'rol_id' => $asignacion->rol_id,
                    'confirmado' => $asignacion->confirmado,
                    'fecha_asignacion' => $asignacion->fecha_asignacion,
                    'notas' => $asignacion->notas,
                    'rol' => [
                        'id' => $asignacion->rolDisponible->id,
                        'nombre' => $asignacion->rolDisponible->nombre,
                        'descripcion' => $asignacion->rolDisponible->descripcion,
                        'color' => $asignacion->rolDisponible->color,
                        'icono' => $asignacion->rolDisponible->icono,
                        'activo' => $asignacion->rolDisponible->activo,
                    ],
                    'usuario' => [
                        'id' => $asignacion->usuario->id,
                        'name' => $asignacion->usuario->name,
                        'email' => $asignacion->usuario->email,
                    ],
                ],
                'message' => $validated['confirmado'] ? 'Rol confirmado exitosamente' : 'Rol desconfirmado exitosamente',
                'server_time' => now()->toISOString(),
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * Obtener sesiones disponibles
     * GET /api/unity/sesiones/disponibles
     */
    public function disponibles(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $query = SesionJuicio::with(['instructor', 'plantilla'])
                ->whereIn('estado', ['programada', 'en_curso']);

            // Filtrar solo sesiones donde el usuario tiene un rol asignado
            $query->whereHas('asignaciones', function($q) use ($user) {
                $q->where('usuario_id', $user->id);
            });

            // Ordenar por fecha de inicio
            $query->orderBy('fecha_inicio', 'asc');

            $sesiones = $query->get();

            $sesionesData = $sesiones->map(function($sesion) {
                return [
                    'id' => $sesion->id,
                    'nombre' => $sesion->nombre,
                    'descripcion' => $sesion->descripcion,
                    'estado' => $sesion->estado,
                    'fecha_inicio' => $sesion->fecha_inicio,
                    'fecha_fin' => $sesion->fecha_fin,
                    'max_participantes' => $sesion->max_participantes,
                    'participantes_count' => $sesion->asignaciones()->count(),
                    'instructor' => [
                        'id' => $sesion->instructor->id,
                        'name' => $sesion->instructor->name,
                        'email' => $sesion->instructor->email,
                    ],
                    'plantilla' => $sesion->plantilla ? [
                        'id' => $sesion->plantilla->id,
                        'nombre' => $sesion->plantilla->nombre,
                        'descripcion' => $sesion->plantilla->descripcion,
                    ] : null,
                    'unity_room_id' => $sesion->unity_room_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $sesionesData,
                'message' => 'Sesiones disponibles obtenidas exitosamente',
                'server_time' => now()->toISOString(),
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage(),
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }
}

