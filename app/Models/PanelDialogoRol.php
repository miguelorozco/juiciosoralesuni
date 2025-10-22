<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanelDialogoRol extends Model
{
    use HasFactory;

    protected $table = 'panel_dialogo_roles';

    protected $fillable = [
        'escenario_id',
        'nombre',
        'descripcion',
        'color',
        'icono',
        'requerido',
        'activo',
        'orden',
        'configuracion'
    ];

    protected $casts = [
        'configuracion' => 'array',
        'requerido' => 'boolean',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoEscenario::class, 'escenario_id');
    }

    public function flujos(): HasMany
    {
        return $this->hasMany(PanelDialogoFlujo::class, 'rol_id')->orderBy('orden');
    }

    public function flujosActivos(): HasMany
    {
        return $this->hasMany(PanelDialogoFlujo::class, 'rol_id')->where('activo', true)->orderBy('orden');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PanelDialogoAsignacion::class, 'rol_id');
    }

    public function decisiones(): HasMany
    {
        return $this->hasMany(PanelDialogoDecision::class, 'rol_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRequeridos($query)
    {
        return $query->where('requerido', true);
    }

    public function scopePorEscenario($query, int $escenarioId)
    {
        return $query->where('escenario_id', $escenarioId);
    }

    // Accessors
    public function getTotalFlujosAttribute(): int
    {
        return $this->flujos()->count();
    }

    public function getTotalDialogosAttribute(): int
    {
        return $this->flujos()->withCount('dialogos')->get()->sum('dialogos_count');
    }

    public function getTotalOpcionesAttribute(): int
    {
        return $this->flujos()
            ->with(['dialogos.opciones'])
            ->get()
            ->pluck('dialogos')
            ->flatten()
            ->pluck('opciones')
            ->flatten()
            ->count();
    }

    // Métodos
    public function obtenerFlujoPrincipal(): ?PanelDialogoFlujo
    {
        return $this->flujosActivos()->orderBy('orden')->first();
    }

    public function obtenerTodosLosDialogos()
    {
        return $this->flujos()
            ->with(['dialogos.opciones'])
            ->get()
            ->pluck('dialogos')
            ->flatten();
    }

    public function obtenerDialogosIniciales()
    {
        return $this->obtenerTodosLosDialogos()->where('es_inicial', true);
    }

    public function obtenerDialogosFinales()
    {
        return $this->obtenerTodosLosDialogos()->where('es_final', true);
    }

    public function obtenerDialogosDeDecision()
    {
        return $this->obtenerTodosLosDialogos()->where('tipo', 'decision');
    }

    public function obtenerEstructuraFlujo(): array
    {
        $flujos = $this->flujosActivos()->with([
            'dialogos' => function($query) {
                $query->orderBy('orden');
            },
            'dialogos.opciones' => function($query) {
                $query->orderBy('orden');
            }
        ])->get();

        return [
            'rol' => $this,
            'flujos' => $flujos,
            'estadisticas' => [
                'total_flujos' => $this->total_flujos,
                'total_dialogos' => $this->total_dialogos,
                'total_opciones' => $this->total_opciones,
                'dialogos_iniciales' => $this->obtenerDialogosIniciales()->count(),
                'dialogos_finales' => $this->obtenerDialogosFinales()->count(),
                'dialogos_decision' => $this->obtenerDialogosDeDecision()->count()
            ]
        ];
    }
}