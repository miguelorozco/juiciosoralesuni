<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RolDisponible>
 */
class RolDisponibleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icono' => fake()->randomElement(['person', 'gavel', 'balance', 'book']),
            'activo' => true,
            'orden' => fake()->numberBetween(0, 100),
        ];
    }
}
