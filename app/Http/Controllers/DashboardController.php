<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\SesionJuicio;
use App\Models\DialogoV2;
use App\Models\AsignacionRol;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Estadísticas principales
        $sesionesActivas = SesionJuicio::whereIn('estado', ['programada', 'en_curso'])->count();
        $totalSesiones = SesionJuicio::count();
        $totalDialogos = DialogoV2::count();
        
        // Participaciones del usuario
        $participacionesUsuario = AsignacionRol::where('usuario_id', $user->id)->count();
        
        // Sesiones recientes (últimas 5)
        $sesionesRecientes = SesionJuicio::with(['instructor', 'asignaciones'])
            ->when(in_array($user->tipo, ['estudiante', 'alumno']), function($query) use ($user) {
                // Estudiantes solo ven sesiones en las que participan
                return $query->whereHas('asignaciones', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                });
            })
            ->when($user->tipo === 'instructor', function($query) use ($user) {
                // Instructores ven sus propias sesiones
                return $query->where('instructor_id', $user->id);
            })
            ->orderBy('fecha_creacion', 'desc')
            ->limit(5)
            ->get();
        
        // Próximas sesiones (programadas)
        $proximasSesiones = SesionJuicio::with(['instructor', 'asignaciones'])
            ->where('estado', 'programada')
            ->when(in_array($user->tipo, ['estudiante', 'alumno']), function($query) use ($user) {
                return $query->whereHas('asignaciones', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                });
            })
            ->when($user->tipo === 'instructor', function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->orderBy('fecha_inicio', 'asc')
            ->limit(5)
            ->get();
        
        // Estadísticas del usuario
        $sesionesEsteMes = SesionJuicio::whereMonth('fecha_creacion', Carbon::now()->month)
            ->when(in_array($user->tipo, ['estudiante', 'alumno']), function($query) use ($user) {
                return $query->whereHas('asignaciones', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                });
            })
            ->when($user->tipo === 'instructor', function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->count();
        
        // Calcular tiempo total de sesiones del usuario
        $sesionesConDuracion = SesionJuicio::whereNotNull('fecha_fin')
            ->whereNotNull('fecha_inicio')
            ->when(in_array($user->tipo, ['estudiante', 'alumno']), function($query) use ($user) {
                return $query->whereHas('asignaciones', function($q) use ($user) {
                    $q->where('usuario_id', $user->id);
                });
            })
            ->when($user->tipo === 'instructor', function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->get();
        
        $tiempoTotalMinutos = $sesionesConDuracion->sum(function ($sesion) {
            return $sesion->fecha_inicio->diffInMinutes($sesion->fecha_fin);
        });
        
        $tiempoTotalHoras = floor($tiempoTotalMinutos / 60);
        $tiempoTotalMinutosRestantes = $tiempoTotalMinutos % 60;
        $tiempoTotalFormateado = $tiempoTotalHoras . 'h ' . $tiempoTotalMinutosRestantes . 'm';
        
        // Calcular porcentaje de progreso (sesiones este mes vs total)
        $porcentajeSesionesMes = $totalSesiones > 0 ? round(($sesionesEsteMes / $totalSesiones) * 100) : 0;
        $porcentajeParticipaciones = $totalSesiones > 0 ? round(($participacionesUsuario / $totalSesiones) * 100) : 0;
        
        return view('dashboard', [
            'user' => $user,
            'userType' => $user->tipo,
            'sesionesActivas' => $sesionesActivas,
            'totalSesiones' => $totalSesiones,
            'totalDialogos' => $totalDialogos,
            'participacionesUsuario' => $participacionesUsuario,
            'sesionesRecientes' => $sesionesRecientes,
            'proximasSesiones' => $proximasSesiones,
            'sesionesEsteMes' => $sesionesEsteMes,
            'tiempoTotalFormateado' => $tiempoTotalFormateado,
            'porcentajeSesionesMes' => min($porcentajeSesionesMes, 100),
            'porcentajeParticipaciones' => min($porcentajeParticipaciones, 100),
        ]);
    }
}
