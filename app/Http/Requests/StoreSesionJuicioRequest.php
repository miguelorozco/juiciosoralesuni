<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSesionJuicioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden crear sesiones
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
            'plantilla_id' => [
                'nullable',
                'integer',
                'exists:plantillas_sesiones,id'
            ],
            'max_participantes' => [
                'nullable',
                'integer',
                'min:1',
                'max:50'
            ],
            'configuracion' => [
                'nullable',
                'array'
            ],
            'configuracion.*' => [
                'string'
            ],
            'participantes' => [
                'nullable',
                'array',
                'max:20' // Máximo 20 participantes
            ],
            'participantes.*.usuario_id' => [
                'required',
                'integer',
                'exists:users,id',
                'distinct' // No duplicados
            ],
            'participantes.*.rol_id' => [
                'required',
                'integer',
                'exists:roles_disponibles,id',
                'distinct' // No duplicados
            ],
            'participantes.*.notas' => [
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
            'nombre.required' => 'El nombre de la sesión es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede tener más de 200 caracteres.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios, guiones y guiones bajos.',
            
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            
            'plantilla_id.integer' => 'El ID de la plantilla debe ser un número entero.',
            'plantilla_id.exists' => 'La plantilla seleccionada no existe.',
            
            'max_participantes.integer' => 'El máximo de participantes debe ser un número entero.',
            'max_participantes.min' => 'El máximo de participantes no puede ser menor a 1.',
            'max_participantes.max' => 'El máximo de participantes no puede ser mayor a 50.',
            
            'configuracion.array' => 'La configuración debe ser un objeto JSON válido.',
            'configuracion.*.string' => 'Cada elemento de la configuración debe ser una cadena de texto.',
            
            'participantes.array' => 'Los participantes deben ser un array válido.',
            'participantes.max' => 'No puede incluir más de 20 participantes.',
            
            'participantes.*.usuario_id.required' => 'El ID del usuario es obligatorio.',
            'participantes.*.usuario_id.integer' => 'El ID del usuario debe ser un número entero.',
            'participantes.*.usuario_id.exists' => 'El usuario seleccionado no existe.',
            'participantes.*.usuario_id.distinct' => 'No puede incluir el mismo usuario dos veces.',
            
            'participantes.*.rol_id.required' => 'El ID del rol es obligatorio.',
            'participantes.*.rol_id.integer' => 'El ID del rol debe ser un número entero.',
            'participantes.*.rol_id.exists' => 'El rol seleccionado no existe.',
            'participantes.*.rol_id.distinct' => 'No puede incluir el mismo rol dos veces.',
            
            'participantes.*.notas.string' => 'Las notas deben ser una cadena de texto.',
            'participantes.*.notas.max' => 'Las notas no pueden tener más de 500 caracteres.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la sesión',
            'descripcion' => 'descripción',
            'plantilla_id' => 'ID de la plantilla',
            'max_participantes' => 'máximo de participantes',
            'configuracion' => 'configuración',
            'participantes' => 'participantes',
            'participantes.*.usuario_id' => 'ID del usuario',
            'participantes.*.rol_id' => 'ID del rol',
            'participantes.*.notas' => 'notas'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validaciones adicionales personalizadas
            if ($this->has('participantes') && is_array($this->participantes)) {
                // Verificar que todos los roles estén activos
                $rolIds = collect($this->participantes)->pluck('rol_id')->toArray();
                $rolesActivos = \App\Models\RolDisponible::whereIn('id', $rolIds)
                    ->where('activo', true)
                    ->pluck('id')
                    ->toArray();
                
                $rolesInactivos = array_diff($rolIds, $rolesActivos);
                if (!empty($rolesInactivos)) {
                    $validator->errors()->add('participantes', 'Algunos roles seleccionados están inactivos: ' . implode(', ', $rolesInactivos));
                }
                
                // Verificar que los usuarios estén activos
                $usuarioIds = collect($this->participantes)->pluck('usuario_id')->toArray();
                $usuariosActivos = \App\Models\User::whereIn('id', $usuarioIds)
                    ->where('activo', true)
                    ->pluck('id')
                    ->toArray();
                
                $usuariosInactivos = array_diff($usuarioIds, $usuariosActivos);
                if (!empty($usuariosInactivos)) {
                    $validator->errors()->add('participantes', 'Algunos usuarios seleccionados están inactivos: ' . implode(', ', $usuariosInactivos));
                }
            }
            
            // Verificar que la plantilla sea pública o del usuario actual
            if ($this->has('plantilla_id') && $this->plantilla_id) {
                $plantilla = \App\Models\PlantillaSesion::find($this->plantilla_id);
                if ($plantilla && !$plantilla->puedeSerUsadaPor(auth()->user())) {
                    $validator->errors()->add('plantilla_id', 'No tiene permisos para usar esta plantilla.');
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
        
        // Asegurar que max_participantes sea integer
        if ($this->has('max_participantes')) {
            $this->merge([
                'max_participantes' => (int) $this->max_participantes
            ]);
        }
    }
}