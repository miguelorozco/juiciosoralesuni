<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SesionDialogoV2 extends Model
{
    use HasFactory;

    protected $table = 'sesiones_dialogos_v2';

    protected $fillable = [
        'sesion_id',
        'dialogo_id',
        'nodo_actual_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'variables',
        'configuracion',
        'historial_nodos',
        'audio_mp3_completo',
        'audio_duracion_completo',
        'audio_grabado_en',
        'audio_procesado',
        'audio_habilitado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'audio_grabado_en' => 'datetime',
            'variables' => 'array',
            'configuracion' => 'array',
            'historial_nodos' => 'array',
            'audio_procesado' => 'boolean',
            'audio_habilitado' => 'boolean',
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
        return $this->belongsTo(DialogoV2::class, 'dialogo_id');
    }

    public function nodoActual()
    {
        return $this->belongsTo(NodoDialogoV2::class, 'nodo_actual_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionDialogoV2::class, 'sesion_dialogo_id');
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
        return $query->where('estado', 'finalizado');
    }

    public function scopeDeSesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }

    public function scopeConAudioHabilitado($query)
    {
        return $query->where('audio_habilitado', true);
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
        return $this->fecha_inicio->diffInSeconds($fechaFin);
    }

    public function getEstaActivaAttribute()
    {
        return in_array($this->estado, ['iniciado', 'en_curso', 'pausado']);
    }

    public function getPuedeAvanzarAttribute()
    {
        return $this->estado === 'en_curso' && $this->nodoActual;
    }

    public function getAudioUrlAttribute()
    {
        if (!$this->audio_mp3_completo) {
            return null;
        }

        return Storage::url($this->audio_mp3_completo);
    }

    /**
     * Métodos para gestionar historial
     */
    public function agregarAlHistorial($nodoId, $usuarioId = null, $rolId = null, $respuestaId = null, $tiempoEnNodo = null)
    {
        $historial = $this->historial_nodos ?? [];
        
        $historial[] = [
            'nodo_id' => $nodoId,
            'fecha' => now()->toIso8601String(),
            'usuario_id' => $usuarioId,
            'rol_id' => $rolId,
            'tiempo_en_nodo' => $tiempoEnNodo,
            'respuesta_seleccionada_id' => $respuestaId,
        ];

        $this->update(['historial_nodos' => $historial]);
    }

    public function obtenerHistorial()
    {
        return $this->historial_nodos ?? [];
    }

    public function obtenerNodosVisitados()
    {
        $historial = $this->historial_nodos ?? [];
        return collect($historial)->pluck('nodo_id')->unique()->values();
    }

    public function obtenerTiempoTotalEnNodos()
    {
        $historial = $this->historial_nodos ?? [];
        return collect($historial)->sum('tiempo_en_nodo');
    }

    /**
     * Métodos para gestionar variables
     */
    public function obtenerVariable($nombre, $default = null)
    {
        $variables = $this->variables ?? [];
        return $variables[$nombre] ?? $default;
    }

    public function establecerVariable($nombre, $valor)
    {
        $variables = $this->variables ?? [];
        $variables[$nombre] = $valor;
        $this->update(['variables' => $variables]);
    }

    public function incrementarVariable($nombre, $incremento = 1)
    {
        $variables = $this->variables ?? [];
        $variables[$nombre] = ($variables[$nombre] ?? 0) + $incremento;
        $this->update(['variables' => $variables]);
    }

    public function aplicarConsecuencias($consecuencias)
    {
        if (empty($consecuencias)) {
            return;
        }

        $variables = $this->variables ?? [];

        // Aplicar cambios de variables
        if (isset($consecuencias['variables'])) {
            foreach ($consecuencias['variables'] as $variable => $cambio) {
                $operador = $cambio['operator'] ?? '=';
                $valor = $cambio['value'] ?? null;

                switch ($operador) {
                    case '=':
                        $variables[$variable] = $valor;
                        break;
                    case '+=':
                        $variables[$variable] = ($variables[$variable] ?? 0) + $valor;
                        break;
                    case '-=':
                        $variables[$variable] = ($variables[$variable] ?? 0) - $valor;
                        break;
                    case '++':
                        $variables[$variable] = ($variables[$variable] ?? 0) + 1;
                        break;
                    case '--':
                        $variables[$variable] = ($variables[$variable] ?? 0) - 1;
                        break;
                }
            }
        }

        $this->update(['variables' => $variables]);
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

        $variablesIniciales = $this->configuracion['variables_iniciales'] ?? [];
        if (empty($variablesIniciales)) {
            $variablesIniciales = $this->dialogo->configuracion['variables_iniciales'] ?? [];
        }

        return $this->update([
            'estado' => 'en_curso',
            'nodo_actual_id' => $nodoInicial->id,
            'fecha_inicio' => now(),
            'variables' => $variablesIniciales,
            'historial_nodos' => [],
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
            'estado' => 'finalizado',
            'fecha_fin' => now(),
        ]);
    }

    public function avanzarANodo($nodoId, $usuarioId = null, $rolId = null, $tiempoEnNodo = null)
    {
        $nodo = NodoDialogoV2::find($nodoId);
        
        if (!$nodo || $nodo->dialogo_id !== $this->dialogo_id) {
            return false;
        }

        // Agregar al historial
        if ($this->nodo_actual_id) {
            $this->agregarAlHistorial(
                $this->nodo_actual_id,
                $usuarioId,
                $rolId,
                null,
                $tiempoEnNodo
            );
        }

        $this->update(['nodo_actual_id' => $nodoId]);
        $this->actualizarProgreso();
        
        return true;
    }

    public function procesarDecision($usuarioId, $rolId, $respuestaId, $decisionTexto = null, $tiempoRespuesta = null, $audioMp3 = null)
    {
        $respuesta = RespuestaDialogoV2::find($respuestaId);
        
        if (!$respuesta || $respuesta->nodo_padre_id !== $this->nodo_actual_id) {
            return false;
        }

        // Crear registro de decisión
        $decision = DecisionDialogoV2::create([
            'sesion_dialogo_id' => $this->id,
            'nodo_dialogo_id' => $this->nodo_actual_id,
            'respuesta_id' => $respuestaId,
            'usuario_id' => $usuarioId,
            'rol_id' => $rolId,
            'texto_respuesta' => $respuesta->texto,
            'puntuacion_obtenida' => $respuesta->puntuacion,
            'tiempo_respuesta' => $tiempoRespuesta,
            'fue_opcion_por_defecto' => $respuesta->es_opcion_por_defecto,
            'usuario_registrado' => $usuarioId !== null,
            'audio_mp3' => $audioMp3,
            'audio_grabado_en' => $audioMp3 ? now() : null,
        ]);

        // Aplicar consecuencias de la respuesta
        if ($respuesta->tiene_consecuencias) {
            $this->aplicarConsecuencias($respuesta->consecuencias);
        }

        // Aplicar consecuencias del nodo
        if ($this->nodoActual && $this->nodoActual->consecuencias) {
            $this->aplicarConsecuencias($this->nodoActual->consecuencias);
        }

        // Agregar al historial
        $this->agregarAlHistorial(
            $this->nodo_actual_id,
            $usuarioId,
            $rolId,
            $respuestaId,
            $tiempoRespuesta
        );

        // Avanzar al siguiente nodo si existe
        if ($respuesta->nodo_siguiente_id) {
            $this->avanzarANodo($respuesta->nodo_siguiente_id, $usuarioId, $rolId, $tiempoRespuesta);
        } else {
            // Si no hay siguiente nodo, finalizar el diálogo
            $this->finalizar();
        }

        return $decision;
    }

    public function obtenerRespuestasDisponibles($usuarioId = null, $rolId = null, $usuarioRegistrado = true)
    {
        if (!$this->nodoActual) {
            return collect();
        }

        $variables = $this->variables ?? [];
        
        return $this->nodoActual->obtenerRespuestasDisponibles(
            $usuarioRegistrado,
            $rolId,
            $variables
        );
    }

    public function actualizarProgreso()
    {
        $totalNodos = $this->dialogo->nodos()->count();
        $nodosVisitados = $this->obtenerNodosVisitados()->count();
        
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
            ->with(['usuario', 'rol', 'nodoDialogo', 'respuesta', 'evaluador'])
            ->orderBy('created_at')
            ->get();
    }

    public function obtenerEstadisticasPorRol()
    {
        return $this->decisiones()
            ->selectRaw('rol_id, COUNT(*) as total_decisiones, AVG(tiempo_respuesta) as tiempo_promedio, AVG(puntuacion_obtenida) as puntuacion_promedio')
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
            'progreso' => $this->configuracion['progreso'] ?? null,
            'variables_finales' => $this->variables,
            'audio_url' => $this->audio_url,
        ];
    }

    /**
     * Métodos para audio
     */
    public function habilitarGrabacion()
    {
        return $this->update([
            'audio_habilitado' => true,
            'audio_grabado_en' => now(),
        ]);
    }

    public function deshabilitarGrabacion()
    {
        return $this->update(['audio_habilitado' => false]);
    }

    public function guardarAudioCompleto($rutaArchivo, $duracion)
    {
        return $this->update([
            'audio_mp3_completo' => $rutaArchivo,
            'audio_duracion_completo' => $duracion,
            'audio_procesado' => false,
        ]);
    }

    public function marcarAudioComoProcesado()
    {
        return $this->update(['audio_procesado' => true]);
    }
}
