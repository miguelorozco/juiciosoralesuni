<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionDialogo extends Model
{
    use HasFactory;

    protected $table = 'sesiones_dialogos';

    protected $fillable = [
        'sesion_id',
        'dialogo_id',
        'nodo_actual_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'variables',
        'configuracion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'variables' => 'array',
            'configuracion' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function sesion()
    {
        return $this->belongsTo(SesionJuicio::class, 'sesion_id');
    }

    public function dialogo()
    {
        return $this->belongsTo(Dialogo::class);
    }

    public function nodoActual()
    {
        return $this->belongsTo(NodoDialogo::class, 'nodo_actual_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionSesion::class, 'sesion_id', 'sesion_id');
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['iniciado', 'en_curso', 'pausado']);
    }

    public function scopeEnCurso($query)
    {
        return $query->where('estado', 'en_curso');
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', 'finalizada');
    }

    public function scopeDeSesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }

    /**
     * Accessors
     */
    public function getTiempoTranscurridoAttribute()
    {
        if (!$this->fecha_inicio) {
            return 0;
        }

        $fechaFin = $this->fecha_fin ?? now();
        return $this->fecha_inicio->diffInMinutes($fechaFin);
    }

    public function getEstaActivaAttribute()
    {
        return in_array($this->estado, ['iniciado', 'en_curso', 'pausado']);
    }

    public function getPuedeAvanzarAttribute()
    {
        return $this->estado === 'en_curso' && $this->nodoActual;
    }

    public function getProgresoAttribute()
    {
        return $this->configuracion['progreso'] ?? [
            'nodos_visitados' => 0,
            'total_nodos' => 0,
            'porcentaje' => 0,
            'tiempo_transcurrido' => 0,
        ];
    }

    /**
     * Métodos útiles
     */
    public function iniciar()
    {
        $nodoInicial = $this->dialogo->nodo_inicial;
        
        if (!$nodoInicial) {
            return false;
        }

        return $this->update([
            'estado' => 'en_curso',
            'nodo_actual_id' => $nodoInicial->id,
            'fecha_inicio' => now(),
            'variables' => $this->configuracion['variables_iniciales'] ?? [],
        ]);
    }

    public function pausar()
    {
        return $this->update(['estado' => 'pausado']);
    }

    public function reanudar()
    {
        return $this->update(['estado' => 'en_curso']);
    }

    public function finalizar()
    {
        return $this->update([
            'estado' => 'finalizada',
            'fecha_fin' => now(),
        ]);
    }

    public function avanzarANodo($nodoId)
    {
        $nodo = NodoDialogo::find($nodoId);
        
        if (!$nodo || $nodo->dialogo_id !== $this->dialogo_id) {
            return false;
        }

        $this->update(['nodo_actual_id' => $nodoId]);
        $this->actualizarProgreso();
        
        return true;
    }

    public function procesarDecision($usuarioId, $rolId, $respuestaId, $decisionTexto = null, $tiempoRespuesta = null)
    {
        $respuesta = RespuestaDialogo::find($respuestaId);
        
        if (!$respuesta || $respuesta->nodo_padre_id !== $this->nodo_actual_id) {
            return false;
        }

        // Crear registro de decisión
        $decision = DecisionSesion::create([
            'sesion_dialogo_id' => $this->id,
            'sesion_id' => $this->sesion_id,
            'usuario_id' => $usuarioId,
            'rol_id' => $rolId,
            'nodo_dialogo_id' => $this->nodo_actual_id,
            'respuesta_id' => $respuestaId,
            'decision_texto' => $decisionTexto,
            'tiempo_respuesta' => $tiempoRespuesta,
            'fecha_decision' => now(),
        ]);

        // Aplicar consecuencias de la respuesta
        $this->aplicarConsecuencias($respuesta);

        // Avanzar al siguiente nodo si existe
        if ($respuesta->nodo_siguiente_id) {
            $this->avanzarANodo($respuesta->nodo_siguiente_id);
        } else {
            // Si no hay siguiente nodo, finalizar el diálogo
            $this->finalizar();
        }

        return $decision;
    }

    public function aplicarConsecuencias($respuesta)
    {
        $variables = $this->variables ?? [];
        $nuevasVariables = $respuesta->aplicarConsecuencias($variables);
        
        $this->update(['variables' => $nuevasVariables]);
    }

    public function obtenerRespuestasDisponibles($usuarioId, $rolId)
    {
        if (!$this->nodoActual) {
            return collect();
        }

        $respuestas = $this->nodoActual->obtenerRespuestasDisponibles($this->variables ?? []);
        
        // Filtrar respuestas según el rol del usuario
        return $respuestas->filter(function($respuesta) use ($rolId) {
            // Verificar si la respuesta está disponible para este rol
            $condicionesRol = $respuesta->condiciones['rol'] ?? null;
            
            if (!$condicionesRol) {
                return true; // Sin restricciones de rol
            }

            return $this->evaluarCondicionRol($condicionesRol, $rolId);
        });
    }

    private function evaluarCondicionRol($condicionesRol, $rolId)
    {
        $operador = $condicionesRol['operador'] ?? '=';
        $roles = $condicionesRol['roles'] ?? [];

        switch ($operador) {
            case '=':
            case 'in':
                return in_array($rolId, $roles);
            case '!=':
            case 'not_in':
                return !in_array($rolId, $roles);
            default:
                return true;
        }
    }

    public function actualizarProgreso()
    {
        $totalNodos = $this->dialogo->nodos()->count();
        $nodosVisitados = $this->decisiones()->distinct('nodo_dialogo_id')->count();
        
        $progreso = [
            'nodos_visitados' => $nodosVisitados,
            'total_nodos' => $totalNodos,
            'porcentaje' => $totalNodos > 0 ? round(($nodosVisitados / $totalNodos) * 100, 2) : 0,
            'tiempo_transcurrido' => $this->tiempo_transcurrido,
        ];

        // Guardar progreso en la configuración
        $configuracion = $this->configuracion ?? [];
        $configuracion['progreso'] = $progreso;
        $this->update(['configuracion' => $configuracion]);
    }

    public function obtenerHistorialDecisiones()
    {
        return $this->decisiones()
            ->with(['usuario', 'rol', 'nodoDialogo', 'respuesta'])
            ->orderBy('fecha_decision')
            ->get();
    }

    public function obtenerEstadisticasPorRol()
    {
        return $this->decisiones()
            ->selectRaw('rol_id, COUNT(*) as total_decisiones, AVG(tiempo_respuesta) as tiempo_promedio')
            ->with('rol')
            ->groupBy('rol_id')
            ->get();
    }

    public function obtenerFlujoCompleto()
    {
        $historial = $this->obtenerHistorialDecisiones();
        
        return [
            'sesion_dialogo' => $this,
            'historial' => $historial,
            'estadisticas' => $this->obtenerEstadisticasPorRol(),
            'progreso' => $this->progreso,
            'variables_finales' => $this->variables,
        ];
    }
}