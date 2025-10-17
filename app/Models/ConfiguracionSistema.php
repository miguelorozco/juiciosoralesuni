<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_sistema';

    protected $fillable = [
        'clave',
        'valor',
        'descripcion',
        'tipo',
        'actualizado_por',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_actualizacion' => 'datetime',
        ];
    }

    /**
     * Relaciones
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Scopes
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeActivas($query)
    {
        return $query->whereNotNull('valor');
    }

    /**
     * Accessors
     */
    public function getValorFormateadoAttribute()
    {
        switch ($this->tipo) {
            case 'boolean':
                return filter_var($this->valor, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($this->valor) ? (float) $this->valor : 0;
            case 'json':
                return json_decode($this->valor, true);
            default:
                return $this->valor;
        }
    }

    public function getNombreActualizadoPorAttribute()
    {
        return $this->actualizadoPor ? $this->actualizadoPor->name . ' ' . $this->actualizadoPor->apellido : 'Sistema';
    }

    public function getTiempoDesdeActualizacionAttribute()
    {
        return $this->fecha_actualizacion ? $this->fecha_actualizacion->diffForHumans() : 'Nunca';
    }

    /**
     * Métodos estáticos útiles
     */
    public static function obtener($clave, $valorPorDefecto = null)
    {
        $config = self::where('clave', $clave)->first();
        
        if (!$config) {
            return $valorPorDefecto;
        }
        
        return $config->valor_formateado;
    }

    public static function establecer($clave, $valor, $descripcion = null, $tipo = 'string', $usuarioId = null)
    {
        $config = self::where('clave', $clave)->first();
        
        if ($config) {
            $config->update([
                'valor' => $valor,
                'descripcion' => $descripcion ?? $config->descripcion,
                'tipo' => $tipo,
                'actualizado_por' => $usuarioId,
                'fecha_actualizacion' => now(),
            ]);
        } else {
            $config = self::create([
                'clave' => $clave,
                'valor' => $valor,
                'descripcion' => $descripcion,
                'tipo' => $tipo,
                'actualizado_por' => $usuarioId,
                'fecha_actualizacion' => now(),
            ]);
        }
        
        return $config;
    }

    public static function obtenerTodas()
    {
        return self::all()->pluck('valor_formateado', 'clave');
    }

    public static function obtenerPorTipo($tipo)
    {
        return self::porTipo($tipo)->get()->pluck('valor_formateado', 'clave');
    }

    /**
     * Métodos de instancia
     */
    public function actualizarValor($valor, $usuarioId = null)
    {
        return $this->update([
            'valor' => $valor,
            'actualizado_por' => $usuarioId,
            'fecha_actualizacion' => now(),
        ]);
    }

    public function esBooleana()
    {
        return $this->tipo === 'boolean';
    }

    public function esNumerica()
    {
        return $this->tipo === 'number';
    }

    public function esJson()
    {
        return $this->tipo === 'json';
    }

    public function esString()
    {
        return $this->tipo === 'string';
    }
}