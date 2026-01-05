<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SesionJuicio>
 */
class SesionJuicioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->sentence(3),
            'descripcion' => fake()->paragraph(),
            'tipo' => fake()->randomElement(['civil', 'penal', 'laboral', 'administrativo']),
            'instructor_id' => \App\Models\User::factory(),
            'plantilla_id' => null,
            'estado' => fake()->randomElement(['programada', 'en_curso', 'finalizada', 'cancelada']),
            'fecha_creacion' => now(),
            'fecha_inicio' => now()->addDays(1),
            'fecha_fin' => now()->addDays(2),
            'max_participantes' => fake()->numberBetween(5, 20),
            'configuracion' => [],
            'unity_room_id' => fake()->uuid(),
        ];
    }
}
