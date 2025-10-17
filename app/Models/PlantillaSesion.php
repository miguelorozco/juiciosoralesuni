<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaSesion extends Model
{
    use HasFactory;

    protected $table = 'plantillas_sesiones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'creado_por',
        'publica',
        'fecha_creacion',
        'configuracion',
    ];

    protected function casts(): array
    {
        return [
            'publica' => 'boolean',
            'fecha_creacion' => 'datetime',
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

    public function asignaciones()
    {
        return $this->hasMany(PlantillaAsignacion::class, 'plantilla_id');
    }

    public function sesiones()
    {
        return $this->hasMany(SesionJuicio::class, 'plantilla_id');
    }

    public function roles()
    {
        return $this->belongsToMany(RolDisponible::class, 'plantilla_asignaciones', 'plantilla_id', 'rol_id')
                    ->withPivot('usuario_id', 'orden')
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopePublicas($query)
    {
        return $query->where('publica', true);
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('creado_por', $userId);
    }

    public function scopeConAsignaciones($query)
    {
        return $query->with(['asignaciones.rol', 'asignaciones.usuario']);
    }

    /**
     * Accessors
     */
    public function getTotalRolesAttribute()
    {
        return $this->asignaciones()->count();
    }

    public function getRolesActivosAttribute()
    {
        return $this->asignaciones()->whereHas('rol', function($query) {
            $query->where('activo', true);
        })->count();
    }

    /**
     * Métodos útiles
     */
    public function agregarRol($rolId, $usuarioId = null, $orden = null)
    {
        $orden = $orden ?? $this->asignaciones()->max('orden') + 1;
        
        return $this->asignaciones()->create([
            'rol_id' => $rolId,
            'usuario_id' => $usuarioId,
            'orden' => $orden,
        ]);
    }

    public function removerRol($rolId)
    {
        return $this->asignaciones()->where('rol_id', $rolId)->delete();
    }

    public function puedeSerUsadaPor($usuario)
    {
        return $this->publica || $this->creado_por === $usuario->id;
    }

    public function crearSesion($datosSesion)
    {
        $datosSesion['plantilla_id'] = $this->id;
        return SesionJuicio::create($datosSesion);
    }
}