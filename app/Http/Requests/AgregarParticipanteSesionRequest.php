<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgregarParticipanteSesionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden agregar participantes
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
            'usuario_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'rol_id' => [
                'required',
                'integer',
                'exists:roles_disponibles,id'
            ],
            'notas' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'rol_id.exists' => 'El rol seleccionado no existe.',
            
            'notas.string' => 'Las notas deben ser una cadena de texto.',
            'notas.max' => 'Las notas no pueden tener más de 500 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'usuario_id' => 'ID del usuario',
            'rol_id' => 'ID del rol',
            'notas' => 'notas'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar que el usuario esté activo
            if ($this->has('usuario_id')) {
                $usuario = \App\Models\User::find($this->usuario_id);
                if ($usuario && !$usuario->activo) {
                    $validator->errors()->add('usuario_id', 'El usuario seleccionado está inactivo.');
                }
            }
            
            // Verificar que el rol esté activo
            if ($this->has('rol_id')) {
                $rol = \App\Models\RolDisponible::find($this->rol_id);
                if ($rol && !$rol->activo) {
                    $validator->errors()->add('rol_id', 'El rol seleccionado está inactivo.');
                }
            }
            
            // Verificar que el usuario no esté ya asignado a la sesión
            if ($this->has('usuario_id')) {
                $sesion = $this->route('sesionJuicio');
                $usuarioExistente = $sesion->asignaciones()->where('usuario_id', $this->usuario_id)->first();
                if ($usuarioExistente) {
                    $validator->errors()->add('usuario_id', 'Este usuario ya está asignado a la sesión.');
                }
            }
            
            // Verificar que el rol no esté ya asignado en la sesión
            if ($this->has('rol_id')) {
                $sesion = $this->route('sesionJuicio');
                $rolExistente = $sesion->asignaciones()->where('rol_id', $this->rol_id)->first();
                if ($rolExistente) {
                    $validator->errors()->add('rol_id', 'Este rol ya está asignado en la sesión.');
                }
            }
            
            // Verificar que la sesión no esté en curso o finalizada
            if ($this->has('usuario_id') || $this->has('rol_id')) {
                $sesion = $this->route('sesionJuicio');
                if ($sesion && !in_array($sesion->estado, ['programada'])) {
                    $validator->errors()->add('sesion', 'Solo se pueden agregar participantes a sesiones programadas.');
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
        if ($this->has('usuario_id')) {
            $this->merge([
                'usuario_id' => (int) $this->usuario_id
            ]);
        }
        
        if ($this->has('rol_id')) {
            $this->merge([
                'rol_id' => (int) $this->rol_id
            ]);
        }
        
        if ($this->has('notas')) {
            $this->merge([
                'notas' => trim($this->notas)
            ]);
        }
    }
}