<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UnityRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'nombre',
        'descripcion',
        'sesion_juicio_id',
        'creado_por',
        'estado',
        'configuracion',
        'audio_config',
        'participantes_activos',
        'max_participantes',
        'participantes_conectados',
        'fecha_creacion',
        'fecha_inicio',
        'fecha_fin',
        'ultima_actividad',
    ];

    protected function casts(): array
    {
        return [
            'configuracion' => 'array',
            'audio_config' => 'array',
            'participantes_activos' => 'array',
            'fecha_creacion' => 'datetime',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'ultima_actividad' => 'datetime',
        ];
    }

    /**
     * Relaciones
     */
    public function sesionJuicio(): BelongsTo
    {
        return $this->belongsTo(SesionJuicio::class, 'sesion_juicio_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(UnityRoomEvent::class, 'room_id', 'room_id');
    }

    /**
     * Scopes
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }

    public function scopeConParticipantes($query)
    {
        return $query->where('participantes_conectados', '>', 0);
    }

    public function scopePorSesion($query, $sesionId)
    {
        return $query->where('sesion_juicio_id', $sesionId);
    }

    /**
     * Accessors
     */
    public function getEstaActivaAttribute(): bool
    {
        return $this->estado === 'activa';
    }

    public function getTieneParticipantesAttribute(): bool
    {
        return $this->participantes_conectados > 0;
    }

    public function getPuedeConectarAttribute(): bool
    {
        return $this->estado === 'activa' && 
               $this->participantes_conectados < $this->max_participantes;
    }

    public function getTiempoActividadAttribute(): ?int
    {
        if ($this->fecha_inicio) {
            return now()->diffInMinutes($this->fecha_inicio);
        }
        return null;
    }

    /**
     * Métodos estáticos
     */
    public static function crearParaSesion(SesionJuicio $sesion, User $creador, array $configuracion = []): self
    {
        $roomId = self::generarRoomId();
        
        $configuracionDefault = [
            'unity_version' => '2022.3.15f1',
            'platform' => 'WindowsPlayer',
            'scene_name' => 'JuicioScene',
            'audio_enabled' => true,
            'voice_chat_enabled' => true,
            'max_voice_distance' => 10.0,
            'audio_quality' => 'high',
        ];

        return self::create([
            'room_id' => $roomId,
            'nombre' => "Sala de {$sesion->nombre}",
            'descripcion' => "Sala Unity para la sesión de juicio: {$sesion->descripcion}",
            'sesion_juicio_id' => $sesion->id,
            'creado_por' => $creador->id,
            'estado' => 'activa',
            'configuracion' => array_merge($configuracionDefault, $configuracion),
            'audio_config' => [
                'voice_chat_enabled' => true,
                'spatial_audio' => true,
                'max_distance' => 10.0,
                'volume_multiplier' => 1.0,
                'echo_cancellation' => true,
                'noise_suppression' => true,
            ],
            'participantes_activos' => [],
            'max_participantes' => $sesion->max_participantes ?? 10,
            'participantes_conectados' => 0,
            'fecha_creacion' => now(),
            'fecha_inicio' => now(),
            'ultima_actividad' => now(),
        ]);
    }

    public static function generarRoomId(): string
    {
        do {
            $roomId = 'room_' . Str::random(12) . '_' . time();
        } while (self::where('room_id', $roomId)->exists());
        
        return $roomId;
    }

    /**
     * Métodos de gestión de participantes
     */
    public function conectarParticipante(int $usuarioId, array $metadata = []): bool
    {
        if (!$this->puede_conectar) {
            return false;
        }

        $participantes = $this->participantes_activos ?? [];
        $participantes[$usuarioId] = [
            'usuario_id' => $usuarioId,
            'conectado_desde' => now()->toISOString(),
            'metadata' => $metadata,
            'posicion' => ['x' => 0, 'y' => 0, 'z' => 0],
            'rotacion' => ['x' => 0, 'y' => 0, 'z' => 0],
            'audio_enabled' => true,
            'microfono_activo' => false,
        ];

        $this->update([
            'participantes_activos' => $participantes,
            'participantes_conectados' => count($participantes),
            'ultima_actividad' => now(),
        ]);

        return true;
    }

    public function desconectarParticipante(int $usuarioId): bool
    {
        $participantes = $this->participantes_activos ?? [];
        
        if (!isset($participantes[$usuarioId])) {
            return false;
        }

        unset($participantes[$usuarioId]);

        $this->update([
            'participantes_activos' => $participantes,
            'participantes_conectados' => count($participantes),
            'ultima_actividad' => now(),
        ]);

        return true;
    }

    public function actualizarPosicionParticipante(int $usuarioId, array $posicion, array $rotacion = []): bool
    {
        $participantes = $this->participantes_activos ?? [];
        
        if (!isset($participantes[$usuarioId])) {
            return false;
        }

        $participantes[$usuarioId]['posicion'] = $posicion;
        if (!empty($rotacion)) {
            $participantes[$usuarioId]['rotacion'] = $rotacion;
        }
        $participantes[$usuarioId]['ultima_actualizacion'] = now()->toISOString();

        $this->update([
            'participantes_activos' => $participantes,
            'ultima_actividad' => now(),
        ]);

        return true;
    }

    public function actualizarAudioParticipante(int $usuarioId, bool $microfonoActivo, array $audioData = []): bool
    {
        $participantes = $this->participantes_activos ?? [];
        
        if (!isset($participantes[$usuarioId])) {
            return false;
        }

        $participantes[$usuarioId]['microfono_activo'] = $microfonoActivo;
        $participantes[$usuarioId]['audio_data'] = $audioData;
        $participantes[$usuarioId]['ultima_actualizacion'] = now()->toISOString();

        $this->update([
            'participantes_activos' => $participantes,
            'ultima_actividad' => now(),
        ]);

        return true;
    }

    /**
     * Métodos de gestión de estado
     */
    public function pausar(): bool
    {
        if ($this->estado === 'activa') {
            return $this->update(['estado' => 'pausada']);
        }
        return false;
    }

    public function reanudar(): bool
    {
        if ($this->estado === 'pausada') {
            return $this->update(['estado' => 'activa']);
        }
        return false;
    }

    public function finalizar(): bool
    {
        if (in_array($this->estado, ['activa', 'pausada'])) {
            return $this->update([
                'estado' => 'finalizada',
                'fecha_fin' => now(),
            ]);
        }
        return false;
    }

    public function cerrar(): bool
    {
        return $this->update([
            'estado' => 'cerrada',
            'fecha_fin' => now(),
        ]);
    }

    /**
     * Métodos de utilidad
     */
    public function obtenerParticipante(int $usuarioId): ?array
    {
        $participantes = $this->participantes_activos ?? [];
        return $participantes[$usuarioId] ?? null;
    }

    public function obtenerParticipantesConectados(): array
    {
        return $this->participantes_activos ?? [];
    }

    public function limpiarParticipantesInactivos(int $timeoutMinutos = 5): int
    {
        $participantes = $this->participantes_activos ?? [];
        $limpiados = 0;
        $timeout = now()->subMinutes($timeoutMinutos);

        foreach ($participantes as $usuarioId => $participante) {
            $ultimaActualizacion = $participante['ultima_actualizacion'] ?? $participante['conectado_desde'];
            if (strtotime($ultimaActualizacion) < $timeout->timestamp) {
                unset($participantes[$usuarioId]);
                $limpiados++;
            }
        }

        if ($limpiados > 0) {
            $this->update([
                'participantes_activos' => $participantes,
                'participantes_conectados' => count($participantes),
            ]);
        }

        return $limpiados;
    }

    /**
     * Verificar si un usuario puede gestionar esta sala
     */
    public function puedeSerGestionadaPor(User $user): bool
    {
        return $this->creado_por === $user->id || 
               $this->sesionJuicio->puedeSerGestionadaPor($user) ||
               $user->tipo === 'admin';
    }
}