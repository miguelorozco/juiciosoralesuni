<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodoDialogoV2 extends Model
{
    use HasFactory;

    protected $table = 'nodos_dialogo_v2';

    protected $fillable = [
        'dialogo_id',
        'rol_id',
        'conversant_id',
        'titulo',
        'contenido',
        'menu_text',
        'instrucciones',
        'tipo',
        'posicion_x',
        'posicion_y',
        'es_inicial',
        'es_final',
        'condiciones',
        'consecuencias',
        'metadata',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'posicion_x' => 'integer',
            'posicion_y' => 'integer',
            'orden' => 'integer',
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
            'activo' => 'boolean',
            'condiciones' => 'array',
            'consecuencias' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function dialogo()
    {
        return $this->belongsTo(DialogoV2::class, 'dialogo_id');
    }

    public function rol()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function conversant()
    {
        return $this->belongsTo(RolDisponible::class, 'conversant_id');
    }

    public function respuestas()
    {
        return $this->hasMany(RespuestaDialogoV2::class, 'nodo_padre_id')->orderBy('orden');
    }

    public function respuestasEntrantes()
    {
        return $this->hasMany(RespuestaDialogoV2::class, 'nodo_siguiente_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionDialogoV2::class, 'nodo_dialogo_id');
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

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorPosicion($query, $x, $y, $tolerancia = 50)
    {
        return $query->whereBetween('posicion_x', [$x - $tolerancia, $x + $tolerancia])
                     ->whereBetween('posicion_y', [$y - $tolerancia, $y + $tolerancia]);
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

    public function getPosicionAttribute()
    {
        return ['x' => $this->posicion_x, 'y' => $this->posicion_y];
    }

    public function getXAttribute()
    {
        return $this->posicion_x;
    }

    public function getYAttribute()
    {
        return $this->posicion_y;
    }

    public function getTieneSequenceAttribute()
    {
        return isset($this->metadata['sequence']) && !empty($this->metadata['sequence']);
    }

    public function getSequenceAttribute()
    {
        return $this->metadata['sequence'] ?? null;
    }

    public function getUserScriptAttribute()
    {
        return $this->metadata['userScript'] ?? null;
    }

    /**
     * Métodos para manejo de posiciones
     */
    public function actualizarPosicion($x, $y)
    {
        return $this->update([
            'posicion_x' => $x,
            'posicion_y' => $y,
        ]);
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

    public function obtenerRespuestasDisponibles($usuarioRegistrado = true, $rolId = null, $variables = [])
    {
        $respuestas = $this->respuestas()->where('activo', true)->get();
        
        // Filtrar por usuario registrado
        if (!$usuarioRegistrado) {
            $respuestas = $respuestas->filter(function($respuesta) {
                return !$respuesta->requiere_usuario_registrado || $respuesta->es_opcion_por_defecto;
            });
        }
        
        // Filtrar por rol
        if ($rolId) {
            $respuestas = $respuestas->filter(function($respuesta) use ($rolId) {
                if (empty($respuesta->requiere_rol)) {
                    return true;
                }
                return in_array($rolId, $respuesta->requiere_rol);
            });
        }
        
        // Filtrar por condiciones
        return $respuestas->filter(function($respuesta) use ($variables) {
            return $respuesta->evaluarCondiciones($variables);
        });
    }

    public function evaluarCondiciones($variables = [])
    {
        if (empty($this->condiciones)) {
            return true;
        }

        // Si es tipo Lua, necesitaríamos un evaluador Lua
        if (isset($this->condiciones['type']) && $this->condiciones['type'] === 'lua') {
            // Por ahora, retornamos true si no hay expresión o si está vacía
            return empty($this->condiciones['expression']);
        }

        // Evaluar condiciones simples
        $conditions = $this->condiciones['conditions'] ?? [];
        $logic = $this->condiciones['logic'] ?? 'AND';

        if (empty($conditions)) {
            return true;
        }

        $resultados = [];
        foreach ($conditions as $condicion) {
            $resultados[] = $this->evaluarCondicion($condicion, $variables);
        }

        if ($logic === 'OR') {
            return in_array(true, $resultados);
        }

        return !in_array(false, $resultados);
    }

    private function evaluarCondicion($condicion, $variables)
    {
        $variable = $condicion['variable'] ?? null;
        $operador = $condicion['operator'] ?? '==';
        $valor = $condicion['value'] ?? null;

        if (!$variable || !isset($variables[$variable])) {
            return false;
        }

        $valorVariable = $variables[$variable];

        switch ($operador) {
            case '==':
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
                return in_array($valorVariable, (array)$valor);
            case 'not_in':
                return !in_array($valorVariable, (array)$valor);
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

    public function activar()
    {
        return $this->update(['activo' => true]);
    }

    public function desactivar()
    {
        return $this->update(['activo' => false]);
    }
}
