<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolDisponibleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden crear roles
        return auth()->check() && in_array(auth()->user()->tipo, ['admin', 'instructor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                'min:2',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\d]+$/u', // Solo letras, números y espacios
                'unique:roles_disponibles,nombre'
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
                'min:10'
            ],
            'color' => [
                'nullable',
                'string',
                'max:7',
                'min:7',
                'regex:/^#[0-9A-Fa-f]{6}$/' // Formato hexadecimal válido
            ],
            'icono' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\-_\.]+$/' // Solo caracteres válidos para nombres de iconos
            ],
            'activo' => [
                'boolean'
            ],
            'orden' => [
                'nullable',
                'integer',
                'min:0',
                'max:999'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            
            'color.string' => 'El color debe ser una cadena de texto.',
            'color.max' => 'El color debe tener exactamente 7 caracteres.',
            'color.min' => 'El color debe tener exactamente 7 caracteres.',
            'color.regex' => 'El color debe ser un código hexadecimal válido (ej: #FF0000).',
            
            'icono.string' => 'El icono debe ser una cadena de texto.',
            'icono.max' => 'El nombre del icono no puede tener más de 100 caracteres.',
            'icono.regex' => 'El nombre del icono solo puede contener letras, números, guiones y puntos.',
            
            'activo.boolean' => 'El estado activo debe ser verdadero o falso.',
            
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser menor a 0.',
            'orden.max' => 'El orden no puede ser mayor a 999.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del rol',
            'descripcion' => 'descripción',
            'color' => 'color',
            'icono' => 'icono',
            'activo' => 'estado activo',
            'orden' => 'orden'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validaciones adicionales personalizadas
            if ($this->has('color') && $this->color) {
                // Verificar que el color no sea muy claro (para legibilidad)
                $color = str_replace('#', '', $this->color);
                $r = hexdec(substr($color, 0, 2));
                $g = hexdec(substr($color, 2, 2));
                $b = hexdec(substr($color, 4, 2));
                
                // Calcular luminancia
                $luminancia = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                
                if ($luminancia > 0.8) {
                    $validator->errors()->add('color', 'El color es demasiado claro para una buena legibilidad.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar datos antes de la validación
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }
        
        if ($this->has('descripcion')) {
            $this->merge([
                'descripcion' => trim($this->descripcion)
            ]);
        }
        
        if ($this->has('color') && $this->color) {
            $this->merge([
                'color' => strtoupper($this->color)
            ]);
        }
        
        if ($this->has('icono')) {
            $this->merge([
                'icono' => strtolower(trim($this->icono))
            ]);
        }
    }
}