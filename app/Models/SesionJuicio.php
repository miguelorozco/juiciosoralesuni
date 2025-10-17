<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesionJuicio extends Model
{
    use HasFactory;

    protected $table = 'sesiones_juicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'instructor_id',
        'plantilla_id',
        'estado',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin',
        'max_participantes',
        'configuracion',
        'unity_room_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'max_participantes' => 'integer',
            'configuracion' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(PlantillaSesion::class, 'plantilla_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionRol::class, 'sesion_id');
    }

    public function participantes()
    {
        return $this->belongsToMany(User::class, 'asignaciones_roles', 'sesion_id', 'usuario_id')
                    ->withPivot('rol_id', 'asignado_por', 'fecha_asignacion', 'confirmado', 'notas')
                    ->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(RolDisponible::class, 'asignaciones_roles', 'sesion_id', 'rol_id')
                    ->withPivot('usuario_id', 'asignado_por', 'fecha_asignacion', 'confirmado', 'notas')
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['programada', 'en_curso']);
    }

    public function scopeEnCurso($query)
    {
        return $query->where('estado', 'en_curso');
    }

    public function scopeProgramadas($query)
    {
        return $query->where('estado', 'programada');
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', 'finalizada');
    }

    public function scopeDelInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeConParticipantes($query)
    {
        return $query->with(['asignaciones.usuario', 'asignaciones.rol']);
    }

    /**
     * Accessors
     */
    public function getTotalParticipantesAttribute()
    {
        return $this->asignaciones()->count();
    }

    public function getParticipantesConfirmadosAttribute()
    {
        return $this->asignaciones()->where('confirmado', true)->count();
    }

    public function getParticipantesPendientesAttribute()
    {
        return $this->asignaciones()->where('confirmado', false)->count();
    }

    public function getDuracionAttribute()
    {
        if ($this->fecha_inicio && $this->fecha_fin) {
            return $this->fecha_inicio->diffInMinutes($this->fecha_fin);
        }
        return null;
    }

    public function getEstaActivaAttribute()
    {
        return in_array($this->estado, ['programada', 'en_curso']);
    }

    public function getPuedeIniciarAttribute()
    {
        return $this->estado === 'programada' && $this->participantes_confirmados > 0;
    }

    /**
     * Métodos útiles
     */
    public function iniciar()
    {
        if ($this->puede_iniciar) {
            return $this->update([
                'estado' => 'en_curso',
                'fecha_inicio' => now(),
            ]);
        }
        return false;
    }

    public function finalizar()
    {
        if ($this->estado === 'en_curso') {
            return $this->update([
                'estado' => 'finalizada',
                'fecha_fin' => now(),
            ]);
        }
        return false;
    }

    public function cancelar()
    {
        if (in_array($this->estado, ['programada', 'en_curso'])) {
            return $this->update(['estado' => 'cancelada']);
        }
        return false;
    }

    public function agregarParticipante($usuarioId, $rolId, $asignadoPor, $notas = null)
    {
        return $this->asignaciones()->create([
            'usuario_id' => $usuarioId,
            'rol_id' => $rolId,
            'asignado_por' => $asignadoPor,
            'notas' => $notas,
        ]);
    }

    public function removerParticipante($usuarioId)
    {
        return $this->asignaciones()->where('usuario_id', $usuarioId)->delete();
    }

    public function confirmarParticipante($usuarioId)
    {
        return $this->asignaciones()->where('usuario_id', $usuarioId)->update(['confirmado' => true]);
    }

    public function generarRoomId()
    {
        $roomId = 'sesion_' . $this->id . '_' . time();
        $this->update(['unity_room_id' => $roomId]);
        return $roomId;
    }

    public function obtenerParticipantePorRol($rolId)
    {
        return $this->asignaciones()->where('rol_id', $rolId)->first();
    }

    public function obtenerParticipantePorUsuario($usuarioId)
    {
        return $this->asignaciones()->where('usuario_id', $usuarioId)->first();
    }

    /**
     * Verificar si un usuario puede gestionar esta sesión
     */
    public function puedeSerGestionadaPor($user)
    {
        return $this->instructor_id === $user->id || $user->tipo === 'admin';
    }

    /**
     * Relación con diálogos de la sesión
     */
    public function dialogos()
    {
        return $this->hasMany(SesionDialogo::class, 'sesion_id');
    }

    /**
     * Obtener diálogo activo
     */
    public function dialogoActivo()
    {
        return $this->dialogos()
            ->whereIn('estado', ['iniciado', 'en_curso', 'pausado'])
            ->first();
    }
}