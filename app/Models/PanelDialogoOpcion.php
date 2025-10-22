<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoOpcion extends Model
{
    use HasFactory;

    protected $table = 'panel_dialogo_opciones';

    protected $fillable = [
        'dialogo_id',
        'texto',
        'descripcion',
        'letra',
        'color',
        'puntuacion',
        'activo',
        'orden',
        'configuracion'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function dialogo(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoDialogo::class, 'dialogo_id');
    }

    public function conexiones(): HasMany
    {
        return $this->hasMany(PanelDialogoConexion::class, 'opcion_id');
    }

    public function decisiones(): HasMany
    {
        return $this->hasMany(PanelDialogoDecision::class, 'opcion_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorDialogo($query, int $dialogoId)
    {
        return $query->where('dialogo_id', $dialogoId);
    }

    public function scopePorLetra($query, string $letra)
    {
        return $query->where('letra', strtoupper($letra));
    }

    // Accessors
    public function getTotalConexionesAttribute(): int
    {
        return $this->conexiones()->count();
    }

    public function getTotalDecisionesAttribute(): int
    {
        return $this->decisiones()->count();
    }

    public function getEsOpcionAAttribute(): bool
    {
        return strtoupper($this->letra) === 'A';
    }

    public function getEsOpcionBAttribute(): bool
    {
        return strtoupper($this->letra) === 'B';
    }

    public function getEsOpcionCAttribute(): bool
    {
        return strtoupper($this->letra) === 'C';
    }

    public function getColorHexAttribute(): string
    {
        return $this->color ?: '#007bff';
    }

    public function getTextoCompletoAttribute(): string
    {
        return $this->letra . ': ' . $this->texto;
    }

    // Métodos
    public function obtenerDialogoSiguiente(): ?PanelDialogoDialogo
    {
        $conexion = $this->conexiones()->first();
        return $conexion ? $conexion->dialogoDestino : null;
    }

    public function tieneConexion(): bool
    {
        return $this->conexiones()->count() > 0;
    }

    public function obtenerConsecuencias(): array
    {
        $conexion = $this->conexiones()->first();
        return $conexion ? ($conexion->consecuencias ?? []) : [];
    }

    public function obtenerCondiciones(): array
    {
        $conexion = $this->conexiones()->first();
        return $conexion ? ($conexion->condiciones ?? []) : [];
    }

    public function validarEstructura(): array
    {
        $errores = [];

        // Validar texto
        if (empty(trim($this->texto))) {
            $errores[] = 'El texto de la opción no puede estar vacío';
        }

        // Validar letra
        if (!in_array(strtoupper($this->letra), ['A', 'B', 'C'])) {
            $errores[] = 'La letra de la opción debe ser A, B o C';
        }

        // Validar color
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color)) {
            $errores[] = 'El color debe ser un código hexadecimal válido';
        }

        // Validar puntuación
        if ($this->puntuacion < 0 || $this->puntuacion > 100) {
            $errores[] = 'La puntuación debe estar entre 0 y 100';
        }

        // Validar orden
        if ($this->orden < 0) {
            $errores[] = 'El orden debe ser un número positivo';
        }

        return $errores;
    }

    public function obtenerEstadisticas(): array
    {
        return [
            'total_conexiones' => $this->total_conexiones,
            'total_decisiones' => $this->total_decisiones,
            'tiene_conexion' => $this->tieneConexion(),
            'es_opcion_a' => $this->es_opcion_a,
            'es_opcion_b' => $this->es_opcion_b,
            'es_opcion_c' => $this->es_opcion_c,
            'color_hex' => $this->color_hex,
            'texto_completo' => $this->texto_completo
        ];
    }

    public function duplicar(): self
    {
        $nuevaOpcion = $this->replicate(['id', 'created_at', 'updated_at']);
        $nuevaOpcion->texto = $this->texto . ' (Copia)';
        $nuevaOpcion->save();

        return $nuevaOpcion;
    }

    public function activar(): void
    {
        $this->update(['activo' => true]);
    }

    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }

    public function cambiarLetra(string $nuevaLetra): void
    {
        $letraValida = in_array(strtoupper($nuevaLetra), ['A', 'B', 'C']);
        
        if ($letraValida) {
            $this->update(['letra' => strtoupper($nuevaLetra)]);
        }
    }

    public function cambiarColor(string $nuevoColor): void
    {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $nuevoColor)) {
            $this->update(['color' => $nuevoColor]);
        }
    }

    public function actualizarPuntuacion(int $nuevaPuntuacion): void
    {
        if ($nuevaPuntuacion >= 0 && $nuevaPuntuacion <= 100) {
            $this->update(['puntuacion' => $nuevaPuntuacion]);
        }
    }
}