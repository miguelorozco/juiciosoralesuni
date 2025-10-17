<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReordenarRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo administradores e instructores pueden reordenar roles
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
            'roles' => [
                'required',
                'array',
                'min:1'
            ],
            'roles.*.id' => [
                'required',
                'integer',
                'exists:roles_disponibles,id',
                'distinct' // No duplicados
            ],
            'roles.*.orden' => [
                'required',
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
            'roles.required' => 'La lista de roles es obligatoria.',
            'roles.array' => 'Los roles deben ser un array válido.',
            'roles.min' => 'Debe incluir al menos 1 rol.',
            
            'roles.*.id.required' => 'El ID del rol es obligatorio.',
            'roles.*.id.integer' => 'El ID del rol debe ser un número entero.',
            'roles.*.id.exists' => 'El rol seleccionado no existe.',
            'roles.*.id.distinct' => 'No puede incluir el mismo rol dos veces.',
            
            'roles.*.orden.required' => 'El orden del rol es obligatorio.',
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
            'roles' => 'roles',
            'roles.*.id' => 'ID del rol',
            'roles.*.orden' => 'orden del rol'
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
                $rolIds = collect($this->roles)->pluck('id')->toArray();
                $rolesActivos = \App\Models\RolDisponible::whereIn('id', $rolIds)
                    ->where('activo', true)
                    ->pluck('id')
                    ->toArray();
                
                $rolesInactivos = array_diff($rolIds, $rolesActivos);
                if (!empty($rolesInactivos)) {
                    $validator->errors()->add('roles', 'Algunos roles seleccionados están inactivos: ' . implode(', ', $rolesInactivos));
                }
                
                // Verificar que no haya órdenes duplicados
                $ordenes = collect($this->roles)->pluck('orden')->toArray();
                if (count($ordenes) !== count(array_unique($ordenes))) {
                    $validator->errors()->add('roles', 'No puede haber órdenes duplicados en los roles.');
                }
                
                // Verificar que todos los roles existan
                $rolesExistentes = \App\Models\RolDisponible::whereIn('id', $rolIds)->pluck('id')->toArray();
                $rolesNoExistentes = array_diff($rolIds, $rolesExistentes);
                if (!empty($rolesNoExistentes)) {
                    $validator->errors()->add('roles', 'Algunos roles no existen: ' . implode(', ', $rolesNoExistentes));
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
        if ($this->has('roles') && is_array($this->roles)) {
            $rolesNormalizados = collect($this->roles)->map(function ($rol) {
                return [
                    'id' => (int) $rol['id'],
                    'orden' => (int) $rol['orden']
                ];
            })->toArray();
            
            $this->merge([
                'roles' => $rolesNormalizados
            ]);
        }
    }
}