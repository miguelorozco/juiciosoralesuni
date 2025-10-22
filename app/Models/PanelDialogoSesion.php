<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PanelDialogoSesion extends Model
{
    protected $table = 'panel_dialogo_sesiones';

    protected $fillable = [
        'escenario_id',
        'instructor_id',
        'nombre',
        'descripcion',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'configuracion',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    // Relaciones
    public function escenario(): BelongsTo
    {
        return $this->belongsTo(PanelDialogoEscenario::class, 'escenario_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PanelDialogoAsignacion::class, 'sesion_id');
    }

    public function decisiones(): HasMany
    {
        return $this->hasMany(PanelDialogoDecision::class, 'sesion_id');
    }
}
