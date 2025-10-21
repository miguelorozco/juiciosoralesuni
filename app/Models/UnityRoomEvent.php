<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnityRoomEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'event_type',
        'usuario_id',
        'event_data',
        'metadata',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'metadata' => 'array',
            'timestamp' => 'datetime',
        ];
    }

    /**
     * Relaciones
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(UnityRoom::class, 'room_id', 'room_id');
    }

    /**
     * Scopes
     */
    public function scopePorRoom($query, string $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopePorTipo($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeRecientes($query, int $minutos = 60)
    {
        return $query->where('timestamp', '>=', now()->subMinutes($minutos));
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Métodos estáticos para crear eventos
     */
    public static function crearEvento(string $roomId, string $eventType, ?int $usuarioId = null, array $eventData = [], array $metadata = []): self
    {
        return self::create([
            'room_id' => $roomId,
            'event_type' => $eventType,
            'usuario_id' => $usuarioId,
            'event_data' => $eventData,
            'metadata' => $metadata,
            'timestamp' => now(),
        ]);
    }

    public static function usuarioConectado(string $roomId, int $usuarioId, array $metadata = []): self
    {
        return self::crearEvento(
            $roomId,
            'usuario_conectado',
            $usuarioId,
            [
                'usuario_id' => $usuarioId,
                'conectado_desde' => now()->toISOString(),
            ],
            $metadata
        );
    }

    public static function usuarioDesconectado(string $roomId, int $usuarioId, array $metadata = []): self
    {
        return self::crearEvento(
            $roomId,
            'usuario_desconectado',
            $usuarioId,
            [
                'usuario_id' => $usuarioId,
                'desconectado_desde' => now()->toISOString(),
            ],
            $metadata
        );
    }

    public static function posicionActualizada(string $roomId, int $usuarioId, array $posicion, array $rotacion = []): self
    {
        return self::crearEvento(
            $roomId,
            'posicion_actualizada',
            $usuarioId,
            [
                'usuario_id' => $usuarioId,
                'posicion' => $posicion,
                'rotacion' => $rotacion,
            ]
        );
    }

    public static function audioCambio(string $roomId, int $usuarioId, bool $microfonoActivo, array $audioData = []): self
    {
        return self::crearEvento(
            $roomId,
            'audio_cambio',
            $usuarioId,
            [
                'usuario_id' => $usuarioId,
                'microfono_activo' => $microfonoActivo,
                'audio_data' => $audioData,
            ]
        );
    }

    public static function mensajeChat(string $roomId, int $usuarioId, string $mensaje, string $tipo = 'texto'): self
    {
        return self::crearEvento(
            $roomId,
            'mensaje_chat',
            $usuarioId,
            [
                'usuario_id' => $usuarioId,
                'mensaje' => $mensaje,
                'tipo' => $tipo,
            ]
        );
    }

    public static function dialogoEvento(string $roomId, string $eventType, array $dialogoData): self
    {
        return self::crearEvento(
            $roomId,
            'dialogo_' . $eventType,
            null,
            $dialogoData
        );
    }

    public static function salaEstado(string $roomId, string $estado, array $metadata = []): self
    {
        return self::crearEvento(
            $roomId,
            'sala_estado',
            null,
            [
                'estado' => $estado,
                'timestamp' => now()->toISOString(),
            ],
            $metadata
        );
    }

    /**
     * Métodos de utilidad
     */
    public function esEventoUsuario(): bool
    {
        return !is_null($this->usuario_id);
    }

    public function esEventoAudio(): bool
    {
        return in_array($this->event_type, ['audio_cambio', 'microfono_activo', 'microfono_desactivado']);
    }

    public function esEventoPosicion(): bool
    {
        return $this->event_type === 'posicion_actualizada';
    }

    public function esEventoDialogo(): bool
    {
        return str_starts_with($this->event_type, 'dialogo_');
    }

    public function obtenerDatosUsuario(): ?array
    {
        if (!$this->esEventoUsuario()) {
            return null;
        }

        return $this->event_data['usuario_id'] ?? null;
    }

    public function obtenerPosicion(): ?array
    {
        if (!$this->esEventoPosicion()) {
            return null;
        }

        return $this->event_data['posicion'] ?? null;
    }

    public function obtenerAudioData(): ?array
    {
        if (!$this->esEventoAudio()) {
            return null;
        }

        return $this->event_data['audio_data'] ?? null;
    }
}