<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionRol extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_roles';

    protected $fillable = [
        'sesion_id',
        'usuario_id',
        'rol_dialogo_id',
        'rol_id',
        'asignado_por',
        'fecha_asignacion',
        'confirmado',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'confirmado' => 'boolean',
        ];
    }

    /**
     * Relaciones
     */
    public function sesion()
    {
        return $this->belongsTo(SesionJuicio::class, 'sesion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function rol()
    {
        return $this->belongsTo(RolDialogo::class, 'rol_dialogo_id');
    }

    public function rolDisponible()
    {
        return $this->belongsTo(RolDisponible::class, 'rol_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    /**
     * Scopes
     */
    public function scopeConfirmadas($query)
    {
        return $query->where('confirmado', true);
    }

    public function scopePendientes($query)
    {
        return $query->where('confirmado', false);
    }

    public function scopeDeSesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeConRolActivo($query)
    {
        return $query->whereHas('rol', function($query) {
            $query->where('activo', true);
        });
    }

    /**
     * Accessors
     */
    public function getNombreUsuarioAttribute()
    {
        return $this->usuario ? $this->usuario->name . ' ' . $this->usuario->apellido : 'Usuario no encontrado';
    }

    public function getNombreRolAttribute()
    {
        return $this->rol ? $this->rol->nombre : 'Rol no encontrado';
    }

    public function getNombreAsignadoPorAttribute()
    {
        return $this->asignadoPor ? $this->asignadoPor->name . ' ' . $this->asignadoPor->apellido : 'Sistema';
    }

    public function getEstadoConfirmacionAttribute()
    {
        return $this->confirmado ? 'Confirmado' : 'Pendiente';
    }

    public function getTiempoDesdeAsignacionAttribute()
    {
        return $this->fecha_asignacion ? $this->fecha_asignacion->diffForHumans() : 'No disponible';
    }

    /**
     * Métodos útiles
     */
    public function confirmar()
    {
        return $this->update(['confirmado' => true]);
    }

    public function desconfirmar()
    {
        return $this->update(['confirmado' => false]);
    }

    public function actualizarNotas($notas)
    {
        return $this->update(['notas' => $notas]);
    }

    public function cambiarRol($nuevoRolId, $asignadoPor)
    {
        return $this->update([
            'rol_dialogo_id' => $nuevoRolId,
            'asignado_por' => $asignadoPor,
            'fecha_asignacion' => now(),
            'confirmado' => false, // Requiere nueva confirmación
        ]);
    }

    public function esValida()
    {
        return $this->usuario && $this->rol && $this->rol->activo;
    }

    public function puedeSerConfirmada()
    {
        return !$this->confirmado && $this->esValida();
    }

    public function puedeSerModificada()
    {
        return $this->sesion && in_array($this->sesion->estado, ['programada']);
    }
}