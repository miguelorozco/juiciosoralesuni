<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantillaSesionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden crear plantillas
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
                'max:200',
                'min:3',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\d\-_]+$/u'
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:1000',
                'min:10'
            ],
            'publica' => [
                'boolean'
            ],
            'configuracion' => [
                'nullable',
                'array'
            ],
            'configuracion.*' => [
                'string'
            ],
            'roles' => [
                'nullable',
                'array',
                'min:1',
                'max:20' // Máximo 20 roles por plantilla
            ],
            'roles.*.rol_id' => [
                'required',
                'integer',
                'exists:roles_disponibles,id',
                'distinct' // No duplicados
            ],
            'roles.*.usuario_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'roles.*.orden' => [
                'nullable',
                'integer',
                'min:1',
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
            'nombre.required' => 'El nombre de la plantilla es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 200 caracteres.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos.',
            
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            
            'publica.boolean' => 'El estado público debe ser verdadero o falso.',
            
            'configuracion.array' => 'La configuración debe ser un objeto JSON válido.',
            'configuracion.*.string' => 'Cada elemento de la configuración debe ser una cadena de texto.',
            
            'roles.array' => 'Los roles deben ser un array válido.',
            'roles.min' => 'Debe incluir al menos 1 rol en la plantilla.',
            'roles.max' => 'No puede incluir más de 20 roles en la plantilla.',
            
            'roles.*.rol_id.required' => 'El ID del rol es obligatorio.',
            'roles.*.rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'roles.*.rol_id.exists' => 'El rol seleccionado no existe.',
            'roles.*.rol_id.distinct' => 'No puede incluir el mismo rol dos veces.',
            
            'roles.*.usuario_id.integer' => 'El ID del usuario debe ser un número entero.',
            'roles.*.usuario_id.exists' => 'El usuario seleccionado no existe.',
            
            'roles.*.orden.integer' => 'El orden debe ser un número entero.',
            'roles.*.orden.min' => 'El orden no puede ser menor a 1.',
            'roles.*.orden.max' => 'El orden no puede ser mayor a 999.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la plantilla',
            'descripcion' => 'descripción',
            'publica' => 'visibilidad pública',
            'configuracion' => 'configuración',
            'roles' => 'roles',
            'roles.*.rol_id' => 'ID del rol',
            'roles.*.usuario_id' => 'ID del usuario',
            'roles.*.orden' => 'orden'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validaciones adicionales personalizadas
            if ($this->has('roles') && is_array($this->roles)) {
                // Verificar que todos los roles estén activos
                $rolIds = collect($this->roles)->pluck('rol_id')->toArray();
                $rolesActivos = \App\Models\RolDisponible::whereIn('id', $rolIds)
                    ->where('activo', true)
                    ->pluck('id')
                    ->toArray();
                
                $rolesInactivos = array_diff($rolIds, $rolesActivos);
                if (!empty($rolesInactivos)) {
                    $validator->errors()->add('roles', 'Algunos roles seleccionados están inactivos: ' . implode(', ', $rolesInactivos));
                }
                
                // Verificar que no haya órdenes duplicados
                $ordenes = collect($this->roles)->pluck('orden')->filter()->toArray();
                if (count($ordenes) !== count(array_unique($ordenes))) {
                    $validator->errors()->add('roles', 'No puede haber órdenes duplicados en los roles.');
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
        
        // Asegurar que publica sea boolean
        if ($this->has('publica')) {
            $this->merge([
                'publica' => filter_var($this->publica, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        }
    }
}