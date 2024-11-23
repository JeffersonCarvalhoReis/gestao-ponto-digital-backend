<?php

namespace Database\Factories;

use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Justificativa>
 */
class JustificativaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'motivo' => $this->faker->sentence(),
            'anexo' => $this->faker->imageUrl(),
            'status' => $this->faker->randomElement(['pendente', 'aprovada', 'reprovada']),
            'funcionario_id' => null,
        ];
    }
}
