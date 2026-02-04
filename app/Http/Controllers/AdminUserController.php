<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    /**
     * Create a new user (Admin only)
     */
    public function createUser(RegisterRequest $request): JsonResponse
    {
        // Los administradores pueden crear usuarios incluso si el registro está bloqueado
        // El modelo User tiene el cast 'hashed' para password
        $user = User::create([
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => $request->password,
            'tipo' => $request->tipo,
            'activo' => true,
            'email_verified_at' => now(),
            'creado_por' => auth()->id(), // Registrar quién creó el usuario
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Usuario creado exitosamente por administrador'
        ], 201);
    }

    /**
     * Get registration status
     */
    public function getRegistrationStatus(): JsonResponse
    {
        $registroHabilitado = \App\Models\ConfiguracionSistema::where('clave', 'registro_usuarios_habilitado')
            ->first();

        $mensajeBloqueo = \App\Models\ConfiguracionSistema::where('clave', 'mensaje_registro_bloqueado')
            ->first();

        $emailContacto = \App\Models\ConfiguracionSistema::where('clave', 'email_contacto_admin')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'registro_habilitado' => $registroHabilitado ? (bool) $registroHabilitado->valor : false,
                'mensaje_bloqueo' => $mensajeBloqueo ? $mensajeBloqueo->valor : 'El registro está temporalmente deshabilitado.',
                'email_contacto' => $emailContacto ? $emailContacto->valor : 'admin@juiciosorales.site',
            ]
        ]);
    }

    /**
     * Toggle registration status (Admin only)
     */
    public function toggleRegistrationStatus(): JsonResponse
    {
        $registroHabilitado = \App\Models\ConfiguracionSistema::where('clave', 'registro_usuarios_habilitado')
            ->first();

        $nuevoEstado = !$registroHabilitado || $registroHabilitado->valor === 'false';

        \App\Models\ConfiguracionSistema::updateOrCreate(
            ['clave' => 'registro_usuarios_habilitado'],
            [
                'valor' => $nuevoEstado ? 'true' : 'false',
                'actualizado_por' => auth()->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'registro_habilitado' => $nuevoEstado,
                'mensaje' => $nuevoEstado ? 'Registro de usuarios habilitado' : 'Registro de usuarios deshabilitado'
            ]
        ]);
    }
}
