<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Unidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Funcionario>
 */
class FuncionarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'data_nascimento' => $this->faker->date('Y-m-d', '-18 years'),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'vinculo' => $this->faker->randomElement(['CLT', 'PJ', 'Estagiário']),
            'carga_horaria' => $this->faker->numberBetween(20, 40),
            'data_admissao' => $this->faker->date(),
            'salario_base' => $this->faker->randomFloat(2, 2000, 10000),
            'foto' => $this->faker->imageUrl(),
            'unidade_id' => null,
            'cargo_id' => null,
        ];
    }
}
