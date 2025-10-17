<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaAsignacion extends Model
{
    use HasFactory;

    protected $table = 'plantilla_asignaciones';

    protected $fillable = [
        'plantilla_id',
        'rol_id',
        'usuario_id',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }

    /**
     * Relaciones
     */
    public function plantilla()
    {
        return $this->belongsTo(PlantillaSesion::class, 'plantilla_id');
    }

    public function rol()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Scopes
     */
    public function scopeConRolActivo($query)
    {
        return $query->whereHas('rol', function($query) {
            $query->where('activo', true);
        });
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeDePlantilla($query, $plantillaId)
    {
        return $query->where('plantilla_id', $plantillaId);
    }

    /**
     * Accessors
     */
    public function getNombreRolAttribute()
    {
        return $this->rol ? $this->rol->nombre : 'Rol no encontrado';
    }

    public function getNombreUsuarioAttribute()
    {
        return $this->usuario ? $this->usuario->name . ' ' . $this->usuario->apellido : 'Sin asignar';
    }

    public function getEstaAsignadaAttribute()
    {
        return !is_null($this->usuario_id);
    }

    /**
     * Métodos útiles
     */
    public function asignarUsuario($usuarioId)
    {
        return $this->update(['usuario_id' => $usuarioId]);
    }

    public function desasignarUsuario()
    {
        return $this->update(['usuario_id' => null]);
    }

    public function cambiarOrden($nuevoOrden)
    {
        return $this->update(['orden' => $nuevoOrden]);
    }

    public function esValida()
    {
        return $this->rol && $this->rol->activo;
    }
}