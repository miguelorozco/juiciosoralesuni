<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Actualizar perfil del usuario
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
                'preferencias' => 'array'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $usuario->update([
                'name' => $request->name,
                'apellido' => $request->apellido,
                'email' => $request->email,
                'configuracion' => $request->preferencias ?? $usuario->configuracion
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'user' => $usuario->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ], 500);
        }
    }
    
    /**
     * Cambiar contraseña del usuario
     */
    public function cambiarContraseña(Request $request): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'contraseña_actual' => 'required|string',
                'nueva_contraseña' => 'required|string|min:6|confirmed'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Verificar contraseña actual
            if (!Hash::check($request->contraseña_actual, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }
            
            // Actualizar contraseña
            $usuario->update([
                'password' => Hash::make($request->nueva_contraseña)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error cambiando contraseña: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña'
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            // Simular estadísticas (en producción se calcularían desde la BD)
            $estadisticas = [
                'sesiones_participadas' => 15,
                'sesiones_creadas' => 8,
                'puntuacion_promedio' => 8.2,
                'tiempo_total' => '45h 30m'
            ];
            
            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
    
    /**
     * Obtener actividad del usuario
     */
    public function actividad(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            // Simular actividad reciente
            $actividad = collect([
                [
                    'id' => 1,
                    'descripcion' => 'Participaste en la sesión "Juicio Civil"',
                    'fecha' => now()->subHours(2)->toISOString()
                ],
                [
                    'id' => 2,
                    'descripcion' => 'Creaste la sesión "Juicio Penal"',
                    'fecha' => now()->subDays(1)->toISOString()
                ],
                [
                    'id' => 3,
                    'descripcion' => 'Actualizaste tu perfil',
                    'fecha' => now()->subDays(3)->toISOString()
                ],
                [
                    'id' => 4,
                    'descripcion' => 'Completaste el diálogo "Contrato Laboral"',
                    'fecha' => now()->subDays(5)->toISOString()
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $actividad
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividad del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividad'
            ], 500);
        }
    }
    
    /**
     * Exportar datos del usuario
     */
    public function exportarDatos(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            $datos = [
                'usuario' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'apellido' => $usuario->apellido,
                    'email' => $usuario->email,
                    'tipo' => $usuario->tipo,
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at
                ],
                'estadisticas' => $this->obtenerEstadisticasUsuario($usuario),
                'actividad' => $this->obtenerActividadUsuario($usuario),
                'fecha_exportacion' => now()->toISOString()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $datos
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error exportando datos del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar datos'
            ], 500);
        }
    }
    
    /**
     * Eliminar cuenta del usuario
     */
    public function eliminarCuenta(Request $request): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'confirmacion' => 'required|string|in:ELIMINAR'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmación requerida'
                ], 400);
            }
            
            // En producción se implementaría la eliminación real
            Log::info('Solicitud de eliminación de cuenta para usuario: ' . $usuario->email);
            
            return response()->json([
                'success' => true,
                'message' => 'Solicitud de eliminación procesada'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error eliminando cuenta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cuenta'
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    private function obtenerEstadisticasUsuario(User $usuario): array
    {
        // Simular estadísticas
        return [
            'sesiones_participadas' => 15,
            'sesiones_creadas' => 8,
            'puntuacion_promedio' => 8.2,
            'tiempo_total' => '45h 30m'
        ];
    }
    
    /**
     * Obtener actividad del usuario
     */
    private function obtenerActividadUsuario(User $usuario): array
    {
        // Simular actividad
        return [
            [
                'id' => 1,
                'descripcion' => 'Participaste en la sesión "Juicio Civil"',
                'fecha' => now()->subHours(2)->toISOString()
            ],
            [
                'id' => 2,
                'descripcion' => 'Creaste la sesión "Juicio Penal"',
                'fecha' => now()->subDays(1)->toISOString()
            ]
        ];
    }
}
