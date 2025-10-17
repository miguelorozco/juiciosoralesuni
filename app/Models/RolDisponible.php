<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolDisponible extends Model
{
    use HasFactory;

    protected $table = 'roles_disponibles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'icono',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /**
     * Relaciones
     */
    public function asignacionesPlantillas()
    {
        return $this->hasMany(PlantillaAsignacion::class, 'rol_id');
    }

    public function asignacionesRoles()
    {
        return $this->hasMany(AsignacionRol::class, 'rol_id');
    }

    public function nodosDialogo()
    {
        return $this->hasMany(NodoDialogo::class, 'rol_id');
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Accessors
     */
    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ($this->descripcion ? ' - ' . $this->descripcion : '');
    }

    /**
     * Métodos estáticos útiles
     */
    public static function obtenerRolesActivos()
    {
        return self::activos()->ordenados()->get();
    }

    public static function obtenerRolesPorTipo($tipo = null)
    {
        $query = self::activos()->ordenados();
        
        if ($tipo) {
            $query->where('nombre', 'like', '%' . $tipo . '%');
        }
        
        return $query->get();
    }
}