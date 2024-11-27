<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DadosContrato>
 */
class DadosContratoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vinculo' => $this->faker->randomElement(['CLT', 'PJ', 'Estagiário']),
            'carga_horaria' => $this->faker->numberBetween(20, 40),
            'data_admissao' => $this->faker->date(),
            'salario_base' => $this->faker->randomFloat(2, 2000, 10000),
            'funcionario_id' => null,
        ];
    }
}
