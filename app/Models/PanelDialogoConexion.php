<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoConexion extends Model
{
    use HasFactory;

    protected $table = 'panel_dialogo_conexiones';

    protected $fillable = [
        'escenario_id',
        'dialogo_origen_id',
        'dialogo_destino_id',
        'opcion_id',
        'tipo',
        'condiciones',
        'consecuencias',
        'activo'
    ];

    protected $casts = [
        'condiciones' => 'array',
        'consecuencias' => 'array',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoEscenario::class, 'escenario_id');
    }

    public function dialogoOrigen(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoDialogo::class, 'dialogo_origen_id');
    }

    public function dialogoDestino(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoDialogo::class, 'dialogo_destino_id');
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoOpcion::class, 'opcion_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorEscenario($query, int $escenarioId)
    {
        return $query->where('escenario_id', $escenarioId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Métodos
    public function validarCondiciones(): bool
    {
        if (!$this->condiciones) {
            return true; // Sin condiciones, siempre válida
        }

        // Aquí se implementaría la lógica de validación de condiciones
        // Por ejemplo, verificar variables de sesión, estado del juego, etc.
        return true;
    }

    public function aplicarConsecuencias(): array
    {
        if (!$this->consecuencias) {
            return [];
        }

        // Aquí se implementaría la lógica de aplicación de consecuencias
        // Por ejemplo, modificar puntuaciones, cambiar estados, etc.
        return $this->consecuencias;
    }

    public function obtenerInformacionCompleta(): array
    {
        return [
            'conexion' => $this,
            'dialogo_origen' => $this->dialogoOrigen,
            'dialogo_destino' => $this->dialogoDestino,
            'opcion' => $this->opcion,
            'condiciones_cumplidas' => $this->validarCondiciones(),
            'consecuencias_aplicadas' => $this->aplicarConsecuencias()
        ];
    }
}