<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\SesionJuicio;
use App\Models\Dialogo;
use App\Models\DialogoV2;
use App\Models\AsignacionRol;
use Carbon\Carbon;
use Illuminate\View\View;

class EstadisticasController extends Controller
{
    /**
     * Mostrar vista de estadísticas
     */
    public function index(): View
    {
        $user = Auth::user();
        
        if (!$user) {
            Log::warning('Intento de acceso a estadísticas sin autenticación');
            return redirect()->route('login')->with('error', 'Por favor inicia sesión para continuar');
        }
        
        Log::info('Acceso a estadísticas por usuario: ' . $user->email . ' (tipo: ' . $user->tipo . ')');
        
        return view('estadisticas.index', [
            'user' => $user
        ]);
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $usuario = Auth::user();
            
            // Obtener período desde query string (default: este mes)
            $periodo = request()->get('periodo', 'mes');
            $fechaInicio = $this->getFechaInicioPeriodo($periodo);
            $fechaFin = Carbon::now();
            
            // Estadísticas básicas del período actual
            $sesionesTotales = SesionJuicio::where('fecha_creacion', '>=', $fechaInicio)->count();
            $usuariosActivos = User::where('activo', true)
                ->where(function($query) use ($fechaInicio) {
                    $query->where('created_at', '>=', $fechaInicio)
                          ->orWhereHas('asignacionesRoles', function($q) use ($fechaInicio) {
                              $q->where('fecha_asignacion', '>=', $fechaInicio);
                          });
                })
                ->distinct()
                ->count();
            $dialogosTotales = DialogoV2::where('created_at', '>=', $fechaInicio)->count();
            
            // Calcular tiempo promedio de sesiones
            $sesionesConDuracion = SesionJuicio::whereNotNull('fecha_fin')
                ->whereNotNull('fecha_inicio')
                ->where('fecha_inicio', '>=', $fechaInicio)
                ->get();
            
            $tiempoPromedio = 0;
            if ($sesionesConDuracion->count() > 0) {
                $tiempoTotalMinutos = $sesionesConDuracion->sum(function ($sesion) {
                    return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
                });
                $tiempoPromedio = round($tiempoTotalMinutos / $sesionesConDuracion->count());
            }
            
            // Calcular cambios porcentuales comparando con período anterior
            $periodoAnteriorInicio = $this->getFechaInicioPeriodoAnterior($periodo);
            $periodoAnteriorFin = $fechaInicio->copy()->subSecond();
            
            $sesionesAnteriores = SesionJuicio::whereBetween('fecha_creacion', [$periodoAnteriorInicio, $periodoAnteriorFin])->count();
            $usuariosAnteriores = User::where('activo', true)
                ->where(function($query) use ($periodoAnteriorInicio, $periodoAnteriorFin) {
                    $query->whereBetween('created_at', [$periodoAnteriorInicio, $periodoAnteriorFin])
                          ->orWhereHas('asignacionesRoles', function($q) use ($periodoAnteriorInicio, $periodoAnteriorFin) {
                              $q->whereBetween('fecha_asignacion', [$periodoAnteriorInicio, $periodoAnteriorFin]);
                          });
                })
                ->distinct()
                ->count();
            $dialogosAnteriores = DialogoV2::whereBetween('created_at', [$periodoAnteriorInicio, $periodoAnteriorFin])->count();
            
            $sesionesCambio = $this->calcularCambioPorcentual($sesionesAnteriores, $sesionesTotales);
            $usuariosCambio = $this->calcularCambioPorcentual($usuariosAnteriores, $usuariosActivos);
            $dialogosCambio = $this->calcularCambioPorcentual($dialogosAnteriores, $dialogosTotales);
            
            // Calcular cambio de tiempo promedio
            $sesionesAnterioresConDuracion = SesionJuicio::whereNotNull('fecha_fin')
                ->whereNotNull('fecha_inicio')
                ->whereBetween('fecha_inicio', [$periodoAnteriorInicio, $periodoAnteriorFin])
                ->get();
            
            $tiempoPromedioAnterior = 0;
            if ($sesionesAnterioresConDuracion->count() > 0) {
                $tiempoTotalAnterior = $sesionesAnterioresConDuracion->sum(function ($sesion) {
                    return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
                });
                $tiempoPromedioAnterior = round($tiempoTotalAnterior / $sesionesAnterioresConDuracion->count());
            }
            $tiempoCambio = $this->calcularCambioPorcentual($tiempoPromedioAnterior, $tiempoPromedio);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'sesiones_totales' => $sesionesTotales,
                    'sesiones_cambio' => $sesionesCambio,
                    'usuarios_activos' => $usuariosActivos,
                    'usuarios_cambio' => $usuariosCambio,
                    'dialogos_totales' => $dialogosTotales,
                    'dialogos_cambio' => $dialogosCambio,
                    'tiempo_promedio' => $tiempoPromedio > 0 ? $tiempoPromedio . ' min' : '0 min',
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
                    
                    // Calcular tiempo promedio de sesiones del instructor
                    $sesionesConDuracion = $instructor->sesionesComoInstructor
                        ->filter(function($sesion) {
                            return $sesion->fecha_inicio && $sesion->fecha_fin;
                        });
                    
                    $tiempoPromedioMinutos = 0;
                    if ($sesionesConDuracion->count() > 0) {
                        $tiempoTotal = $sesionesConDuracion->sum(function($sesion) {
                            return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
                        });
                        $tiempoPromedioMinutos = round($tiempoTotal / $sesionesConDuracion->count());
                    }
                    
                    // Calcular "puntuación" basada en métricas reales (sesiones completadas vs totales)
                    $sesionesCompletadas = $instructor->sesionesComoInstructor->where('estado', 'finalizada')->count();
                    $puntuacionPromedio = $instructor->sesiones_como_instructor_count > 0 
                        ? round(($sesionesCompletadas / $instructor->sesiones_como_instructor_count) * 10, 1)
                        : 0;
                    
                    return [
                        'id' => $instructor->id,
                        'name' => $instructor->name . ' ' . ($instructor->apellido ?? ''),
                        'sesiones_count' => $instructor->sesiones_como_instructor_count,
                        'participantes_count' => $participantesCount,
                        'puntuacion_promedio' => $puntuacionPromedio,
                        'tiempo_promedio' => $tiempoPromedioMinutos
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
            $actividad = collect();
            
            // Actividad de sesiones recientes
            $sesionesRecientes = SesionJuicio::with('instructor')
                ->orderBy('fecha_creacion', 'desc')
                ->limit(10)
                ->get();
            
            foreach ($sesionesRecientes as $sesion) {
                $actividad->push([
                    'id' => 'sesion_' . $sesion->id,
                    'tipo' => 'sesion',
                    'descripcion' => 'Nueva sesión "' . $sesion->nombre . '" creada' . 
                                    ($sesion->instructor ? ' por ' . $sesion->instructor->name : ''),
                    'fecha' => $sesion->fecha_creacion->toISOString()
                ]);
            }
            
            // Actividad de sesiones finalizadas recientes
            $sesionesFinalizadas = SesionJuicio::with('instructor')
                ->where('estado', 'finalizada')
                ->whereNotNull('fecha_fin')
                ->orderBy('fecha_fin', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($sesionesFinalizadas as $sesion) {
                $actividad->push([
                    'id' => 'sesion_finalizada_' . $sesion->id,
                    'tipo' => 'sesion_finalizada',
                    'descripcion' => 'Sesión "' . $sesion->nombre . '" finalizada',
                    'fecha' => $sesion->fecha_fin->toISOString()
                ]);
            }
            
            // Actividad de usuarios recientes (solo últimos 5)
            $usuariosRecientes = User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($usuariosRecientes as $usuario) {
                $actividad->push([
                    'id' => 'usuario_' . $usuario->id,
                    'tipo' => 'usuario',
                    'descripcion' => 'Usuario "' . $usuario->name . ' ' . ($usuario->apellido ?? '') . '" se registró',
                    'fecha' => $usuario->created_at->toISOString()
                ]);
            }
            
            // Actividad de diálogos recientes
            $dialogosRecientes = DialogoV2::with('creador')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($dialogosRecientes as $dialogo) {
                $actividad->push([
                    'id' => 'dialogo_' . $dialogo->id,
                    'tipo' => 'dialogo',
                    'descripcion' => 'Diálogo "' . $dialogo->nombre . '" ' . 
                                    ($dialogo->created_at->equalTo($dialogo->updated_at) ? 'creado' : 'actualizado') .
                                    ($dialogo->creador ? ' por ' . $dialogo->creador->name : ''),
                    'fecha' => $dialogo->updated_at->toISOString()
                ]);
            }
            
            // Ordenar por fecha descendente y limitar a 20
            $actividad = $actividad->sortByDesc('fecha')->take(20)->values();
            
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

    /**
     * Obtener datos para gráfico de sesiones por mes
     */
    public function sesionesPorMes(): JsonResponse
    {
        try {
            $meses = [];
            $sesionesData = [];
            $participantesData = [];
            
            // Obtener últimos 12 meses
            for ($i = 11; $i >= 0; $i--) {
                $fecha = Carbon::now()->subMonths($i);
                $mesInicio = $fecha->copy()->startOfMonth();
                $mesFin = $fecha->copy()->endOfMonth();
                
                $meses[] = $fecha->format('M');
                
                $sesionesMes = SesionJuicio::whereBetween('fecha_creacion', [$mesInicio, $mesFin])->count();
                $sesionesData[] = $sesionesMes;
                
                $participantesMes = AsignacionRol::whereBetween('fecha_asignacion', [$mesInicio, $mesFin])
                    ->distinct('usuario_id')
                    ->count('usuario_id');
                $participantesData[] = $participantesMes;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'meses' => $meses,
                    'sesiones' => $sesionesData,
                    'participantes' => $participantesData
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo sesiones por mes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del gráfico'
            ], 500);
        }
    }

    /**
     * Obtener distribución de usuarios
     */
    public function distribucionUsuarios(): JsonResponse
    {
        try {
            $admins = User::where('tipo', 'admin')->where('activo', true)->count();
            $instructores = User::where('tipo', 'instructor')->where('activo', true)->count();
            $estudiantes = User::whereIn('tipo', ['estudiante', 'alumno'])->where('activo', true)->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'admins' => $admins,
                    'instructores' => $instructores,
                    'estudiantes' => $estudiantes
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo distribución de usuarios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribución de usuarios'
            ], 500);
        }
    }

    /**
     * Helper: Obtener fecha de inicio según período
     */
    private function getFechaInicioPeriodo(string $periodo): Carbon
    {
        return match($periodo) {
            'hoy' => Carbon::today()->startOfDay(),
            'semana' => Carbon::now()->startOfWeek(),
            'mes' => Carbon::now()->startOfMonth(),
            'año' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };
    }

    /**
     * Helper: Obtener fecha de inicio del período anterior
     */
    private function getFechaInicioPeriodoAnterior(string $periodo): Carbon
    {
        return match($periodo) {
            'hoy' => Carbon::yesterday()->startOfDay(),
            'semana' => Carbon::now()->subWeek()->startOfWeek(),
            'mes' => Carbon::now()->subMonth()->startOfMonth(),
            'año' => Carbon::now()->subYear()->startOfYear(),
            default => Carbon::now()->subMonth()->startOfMonth()
        };
    }

    /**
     * Helper: Calcular cambio porcentual
     */
    private function calcularCambioPorcentual(float $valorAnterior, float $valorActual): string
    {
        if ($valorAnterior == 0) {
            return $valorActual > 0 ? '+100%' : '0%';
        }
        
        $cambio = (($valorActual - $valorAnterior) / $valorAnterior) * 100;
        $signo = $cambio >= 0 ? '+' : '';
        
        return $signo . round($cambio, 1) . '%';
    }
}
