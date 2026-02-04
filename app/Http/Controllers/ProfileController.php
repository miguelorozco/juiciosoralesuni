<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\SesionJuicio;
use App\Models\AsignacionRol;
use Illuminate\View\View;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Mostrar vista de perfil
     */
    public function index(): View
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor inicia sesión para continuar');
        }
        
        return view('profile.index', [
            'user' => $user
        ]);
    }
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
                'nueva_contraseña' => 'required|string|min:6',
                'confirmar_contraseña' => 'required|string|same:nueva_contraseña'
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
            
            // Actualizar contraseña (el modelo User tiene el cast 'hashed')
            $usuario->update([
                'password' => $request->nueva_contraseña
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
            
            // Calcular estadísticas reales
            $sesionesParticipadas = AsignacionRol::where('usuario_id', $usuario->id)->count();
            $sesionesCreadas = SesionJuicio::where('instructor_id', $usuario->id)->count();
            
            // Calcular tiempo total de sesiones
            $sesionesConDuracion = SesionJuicio::whereNotNull('fecha_fin')
                ->whereNotNull('fecha_inicio')
                ->when(in_array($usuario->tipo, ['estudiante', 'alumno']), function($query) use ($usuario) {
                    return $query->whereHas('asignaciones', function($q) use ($usuario) {
                        $q->where('usuario_id', $usuario->id);
                    });
                })
                ->when($usuario->tipo === 'instructor', function($query) use ($usuario) {
                    return $query->where('instructor_id', $usuario->id);
                })
                ->get();
            
            $tiempoTotalMinutos = $sesionesConDuracion->sum(function ($sesion) {
                return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
            });
            
            $tiempoTotalHoras = floor($tiempoTotalMinutos / 60);
            $tiempoTotalMinutosRestantes = $tiempoTotalMinutos % 60;
            $tiempoTotalFormateado = $tiempoTotalHoras . 'h ' . $tiempoTotalMinutosRestantes . 'm';
            
            // Calcular puntuación promedio (basada en sesiones completadas)
            $sesionesCompletadas = $sesionesConDuracion->count();
            $puntuacionPromedio = $sesionesParticipadas > 0 
                ? round(($sesionesCompletadas / $sesionesParticipadas) * 10, 1)
                : 0;
            
            $estadisticas = [
                'sesiones_participadas' => $sesionesParticipadas,
                'sesiones_creadas' => $sesionesCreadas,
                'puntuacion_promedio' => $puntuacionPromedio,
                'tiempo_total' => $tiempoTotalFormateado
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
            $actividad = collect();
            
            // Actividad de sesiones en las que participó
            $asignacionesRecientes = AsignacionRol::where('usuario_id', $usuario->id)
                ->with('sesion')
                ->orderBy('fecha_asignacion', 'desc')
                ->limit(10)
                ->get();
            
            foreach ($asignacionesRecientes as $asignacion) {
                if ($asignacion->sesion) {
                    $actividad->push([
                        'id' => 'asignacion_' . $asignacion->id,
                        'tipo' => 'participacion',
                        'descripcion' => 'Participaste en la sesión "' . $asignacion->sesion->nombre . '"',
                        'fecha' => $asignacion->fecha_asignacion->toISOString()
                    ]);
                }
            }
            
            // Actividad de sesiones creadas (si es instructor)
            if ($usuario->tipo === 'instructor') {
                $sesionesCreadas = SesionJuicio::where('instructor_id', $usuario->id)
                    ->orderBy('fecha_creacion', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($sesionesCreadas as $sesion) {
                    $actividad->push([
                        'id' => 'sesion_creada_' . $sesion->id,
                        'tipo' => 'creacion',
                        'descripcion' => 'Creaste la sesión "' . $sesion->nombre . '"',
                        'fecha' => $sesion->fecha_creacion->toISOString()
                    ]);
                }
            }
            
            // Actividad de sesiones finalizadas
            $sesionesFinalizadas = SesionJuicio::whereNotNull('fecha_fin')
                ->when(in_array($usuario->tipo, ['estudiante', 'alumno']), function($query) use ($usuario) {
                    return $query->whereHas('asignaciones', function($q) use ($usuario) {
                        $q->where('usuario_id', $usuario->id);
                    });
                })
                ->when($usuario->tipo === 'instructor', function($query) use ($usuario) {
                    return $query->where('instructor_id', $usuario->id);
                })
                ->orderBy('fecha_fin', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($sesionesFinalizadas as $sesion) {
                $actividad->push([
                    'id' => 'sesion_finalizada_' . $sesion->id,
                    'tipo' => 'finalizacion',
                    'descripcion' => 'Sesión "' . $sesion->nombre . '" finalizada',
                    'fecha' => $sesion->fecha_fin->toISOString()
                ]);
            }
            
            // Ordenar por fecha descendente y limitar a 20
            $actividad = $actividad->sortByDesc('fecha')->take(20)->values();
            
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
        $sesionesParticipadas = AsignacionRol::where('usuario_id', $usuario->id)->count();
        $sesionesCreadas = SesionJuicio::where('instructor_id', $usuario->id)->count();
        
        $sesionesConDuracion = SesionJuicio::whereNotNull('fecha_fin')
            ->whereNotNull('fecha_inicio')
            ->when(in_array($usuario->tipo, ['estudiante', 'alumno']), function($query) use ($usuario) {
                return $query->whereHas('asignaciones', function($q) use ($usuario) {
                    $q->where('usuario_id', $usuario->id);
                });
            })
            ->when($usuario->tipo === 'instructor', function($query) use ($usuario) {
                return $query->where('instructor_id', $usuario->id);
            })
            ->get();
        
        $tiempoTotalMinutos = $sesionesConDuracion->sum(function ($sesion) {
            return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
        });
        
        $tiempoTotalHoras = floor($tiempoTotalMinutos / 60);
        $tiempoTotalMinutosRestantes = $tiempoTotalMinutos % 60;
        $tiempoTotalFormateado = $tiempoTotalHoras . 'h ' . $tiempoTotalMinutosRestantes . 'm';
        
        $sesionesCompletadas = $sesionesConDuracion->count();
        $puntuacionPromedio = $sesionesParticipadas > 0 
            ? round(($sesionesCompletadas / $sesionesParticipadas) * 10, 1)
            : 0;
        
        return [
            'sesiones_participadas' => $sesionesParticipadas,
            'sesiones_creadas' => $sesionesCreadas,
            'puntuacion_promedio' => $puntuacionPromedio,
            'tiempo_total' => $tiempoTotalFormateado
        ];
    }
    
    /**
     * Obtener actividad del usuario
     */
    private function obtenerActividadUsuario(User $usuario): array
    {
        $actividad = collect();
        
        // Actividad de sesiones en las que participó
        $asignacionesRecientes = AsignacionRol::where('usuario_id', $usuario->id)
            ->with('sesion')
            ->orderBy('fecha_asignacion', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($asignacionesRecientes as $asignacion) {
            if ($asignacion->sesion) {
                $actividad->push([
                    'id' => 'asignacion_' . $asignacion->id,
                    'descripcion' => 'Participaste en la sesión "' . $asignacion->sesion->nombre . '"',
                    'fecha' => $asignacion->fecha_asignacion->toISOString()
                ]);
            }
        }
        
        // Actividad de sesiones creadas (si es instructor)
        if ($usuario->tipo === 'instructor') {
            $sesionesCreadas = SesionJuicio::where('instructor_id', $usuario->id)
                ->orderBy('fecha_creacion', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($sesionesCreadas as $sesion) {
                $actividad->push([
                    'id' => 'sesion_creada_' . $sesion->id,
                    'descripcion' => 'Creaste la sesión "' . $sesion->nombre . '"',
                    'fecha' => $sesion->fecha_creacion->toISOString()
                ]);
            }
        }
        
        return $actividad->sortByDesc('fecha')->take(20)->values()->toArray();
    }
}
