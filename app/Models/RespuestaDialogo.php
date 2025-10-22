<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaDialogo extends Model
{
    use HasFactory;

    protected $table = 'respuestas_dialogo';

    protected $fillable = [
        'nodo_padre_id',
        'nodo_siguiente_id',
        'texto',
        'descripcion',
        'orden',
        'condiciones',
        'consecuencias',
        'puntuacion',
        'color',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'condiciones' => 'array',
            'consecuencias' => 'array',
            'puntuacion' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /**
     * Relaciones
     */
    public function nodoPadre()
    {
        return $this->belongsTo(NodoDialogo::class, 'nodo_padre_id');
    }

    public function nodoSiguiente()
    {
        return $this->belongsTo(NodoDialogo::class, 'nodo_siguiente_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionSesion::class, 'respuesta_id');
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeDelNodo($query, $nodoId)
    {
        return $query->where('nodo_padre_id', $nodoId);
    }

    /**
     * Accessors
     */
    public function getTieneConsecuenciasAttribute()
    {
        return !empty($this->consecuencias);
    }

    public function getEsRespuestaFinalAttribute()
    {
        return $this->nodo_siguiente_id === null;
    }

    /**
     * Métodos útiles
     */
    public function activar()
    {
        return $this->update(['activo' => true]);
    }

    public function desactivar()
    {
        return $this->update(['activo' => false]);
    }

    public function aplicarConsecuencias($variables)
    {
        if (!$this->tiene_consecuencias) {
            return $variables;
        }

        $nuevasVariables = $variables;

        foreach ($this->consecuencias as $consecuencia) {
            $tipo = $consecuencia['tipo'] ?? 'set';
            $variable = $consecuencia['variable'] ?? null;
            $valor = $consecuencia['valor'] ?? null;

            if (!$variable) continue;

            switch ($tipo) {
                case 'set':
                    $nuevasVariables[$variable] = $valor;
                    break;
                case 'increment':
                    $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) + ($valor ?? 1);
                    break;
                case 'decrement':
                    $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) - ($valor ?? 1);
                    break;
                case 'append':
                    if (!isset($nuevasVariables[$variable])) {
                        $nuevasVariables[$variable] = [];
                    }
                    $nuevasVariables[$variable][] = $valor;
                    break;
            }
        }

        return $nuevasVariables;
    }

    public function evaluarCondiciones($variables)
    {
        if (empty($this->condiciones)) {
            return true;
        }

        foreach ($this->condiciones as $condicion) {
            if (!$this->evaluarCondicion($condicion, $variables)) {
                return false;
            }
        }

        return true;
    }

    private function evaluarCondicion($condicion, $variables)
    {
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
            case 'exists':
                return isset($variables[$variable]);
            case 'not_exists':
                return !isset($variables[$variable]);
            default:
                return false;
        }
    }

    public function obtenerEstadisticas($sesionId = null)
    {
        $query = $this->decisiones();
        
        if ($sesionId) {
            $query->where('sesion_id', $sesionId);
        }

        return [
            'total_selecciones' => $query->count(),
            'porcentaje_seleccion' => $this->calcularPorcentajeSeleccion($sesionId),
            'tiempo_promedio' => $query->avg('tiempo_respuesta'),
        ];
    }

    private function calcularPorcentajeSeleccion($sesionId = null)
    {
        $totalDecisiones = DecisionSesion::where('nodo_dialogo_id', $this->nodo_padre_id);
        
        if ($sesionId) {
            $totalDecisiones->where('sesion_id', $sesionId);
        }

        $totalDecisiones = $totalDecisiones->count();
        
        if ($totalDecisiones === 0) {
            return 0;
        }

        $selecciones = $this->decisiones();
        if ($sesionId) {
            $selecciones->where('sesion_id', $sesionId);
        }

        return round(($selecciones->count() / $totalDecisiones) * 100, 2);
    }
}
