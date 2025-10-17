<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfiguracionSistema;

class ConfiguracionRegistroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Configuración para controlar el registro de usuarios
        ConfiguracionSistema::updateOrCreate(
            ['clave' => 'registro_usuarios_habilitado'],
            [
                'valor' => 'false',
                'descripcion' => 'Controla si los usuarios pueden registrarse en el sistema. Solo administradores pueden crear cuentas cuando está deshabilitado.',
                'tipo' => 'boolean',
                'actualizado_por' => null,
            ]
        );

        // Configuración para mensaje de bloqueo
        ConfiguracionSistema::updateOrCreate(
            ['clave' => 'mensaje_registro_bloqueado'],
            [
                'valor' => 'El registro de nuevos usuarios está temporalmente deshabilitado. Solo los administradores pueden crear cuentas. Contacta al administrador del sistema si necesitas acceso.',
                'descripcion' => 'Mensaje que se muestra cuando el registro está bloqueado',
                'tipo' => 'string',
                'actualizado_por' => null,
            ]
        );

        // Configuración para email de contacto
        ConfiguracionSistema::updateOrCreate(
            ['clave' => 'email_contacto_admin'],
            [
                'valor' => 'miguel.orozco@me.com',
                'descripcion' => 'Email de contacto del administrador para solicitudes de acceso',
                'tipo' => 'string',
                'actualizado_por' => null,
            ]
        );
    }
}
