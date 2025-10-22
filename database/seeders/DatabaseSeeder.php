<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesDisponiblesSeeder::class,
            ConfiguracionesSistemaSeeder::class,
            ConfiguracionRegistroSeeder::class,
            AdminUserSeeder::class,
            EstudiantesSeeder::class,
            InstructoresSeeder::class,
            DialogoJuicioPenalSeeder::class,
            RolesDialogoSeeder::class,
            DialogoRoboOXXOCompletoSeeder::class, // Nuevo seeder del caso OXXO
        ]);
    }
}
