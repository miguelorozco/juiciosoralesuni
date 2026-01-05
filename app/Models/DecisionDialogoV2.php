<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DecisionDialogoV2 extends Model
{
    use HasFactory;

    protected $table = 'decisiones_dialogo_v2';

    protected $fillable = [
        'sesion_dialogo_id',
        'nodo_dialogo_id',
        'respuesta_id',
        'usuario_id',
        'rol_id',
        'texto_respuesta',
        'puntuacion_obtenida',
        'calificacion_profesor',
        'notas_profesor',
        'evaluado_por',
        'fecha_evaluacion',
        'estado_evaluacion',
        'justificacion_estudiante',
        'retroalimentacion',
        'audio_mp3',
        'audio_duracion',
        'audio_grabado_en',
        'audio_procesado',
        'tiempo_respuesta',
        'fue_opcion_por_defecto',
        'usuario_registrado',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'puntuacion_obtenida' => 'integer',
            'calificacion_profesor' => 'integer',
            'tiempo_respuesta' => 'integer',
            'audio_duracion' => 'integer',
            'fecha_evaluacion' => 'datetime',
            'audio_grabado_en' => 'datetime',
            'fue_opcion_por_defecto' => 'boolean',
            'usuario_registrado' => 'boolean',
            'audio_procesado' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function sesionDialogo()
    {
        return $this->belongsTo(SesionDialogoV2::class, 'sesion_dialogo_id');
    }

    public function nodoDialogo()
    {
        return $this->belongsTo(NodoDialogoV2::class, 'nodo_dialogo_id');
    }

    public function respuesta()
    {
        return $this->belongsTo(RespuestaDialogoV2::class, 'respuesta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function rol()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    /**
     * Scopes
     */
    public function scopeDeSesionDialogo($query, $sesionDialogoId)
    {
        return $query->where('sesion_dialogo_id', $sesionDialogoId);
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

    public function scopePendientesEvaluacion($query)
    {
        return $query->where('estado_evaluacion', 'pendiente');
    }

    public function scopeEvaluadas($query)
    {
        return $query->where('estado_evaluacion', 'evaluado');
    }

    public function scopeRevisadas($query)
    {
        return $query->where('estado_evaluacion', 'revisado');
    }

    public function scopeConAudio($query)
    {
        return $query->whereNotNull('audio_mp3');
    }

    public function scopeDeUsuariosRegistrados($query)
    {
        return $query->where('usuario_registrado', true);
    }

    public function scopeDeUsuariosNoRegistrados($query)
    {
        return $query->where('usuario_registrado', false);
    }

    public function scopeOpcionesPorDefecto($query)
    {
        return $query->where('fue_opcion_por_defecto', true);
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

    public function getTieneAudioAttribute()
    {
        return !empty($this->audio_mp3);
    }

    public function getAudioUrlAttribute()
    {
        if (!$this->audio_mp3) {
            return null;
        }

        return Storage::url($this->audio_mp3);
    }

    public function getEstaEvaluadaAttribute()
    {
        return $this->estado_evaluacion !== 'pendiente';
    }

    public function getTieneCalificacionProfesorAttribute()
    {
        return $this->calificacion_profesor !== null;
    }

    public function getCalificacionFinalAttribute()
    {
        // Si hay calificación del profesor, usar esa; si no, usar puntuación obtenida
        return $this->calificacion_profesor ?? $this->puntuacion_obtenida;
    }

    /**
     * Métodos para evaluación
     */
    public function evaluar($calificacion, $notas, $retroalimentacion = null, $evaluadorId = null)
    {
        return $this->update([
            'calificacion_profesor' => $calificacion,
            'notas_profesor' => $notas,
            'retroalimentacion' => $retroalimentacion,
            'evaluado_por' => $evaluadorId ?? auth()->id(),
            'fecha_evaluacion' => now(),
            'estado_evaluacion' => 'evaluado',
        ]);
    }

    public function marcarComoRevisado()
    {
        return $this->update(['estado_evaluacion' => 'revisado']);
    }

    public function agregarJustificacion($justificacion)
    {
        return $this->update(['justificacion_estudiante' => $justificacion]);
    }

    /**
     * Métodos para audio
     */
    public function guardarAudio($rutaArchivo, $duracion)
    {
        return $this->update([
            'audio_mp3' => $rutaArchivo,
            'audio_duracion' => $duracion,
            'audio_grabado_en' => now(),
            'audio_procesado' => false,
        ]);
    }

    public function marcarAudioComoProcesado()
    {
        return $this->update(['audio_procesado' => true]);
    }

    /**
     * Métodos útiles
     */
    public function calcularPuntuacion()
    {
        $puntuacion = $this->puntuacion_obtenida;
        
        // Aplicar modificadores basados en tiempo de respuesta
        if ($this->tiempo_respuesta) {
            if ($this->es_decision_rapida) {
                $puntuacion = (int)($puntuacion * 1.2); // Bonus por decisión rápida
            } elseif ($this->es_decision_lenta) {
                $puntuacion = (int)($puntuacion * 0.8); // Penalización por decisión lenta
            }
        }

        // Aplicar modificadores basados en metadata
        if ($this->metadata) {
            $puntuacion = $this->aplicarModificadoresMetadata($puntuacion);
        }

        return $puntuacion;
    }

    private function aplicarModificadoresMetadata($puntuacion)
    {
        $modificadores = $this->metadata['modificadores_puntuacion'] ?? [];
        
        foreach ($modificadores as $modificador) {
            $tipo = $modificador['tipo'] ?? 'multiplicar';
            $valor = $modificador['valor'] ?? 1;

            switch ($tipo) {
                case 'multiplicar':
                    $puntuacion = (int)($puntuacion * $valor);
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

    public function obtenerEstadisticas()
    {
        return [
            'tiempo_respuesta' => $this->tiempo_respuesta,
            'tiempo_formateado' => $this->tiempo_respuesta_formateado,
            'puntuacion' => $this->puntuacion_obtenida,
            'calificacion_profesor' => $this->calificacion_profesor,
            'calificacion_final' => $this->calificacion_final,
            'es_rapida' => $this->es_decision_rapida,
            'es_lenta' => $this->es_decision_lenta,
            'tiene_audio' => $this->tiene_audio,
            'audio_url' => $this->audio_url,
            'estado_evaluacion' => $this->estado_evaluacion,
            'fecha' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }

    public function obtenerContexto()
    {
        return [
            'usuario' => $this->usuario ? $this->usuario->name : 'Usuario no registrado',
            'rol' => $this->rol ? $this->rol->nombre : null,
            'nodo' => $this->nodoDialogo ? $this->nodoDialogo->titulo : null,
            'respuesta' => $this->texto_respuesta,
            'tiempo' => $this->tiempo_respuesta_formateado,
            'puntuacion' => $this->puntuacion_obtenida,
            'calificacion_profesor' => $this->calificacion_profesor,
            'estado_evaluacion' => $this->estado_evaluacion,
        ];
    }

    /**
     * Métodos estáticos para estadísticas
     */
    public static function obtenerEstadisticasGenerales($sesionDialogoId = null)
    {
        $query = static::query();
        
        if ($sesionDialogoId) {
            $query->where('sesion_dialogo_id', $sesionDialogoId);
        }

        return [
            'total_decisiones' => $query->count(),
            'tiempo_promedio' => $query->avg('tiempo_respuesta'),
            'puntuacion_promedio' => $query->avg('puntuacion_obtenida'),
            'calificacion_promedio' => $query->whereNotNull('calificacion_profesor')->avg('calificacion_profesor'),
            'decisiones_rapidas' => $query->where('tiempo_respuesta', '<', 30)->count(),
            'decisiones_lentas' => $query->where('tiempo_respuesta', '>', 300)->count(),
            'con_audio' => $query->whereNotNull('audio_mp3')->count(),
            'pendientes_evaluacion' => $query->where('estado_evaluacion', 'pendiente')->count(),
            'evaluadas' => $query->where('estado_evaluacion', 'evaluado')->count(),
        ];
    }

    public static function obtenerEstadisticasPorRol($sesionDialogoId = null)
    {
        $query = static::query();
        
        if ($sesionDialogoId) {
            $query->where('sesion_dialogo_id', $sesionDialogoId);
        }

        return $query->selectRaw('
                rol_id,
                COUNT(*) as total_decisiones,
                AVG(tiempo_respuesta) as tiempo_promedio,
                AVG(puntuacion_obtenida) as puntuacion_promedio,
                AVG(calificacion_profesor) as calificacion_promedio
            ')
            ->with('rol')
            ->groupBy('rol_id')
            ->get();
    }

    public static function obtenerEstadisticasPorUsuario($sesionDialogoId = null)
    {
        $query = static::query();
        
        if ($sesionDialogoId) {
            $query->where('sesion_dialogo_id', $sesionDialogoId);
        }

        return $query->selectRaw('
                usuario_id,
                COUNT(*) as total_decisiones,
                AVG(tiempo_respuesta) as tiempo_promedio,
                AVG(puntuacion_obtenida) as puntuacion_promedio,
                AVG(calificacion_profesor) as calificacion_promedio
            ')
            ->with('usuario')
            ->whereNotNull('usuario_id')
            ->groupBy('usuario_id')
            ->get();
    }
}
