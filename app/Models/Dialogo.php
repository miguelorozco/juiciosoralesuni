<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dialogo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'creado_por',
        'plantilla_id',
        'publico',
        'estado',
        'configuracion',
    ];

    protected function casts(): array
    {
        return [
            'publico' => 'boolean',
            'configuracion' => 'array',
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
        return $this->hasMany(NodoDialogo::class)->orderBy('orden');
    }

    public function roles()
    {
        return $this->hasMany(RolDialogo::class)->orderBy('orden');
    }

    public function rolesActivos()
    {
        return $this->hasMany(RolDialogo::class)->where('activo', true)->orderBy('orden');
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

    /**
     * Métodos para manejo del grafo
     */
    public function obtenerEstructuraGrafo()
    {
        $nodos = $this->nodos()->with(['rol', 'respuestas.nodoSiguiente'])->get();
        
        $grafo = [
            'nodos' => $nodos->map(function($nodo) {
                return [
                    'id' => $nodo->id,
                    'titulo' => $nodo->titulo,
                    'tipo' => $nodo->tipo,
                    'posicion' => $nodo->posicion,
                    'rol' => $nodo->rol ? [
                        'id' => $nodo->rol->id,
                        'nombre' => $nodo->rol->nombre,
                        'color' => $nodo->rol->color,
                        'icono' => $nodo->rol->icono
                    ] : null,
                    'es_inicial' => $nodo->es_inicial,
                    'es_final' => $nodo->es_final,
                    'contenido' => $nodo->contenido,
                    'instrucciones' => $nodo->instrucciones
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
                        'consecuencias' => $respuesta->consecuencias
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
        return $this->nodos()->get()->filter(function($nodo) use ($x, $y, $tolerancia) {
            $posicion = $nodo->posicion;
            $distancia = sqrt(pow($posicion['x'] - $x, 2) + pow($posicion['y'] - $y, 2));
            return $distancia <= $tolerancia;
        });
    }

    public function validarEstructuraGrafo()
    {
        $nodos = $this->nodos()->get();
        $errores = [];
        
        // Verificar que hay al menos un nodo inicial
        if ($nodos->where('es_inicial', true)->count() === 0) {
            $errores[] = 'El diálogo debe tener al menos un nodo inicial';
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
