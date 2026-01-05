<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaDialogoV2 extends Model
{
    use HasFactory;

    protected $table = 'respuestas_dialogo_v2';

    protected $fillable = [
        'nodo_padre_id',
        'nodo_siguiente_id',
        'texto',
        'descripcion',
        'orden',
        'puntuacion',
        'color',
        'condiciones',
        'consecuencias',
        'requiere_usuario_registrado',
        'es_opcion_por_defecto',
        'requiere_rol',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'puntuacion' => 'integer',
            'condiciones' => 'array',
            'consecuencias' => 'array',
            'requiere_rol' => 'array',
            'requiere_usuario_registrado' => 'boolean',
            'es_opcion_por_defecto' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Relaciones
     */
    public function nodoPadre()
    {
        return $this->belongsTo(NodoDialogoV2::class, 'nodo_padre_id');
    }

    public function nodoSiguiente()
    {
        return $this->belongsTo(NodoDialogoV2::class, 'nodo_siguiente_id');
    }

    public function decisiones()
    {
        return $this->hasMany(DecisionDialogoV2::class, 'respuesta_id');
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

    public function scopeParaUsuariosRegistrados($query)
    {
        return $query->where('requiere_usuario_registrado', true);
    }

    public function scopeParaUsuariosNoRegistrados($query)
    {
        return $query->where(function($q) {
            $q->where('requiere_usuario_registrado', false)
              ->orWhere('es_opcion_por_defecto', true);
        });
    }

    public function scopeOpcionesPorDefecto($query)
    {
        return $query->where('es_opcion_por_defecto', true);
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

    public function getEsDisponibleParaNoRegistradosAttribute()
    {
        return !$this->requiere_usuario_registrado || $this->es_opcion_por_defecto;
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

        // Si tiene userScript, necesitaríamos un evaluador Lua
        if (isset($this->consecuencias['userScript']) && !empty($this->consecuencias['userScript'])) {
            // Por ahora, solo aplicamos variables
            // En el futuro, se podría evaluar el userScript
        }

        // Aplicar cambios de variables
        if (isset($this->consecuencias['variables'])) {
            foreach ($this->consecuencias['variables'] as $variable => $cambio) {
                $operador = $cambio['operator'] ?? '=';
                $valor = $cambio['value'] ?? null;

                switch ($operador) {
                    case '=':
                        $nuevasVariables[$variable] = $valor;
                        break;
                    case '+=':
                        $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) + $valor;
                        break;
                    case '-=':
                        $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) - $valor;
                        break;
                    case '++':
                        $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) + 1;
                        break;
                    case '--':
                        $nuevasVariables[$variable] = ($nuevasVariables[$variable] ?? 0) - 1;
                        break;
                }
            }
        }

        return $nuevasVariables;
    }

    public function evaluarCondiciones($variables = [], $usuarioRegistrado = true, $rolId = null)
    {
        if (empty($this->condiciones)) {
            return true;
        }

        // Verificar si requiere usuario registrado
        if ($this->requiere_usuario_registrado && !$usuarioRegistrado) {
            return false;
        }

        // Verificar rol requerido
        if (!empty($this->requiere_rol) && $rolId) {
            if (!in_array($rolId, $this->requiere_rol)) {
                return false;
            }
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
            case 'exists':
                return isset($variables[$variable]);
            case 'not_exists':
                return !isset($variables[$variable]);
            default:
                return false;
        }
    }

    /**
     * Método para obtener opción por defecto de un nodo
     */
    public static function obtenerOpcionPorDefecto($nodoId)
    {
        return static::where('nodo_padre_id', $nodoId)
            ->where('es_opcion_por_defecto', true)
            ->where('activo', true)
            ->first();
    }

    /**
     * Filtrar respuestas disponibles para un usuario
     */
    public static function disponiblesParaUsuario($nodoId, $usuarioRegistrado = true, $rolId = null, $variables = [])
    {
        $query = static::where('nodo_padre_id', $nodoId)
            ->where('activo', true);

        // Si el usuario no está registrado, solo mostrar opciones disponibles
        if (!$usuarioRegistrado) {
            $query->where(function($q) {
                $q->where('requiere_usuario_registrado', false)
                  ->orWhere('es_opcion_por_defecto', true);
            });
        }

        $respuestas = $query->get();

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
        return $respuestas->filter(function($respuesta) use ($variables, $usuarioRegistrado, $rolId) {
            return $respuesta->evaluarCondiciones($variables, $usuarioRegistrado, $rolId);
        });
    }

    public function obtenerEstadisticas($sesionDialogoId = null)
    {
        $query = $this->decisiones();
        
        if ($sesionDialogoId) {
            $query->where('sesion_dialogo_id', $sesionDialogoId);
        }

        $total = $query->count();
        $conAudio = $query->whereNotNull('audio_mp3')->count();

        return [
            'total_selecciones' => $total,
            'con_audio' => $conAudio,
            'porcentaje_seleccion' => $this->calcularPorcentajeSeleccion($sesionDialogoId),
            'tiempo_promedio' => $query->avg('tiempo_respuesta'),
            'puntuacion_promedio' => $query->avg('puntuacion_obtenida'),
        ];
    }

    private function calcularPorcentajeSeleccion($sesionDialogoId = null)
    {
        $totalDecisiones = DecisionDialogoV2::where('nodo_dialogo_id', $this->nodo_padre_id);
        
        if ($sesionDialogoId) {
            $totalDecisiones->where('sesion_dialogo_id', $sesionDialogoId);
        }

        $totalDecisiones = $totalDecisiones->count();
        
        if ($totalDecisiones === 0) {
            return 0;
        }

        $selecciones = $this->decisiones();
        if ($sesionDialogoId) {
            $selecciones->where('sesion_dialogo_id', $sesionDialogoId);
        }

        return round(($selecciones->count() / $totalDecisiones) * 100, 2);
    }
}
