<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DialogoV2 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dialogos_v2';

    protected $fillable = [
        'nombre',
        'descripcion',
        'creado_por',
        'plantilla_id',
        'publico',
        'estado',
        'version',
        'configuracion',
        'metadata_unity',
    ];

    protected function casts(): array
    {
        return [
            'publico' => 'boolean',
            'configuracion' => 'array',
            'metadata_unity' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function plantilla()
    {
        return $this->belongsTo(PlantillaSesion::class, 'plantilla_id');
    }

    public function nodos()
    {
        return $this->hasMany(NodoDialogoV2::class, 'dialogo_id')->orderBy('orden');
    }

    public function sesiones()
    {
        return $this->hasMany(SesionDialogoV2::class, 'dialogo_id');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePublicos($query)
    {
        return $query->where('publico', true);
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('creado_por', $userId);
    }

    public function scopeDisponiblesParaUsuario($query, $user)
    {
        return $query->where(function($q) use ($user) {
            $q->where('publico', true)
              ->orWhere('creado_por', $user->id);
        });
    }

    public function scopeBorradores($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeArchivados($query)
    {
        return $query->where('estado', 'archivado');
    }

    /**
     * Accessors
     */
    public function getTotalNodosAttribute()
    {
        return $this->nodos()->count();
    }

    public function getNodoInicialAttribute()
    {
        return $this->nodos()->where('es_inicial', true)->first();
    }

    public function getNodosFinalesAttribute()
    {
        return $this->nodos()->where('es_final', true)->get();
    }

    public function getTieneMetadataUnityAttribute()
    {
        return !empty($this->metadata_unity);
    }

    /**
     * Métodos para manejo del grafo
     */
    public function obtenerEstructuraGrafo()
    {
        $nodos = $this->nodos()->with(['rol', 'conversant', 'respuestas.nodoSiguiente'])->get();
        
        $grafo = [
            'nodos' => $nodos->map(function($nodo) {
                return [
                    'id' => $nodo->id,
                    'titulo' => $nodo->titulo,
                    'tipo' => $nodo->tipo,
                    'posicion' => ['x' => $nodo->posicion_x, 'y' => $nodo->posicion_y],
                    'rol' => $nodo->rol ? [
                        'id' => $nodo->rol->id,
                        'nombre' => $nodo->rol->nombre,
                    ] : null,
                    'conversant' => $nodo->conversant ? [
                        'id' => $nodo->conversant->id,
                        'nombre' => $nodo->conversant->nombre,
                    ] : null,
                    'es_inicial' => $nodo->es_inicial,
                    'es_final' => $nodo->es_final,
                    'contenido' => $nodo->contenido,
                    'menu_text' => $nodo->menu_text,
                    'instrucciones' => $nodo->instrucciones,
                ];
            }),
            'conexiones' => $nodos->flatMap(function($nodo) {
                return $nodo->respuestas->map(function($respuesta) use ($nodo) {
                    return [
                        'id' => $respuesta->id,
                        'desde' => $nodo->id,
                        'hacia' => $respuesta->nodo_siguiente_id,
                        'texto' => $respuesta->texto,
                        'color' => $respuesta->color,
                        'puntuacion' => $respuesta->puntuacion,
                        'condiciones' => $respuesta->condiciones,
                        'consecuencias' => $respuesta->consecuencias,
                    ];
                });
            })
        ];
        
        return $grafo;
    }

    public function actualizarPosicionesNodos($posiciones)
    {
        foreach ($posiciones as $nodoId => $posicion) {
            $nodo = $this->nodos()->find($nodoId);
            if ($nodo) {
                $nodo->actualizarPosicion($posicion['x'], $posicion['y']);
            }
        }
    }

    public function obtenerNodosPorPosicion($x, $y, $tolerancia = 50)
    {
        return $this->nodos()
            ->whereBetween('posicion_x', [$x - $tolerancia, $x + $tolerancia])
            ->whereBetween('posicion_y', [$y - $tolerancia, $y + $tolerancia])
            ->get();
    }

    public function validarEstructuraGrafo()
    {
        $nodos = $this->nodos()->get();
        $errores = [];
        
        // Verificar que hay exactamente un nodo inicial
        $nodosIniciales = $nodos->where('es_inicial', true);
        if ($nodosIniciales->count() === 0) {
            $errores[] = 'El diálogo debe tener al menos un nodo inicial';
        } elseif ($nodosIniciales->count() > 1) {
            $errores[] = 'El diálogo debe tener exactamente un nodo inicial';
        }
        
        // Verificar que hay al menos un nodo final
        if ($nodos->where('es_final', true)->count() === 0) {
            $errores[] = 'El diálogo debe tener al menos un nodo final';
        }
        
        // Verificar que no hay nodos huérfanos (excepto el inicial)
        $nodosConConexiones = $nodos->filter(function($nodo) {
            return $nodo->respuestas()->count() > 0 || $nodo->respuestasEntrantes()->count() > 0;
        });
        
        $nodosHuerfanos = $nodos->diff($nodosConConexiones)->where('es_inicial', false);
        if ($nodosHuerfanos->count() > 0) {
            $errores[] = 'Hay nodos huérfanos: ' . $nodosHuerfanos->pluck('titulo')->join(', ');
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Métodos para exportar a formato Unity
     */
    public function exportarParaUnity()
    {
        $nodos = $this->nodos()->with(['rol', 'conversant', 'respuestas'])->get();
        
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'version' => $this->version,
            'nodos' => $nodos->map(function($nodo) {
                return [
                    'id' => $nodo->id,
                    'titulo' => $nodo->titulo,
                    'contenido' => $nodo->contenido,
                    'menu_text' => $nodo->menu_text,
                    'tipo' => $nodo->tipo,
                    'posicion' => ['x' => $nodo->posicion_x, 'y' => $nodo->posicion_y],
                    'rol_id' => $nodo->rol_id,
                    'conversant_id' => $nodo->conversant_id,
                    'es_inicial' => $nodo->es_inicial,
                    'es_final' => $nodo->es_final,
                    'condiciones' => $nodo->condiciones,
                    'consecuencias' => $nodo->consecuencias,
                    'metadata' => $nodo->metadata,
                    'respuestas' => $nodo->respuestas->map(function($respuesta) {
                        return [
                            'id' => $respuesta->id,
                            'texto' => $respuesta->texto,
                            'nodo_siguiente_id' => $respuesta->nodo_siguiente_id,
                            'puntuacion' => $respuesta->puntuacion,
                            'color' => $respuesta->color,
                            'condiciones' => $respuesta->condiciones,
                            'consecuencias' => $respuesta->consecuencias,
                        ];
                    }),
                ];
            }),
            'metadata_unity' => $this->metadata_unity,
        ];
    }

    /**
     * Métodos útiles
     */
    public function puedeSerEditadoPor($user)
    {
        return $this->creado_por === $user->id || $user->tipo === 'admin';
    }

    public function puedeSerUsadoPor($user)
    {
        return $this->publico || $this->creado_por === $user->id || $user->tipo === 'admin';
    }

    public function activar()
    {
        return $this->update(['estado' => 'activo']);
    }

    public function archivar()
    {
        return $this->update(['estado' => 'archivado']);
    }

    public function crearCopia($nuevoNombre, $usuarioId)
    {
        $nuevoDialogo = $this->replicate();
        $nuevoDialogo->nombre = $nuevoNombre;
        $nuevoDialogo->creado_por = $usuarioId;
        $nuevoDialogo->estado = 'borrador';
        $nuevoDialogo->version = '1.0.0';
        $nuevoDialogo->save();

        // Copiar nodos
        foreach ($this->nodos as $nodo) {
            $nuevoNodo = $nodo->replicate();
            $nuevoNodo->dialogo_id = $nuevoDialogo->id;
            $nuevoNodo->save();

            // Copiar respuestas
            foreach ($nodo->respuestas as $respuesta) {
                $nuevaRespuesta = $respuesta->replicate();
                $nuevaRespuesta->nodo_padre_id = $nuevoNodo->id;
                $nuevaRespuesta->save();
            }
        }

        return $nuevoDialogo;
    }
}
