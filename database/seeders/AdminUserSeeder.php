<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador específico
        User::updateOrCreate(
            ['email' => 'miguel.orozco@me.com'],
            [
                'name' => 'Miguel',
                'apellido' => 'Orozco',
                'email' => 'miguel.orozco@me.com',
                'password' => Hash::make('m1gu314ng31'),
                'tipo' => 'admin',
                'activo' => true,
                'email_verified_at' => now(),
                'creado_por' => null, // Usuario raíz del sistema
            ]
        );

        // Crear usuario administrador genérico como respaldo
        User::updateOrCreate(
            ['email' => 'admin@juiciosorales.site'],
            [
                'name' => 'Administrador',
                'apellido' => 'Sistema',
                'email' => 'admin@juiciosorales.site',
                'password' => Hash::make('password'),
                'tipo' => 'admin',
                'activo' => true,
                'email_verified_at' => now(),
                'creado_por' => null,
            ]
        );
    }
}
