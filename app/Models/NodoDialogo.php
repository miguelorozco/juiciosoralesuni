<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodoDialogo extends Model
{
    use HasFactory;

    protected $fillable = [
        'dialogo_id',
        'rol_id',
        'titulo',
        'contenido',
        'instrucciones',
        'orden',
        'tipo',
        'condiciones',
        'metadata',
        'es_inicial',
        'es_final',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'condiciones' => 'array',
            'metadata' => 'array',
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
        ];
    }

    /**
     * Relaciones
     */
    public function dialogo()
    {
        return $this->belongsTo(Dialogo::class);
    }

    public function rol()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function respuestas()
    {
        return $this->hasMany(RespuestaDialogo::class, 'nodo_padre_id')->orderBy('orden');
    }

    public function respuestasEntrantes()
    {
        return $this->hasMany(RespuestaDialogo::class, 'nodo_siguiente_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionSesion::class, 'nodo_dialogo_id');
    }

    /**
     * Scopes
     */
    public function scopeDelDialogo($query, $dialogoId)
    {
        return $query->where('dialogo_id', $dialogoId);
    }

    public function scopeDelRol($query, $rolId)
    {
        return $query->where('rol_id', $rolId);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeIniciales($query)
    {
        return $query->where('es_inicial', true);
    }

    public function scopeFinales($query)
    {
        return $query->where('es_final', true);
    }

    public function scopeDeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Accessors
     */
    public function getTieneRespuestasAttribute()
    {
        return $this->respuestas()->count() > 0;
    }

    public function getEsNodoDecisionAttribute()
    {
        return $this->tipo === 'decision' && $this->tiene_respuestas;
    }

    public function getTotalRespuestasAttribute()
    {
        return $this->respuestas()->count();
    }

    /**
     * Accessors para posiciones
     */
    public function getPosicionAttribute()
    {
        return $this->metadata['posicion'] ?? ['x' => 0, 'y' => 0];
    }

    public function getXAttribute()
    {
        return $this->posicion['x'] ?? 0;
    }

    public function getYAttribute()
    {
        return $this->posicion['y'] ?? 0;
    }

    /**
     * Métodos para manejo de posiciones
     */
    public function actualizarPosicion($x, $y)
    {
        $metadata = $this->metadata ?? [];
        $metadata['posicion'] = ['x' => $x, 'y' => $y];
        $this->update(['metadata' => $metadata]);
    }

    public function obtenerConexionesSalientes()
    {
        return $this->respuestas()->with('nodoSiguiente')->get();
    }

    public function obtenerConexionesEntrantes()
    {
        return $this->respuestasEntrantes()->with('nodoPadre')->get();
    }

    public function obtenerTodosLosNodosConectados()
    {
        $nodosConectados = collect();
        
        // Nodos a los que se puede llegar desde este nodo
        $nodosSalientes = $this->respuestas()->with('nodoSiguiente')->get()
            ->pluck('nodoSiguiente')->filter();
        
        // Nodos desde los que se puede llegar a este nodo
        $nodosEntrantes = $this->respuestasEntrantes()->with('nodoPadre')->get()
            ->pluck('nodoPadre')->filter();
        
        return $nodosConectados->merge($nodosSalientes)->merge($nodosEntrantes)->unique('id');
    }

    /**
     * Métodos útiles
     */
    public function agregarRespuesta($datos)
    {
        $orden = $this->respuestas()->max('orden') + 1;
        
        return $this->respuestas()->create(array_merge($datos, [
            'orden' => $orden
        ]));
    }

    public function removerRespuesta($respuestaId)
    {
        return $this->respuestas()->where('id', $respuestaId)->delete();
    }

    public function obtenerRespuestasDisponibles($variables = [])
    {
        $respuestas = $this->respuestas()->where('activo', true)->get();
        
        // Filtrar respuestas según condiciones
        return $respuestas->filter(function($respuesta) use ($variables) {
            return $this->evaluarCondiciones($respuesta->condiciones, $variables);
        });
    }

    public function evaluarCondiciones($condiciones, $variables)
    {
        if (empty($condiciones)) {
            return true;
        }

        // Implementar lógica de evaluación de condiciones
        // Por ejemplo: verificar variables de sesión, decisiones anteriores, etc.
        foreach ($condiciones as $condicion) {
            if (!$this->evaluarCondicion($condicion, $variables)) {
                return false;
            }
        }

        return true;
    }

    private function evaluarCondicion($condicion, $variables)
    {
        // Implementar lógica específica de evaluación
        // Ejemplo: verificar si una variable tiene un valor específico
        $variable = $condicion['variable'] ?? null;
        $operador = $condicion['operador'] ?? '=';
        $valor = $condicion['valor'] ?? null;

        if (!$variable || !isset($variables[$variable])) {
            return false;
        }

        $valorVariable = $variables[$variable];

        switch ($operador) {
            case '=':
                return $valorVariable == $valor;
            case '!=':
                return $valorVariable != $valor;
            case '>':
                return $valorVariable > $valor;
            case '<':
                return $valorVariable < $valor;
            case '>=':
                return $valorVariable >= $valor;
            case '<=':
                return $valorVariable <= $valor;
            case 'in':
                return in_array($valorVariable, $valor);
            case 'not_in':
                return !in_array($valorVariable, $valor);
            default:
                return false;
        }
    }

    public function marcarComoInicial()
    {
        // Desmarcar otros nodos iniciales del mismo diálogo
        $this->dialogo->nodos()->update(['es_inicial' => false]);
        
        return $this->update(['es_inicial' => true]);
    }

    public function marcarComoFinal()
    {
        return $this->update(['es_final' => true]);
    }
}
