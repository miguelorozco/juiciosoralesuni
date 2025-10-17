<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgregarRolPlantillaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden agregar roles a plantillas
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
            'rol_id' => [
                'required',
                'integer',
                'exists:roles_disponibles,id'
            ],
            'usuario_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'orden' => [
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
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
            
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden no puede ser menor a 1.',
            'orden.max' => 'El orden no puede ser mayor a 999.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'rol_id' => 'ID del rol',
            'usuario_id' => 'ID del usuario',
            'orden' => 'orden'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar que el rol esté activo
            if ($this->has('rol_id')) {
                $rol = \App\Models\RolDisponible::find($this->rol_id);
                if ($rol && !$rol->activo) {
                    $validator->errors()->add('rol_id', 'El rol seleccionado está inactivo.');
                }
            }
            
            // Verificar que el usuario esté activo
            if ($this->has('usuario_id') && $this->usuario_id) {
                $usuario = \App\Models\User::find($this->usuario_id);
                if ($usuario && !$usuario->activo) {
                    $validator->errors()->add('usuario_id', 'El usuario seleccionado está inactivo.');
                }
            }
            
            // Verificar que el rol no esté ya asignado a la plantilla
            if ($this->has('rol_id')) {
                $plantilla = $this->route('plantillaSesion');
                $rolExistente = $plantilla->asignaciones()->where('rol_id', $this->rol_id)->first();
                if ($rolExistente) {
                    $validator->errors()->add('rol_id', 'Este rol ya está asignado a la plantilla.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Asegurar que los IDs sean integers
        if ($this->has('rol_id')) {
            $this->merge([
                'rol_id' => (int) $this->rol_id
            ]);
        }
        
        if ($this->has('usuario_id') && $this->usuario_id) {
            $this->merge([
                'usuario_id' => (int) $this->usuario_id
            ]);
        }
        
        if ($this->has('orden') && $this->orden) {
            $this->merge([
                'orden' => (int) $this->orden
            ]);
        }
    }
}