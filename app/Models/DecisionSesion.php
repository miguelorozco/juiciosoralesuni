<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecisionSesion extends Model
{
    use HasFactory;

    protected $table = 'decisiones_sesion';

    protected $fillable = [
        'sesion_id',
        'usuario_id',
        'rol_id',
        'nodo_dialogo_id',
        'respuesta_id',
        'decision_texto',
        'metadata',
        'tiempo_respuesta',
        'fecha_decision',
    ];

    protected function casts(): array
    {
        return [
            'tiempo_respuesta' => 'integer',
            'fecha_decision' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function sesion()
    {
        return $this->belongsTo(SesionJuicio::class, 'sesion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function rol()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function nodoDialogo()
    {
        return $this->belongsTo(NodoDialogo::class, 'nodo_dialogo_id');
    }

    public function respuesta()
    {
        return $this->belongsTo(RespuestaDialogo::class, 'respuesta_id');
    }

    /**
     * Scopes
     */
    public function scopeDeSesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }

    public function scopeDeUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeDeRol($query, $rolId)
    {
        return $query->where('rol_id', $rolId);
    }

    public function scopeDeNodo($query, $nodoId)
    {
        return $query->where('nodo_dialogo_id', $nodoId);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        $query->where('fecha_decision', '>=', $fechaInicio);
        
        if ($fechaFin) {
            $query->where('fecha_decision', '<=', $fechaFin);
        }
        
        return $query;
    }

    public function scopeConPuntuacion($query)
    {
        return $query->whereNotNull('puntuacion_obtenida');
    }

    /**
     * Accessors
     */
    public function getTiempoRespuestaFormateadoAttribute()
    {
        if (!$this->tiempo_respuesta) {
            return 'N/A';
        }

        $minutos = floor($this->tiempo_respuesta / 60);
        $segundos = $this->tiempo_respuesta % 60;

        if ($minutos > 0) {
            return "{$minutos}m {$segundos}s";
        }

        return "{$segundos}s";
    }

    public function getEsDecisionRapidaAttribute()
    {
        return $this->tiempo_respuesta && $this->tiempo_respuesta < 30; // Menos de 30 segundos
    }

    public function getEsDecisionLentaAttribute()
    {
        return $this->tiempo_respuesta && $this->tiempo_respuesta > 300; // Más de 5 minutos
    }

    public function getTieneTextoAdicionalAttribute()
    {
        return !empty($this->decision_texto);
    }

    /**
     * Métodos útiles
     */
    public function calcularPuntuacion()
    {
        $puntuacion = $this->respuesta->puntuacion ?? 0;
        
        // Aplicar modificadores basados en tiempo de respuesta
        if ($this->tiempo_respuesta) {
            if ($this->es_decision_rapida) {
                $puntuacion *= 1.2; // Bonus por decisión rápida
            } elseif ($this->es_decision_lenta) {
                $puntuacion *= 0.8; // Penalización por decisión lenta
            }
        }

        // Aplicar modificadores basados en metadata
        if ($this->metadata) {
            $puntuacion = $this->aplicarModificadoresMetadata($puntuacion);
        }

        return round($puntuacion);
    }

    private function aplicarModificadoresMetadata($puntuacion)
    {
        $modificadores = $this->metadata['modificadores_puntuacion'] ?? [];
        
        foreach ($modificadores as $modificador) {
            $tipo = $modificador['tipo'] ?? 'multiplicar';
            $valor = $modificador['valor'] ?? 1;

            switch ($tipo) {
                case 'multiplicar':
                    $puntuacion *= $valor;
                    break;
                case 'sumar':
                    $puntuacion += $valor;
                    break;
                case 'restar':
                    $puntuacion -= $valor;
                    break;
            }
        }

        return $puntuacion;
    }

    public function actualizarPuntuacion()
    {
        $puntuacion = $this->calcularPuntuacion();
        
        $metadata = $this->metadata ?? [];
        $metadata['puntuacion_obtenida'] = $puntuacion;
        $this->update(['metadata' => $metadata]);
        
        return $puntuacion;
    }

    public function obtenerEstadisticas()
    {
        return [
            'tiempo_respuesta' => $this->tiempo_respuesta,
            'tiempo_formateado' => $this->tiempo_respuesta_formateado,
            'puntuacion' => $this->metadata['puntuacion_obtenida'] ?? 0,
            'es_rapida' => $this->es_decision_rapida,
            'es_lenta' => $this->es_decision_lenta,
            'tiene_texto' => $this->tiene_texto_adicional,
            'fecha' => $this->fecha_decision->format('d/m/Y H:i:s'),
        ];
    }

    public function obtenerContexto()
    {
        return [
            'usuario' => $this->usuario->name,
            'rol' => $this->rol->nombre,
            'nodo' => $this->nodoDialogo->titulo,
            'respuesta' => $this->respuesta->texto,
            'decision_texto' => $this->decision_texto,
            'tiempo' => $this->tiempo_respuesta_formateado,
            'puntuacion' => $this->metadata['puntuacion_obtenida'] ?? 0,
        ];
    }

    /**
     * Métodos estáticos para estadísticas
     */
    public static function obtenerEstadisticasGenerales($sesionId = null)
    {
        $query = static::query();
        
        if ($sesionId) {
            $query->where('sesion_id', $sesionId);
        }

        return [
            'total_decisiones' => $query->count(),
            'tiempo_promedio' => $query->avg('tiempo_respuesta'),
            'puntuacion_promedio' => 0, // Se calculará dinámicamente
            'decisiones_rapidas' => $query->where('tiempo_respuesta', '<', 30)->count(),
            'decisiones_lentas' => $query->where('tiempo_respuesta', '>', 300)->count(),
        ];
    }

    public static function obtenerEstadisticasPorRol($sesionId = null)
    {
        $query = static::query();
        
        if ($sesionId) {
            $query->where('sesion_id', $sesionId);
        }

        return $query->selectRaw('
                rol_id,
                COUNT(*) as total_decisiones,
                AVG(tiempo_respuesta) as tiempo_promedio,
                MIN(tiempo_respuesta) as tiempo_minimo,
                MAX(tiempo_respuesta) as tiempo_maximo
            ')
            ->with('rol')
            ->groupBy('rol_id')
            ->get();
    }

    public static function obtenerEstadisticasPorUsuario($sesionId = null)
    {
        $query = static::query();
        
        if ($sesionId) {
            $query->where('sesion_id', $sesionId);
        }

        return $query->selectRaw('
                usuario_id,
                COUNT(*) as total_decisiones,
                AVG(tiempo_respuesta) as tiempo_promedio
            ')
            ->with('usuario')
            ->groupBy('usuario_id')
            ->get();
    }
}