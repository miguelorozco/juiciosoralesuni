<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\SesionJuicio;
use App\Models\Dialogo;
use App\Models\AsignacionRol;

class EstadisticasController extends Controller
{
    /**
     * Obtener estadísticas del dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            // Estadísticas básicas
            $sesionesTotales = SesionJuicio::count();
            $usuariosActivos = User::where('activo', true)->count();
            $dialogosTotales = Dialogo::where('activo', true)->count();
            
            // Calcular tiempo promedio de sesiones
            $sesionesConDuracion = SesionJuicio::whereNotNull('fecha_fin')
                ->whereNotNull('fecha_inicio')
                ->get();
            
            $tiempoPromedio = 0;
            if ($sesionesConDuracion->count() > 0) {
                $tiempoTotal = $sesionesConDuracion->sum(function ($sesion) {
                    return strtotime($sesion->fecha_fin) - strtotime($sesion->fecha_inicio);
                });
                $tiempoPromedio = round($tiempoTotal / $sesionesConDuracion->count() / 60); // en minutos
            }
            
            // Calcular cambios porcentuales (simulado)
            $sesionesCambio = '+12%';
            $usuariosCambio = '+8%';
            $dialogosCambio = '+15%';
            $tiempoCambio = '-5%';
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sesiones_totales' => $sesionesTotales,
                    'sesiones_cambio' => $sesionesCambio,
                    'usuarios_activos' => $usuariosActivos,
                    'usuarios_cambio' => $usuariosCambio,
                    'dialogos_totales' => $dialogosTotales,
                    'dialogos_cambio' => $dialogosCambio,
                    'tiempo_promedio' => $tiempoPromedio . ' min',
                    'tiempo_cambio' => $tiempoCambio
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
    
    /**
     * Obtener top instructores
     */
    public function topInstructores(): JsonResponse
    {
        try {
            $instructores = User::where('tipo', 'instructor')
                ->withCount(['sesionesComoInstructor'])
                ->with(['sesionesComoInstructor' => function ($query) {
                    $query->withCount('asignaciones');
                }])
                ->orderBy('sesiones_como_instructor_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($instructor) {
                    $participantesCount = $instructor->sesionesComoInstructor->sum('asignaciones_count');
                    $puntuacionPromedio = 8.5; // Simulado
                    
                    return [
                        'id' => $instructor->id,
                        'name' => $instructor->name,
                        'sesiones_count' => $instructor->sesiones_como_instructor_count,
                        'participantes_count' => $participantesCount,
                        'puntuacion_promedio' => $puntuacionPromedio
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $instructores
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo top instructores: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener top instructores'
            ], 500);
        }
    }
    
    /**
     * Obtener actividad reciente
     */
    public function actividadReciente(): JsonResponse
    {
        try {
            $actividad = collect([
                [
                    'id' => 1,
                    'descripcion' => 'Nueva sesión "Juicio Civil" creada',
                    'fecha' => now()->subMinutes(5)->toISOString()
                ],
                [
                    'id' => 2,
                    'descripcion' => 'Usuario "Juan Pérez" se registró',
                    'fecha' => now()->subMinutes(15)->toISOString()
                ],
                [
                    'id' => 3,
                    'descripcion' => 'Sesión "Juicio Penal" finalizada',
                    'fecha' => now()->subMinutes(30)->toISOString()
                ],
                [
                    'id' => 4,
                    'descripcion' => 'Diálogo "Contrato Laboral" actualizado',
                    'fecha' => now()->subHours(1)->toISOString()
                ],
                [
                    'id' => 5,
                    'descripcion' => 'Nuevo rol "Testigo" creado',
                    'fecha' => now()->subHours(2)->toISOString()
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $actividad
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividad reciente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener actividad reciente'
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    public function usuario(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            $sesionesParticipadas = AsignacionRol::where('usuario_id', $usuario->id)->count();
            $sesionesCreadas = SesionJuicio::where('instructor_id', $usuario->id)->count();
            $puntuacionPromedio = 8.2; // Simulado
            $tiempoTotal = '45h 30m'; // Simulado
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sesiones_participadas' => $sesionesParticipadas,
                    'sesiones_creadas' => $sesionesCreadas,
                    'puntuacion_promedio' => $puntuacionPromedio,
                    'tiempo_total' => $tiempoTotal
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del usuario'
            ], 500);
        }
    }
    
    /**
     * Obtener actividad del usuario
     */
    public function actividadUsuario(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
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
                'message' => 'Error al obtener actividad del usuario'
            ], 500);
        }
    }
}
