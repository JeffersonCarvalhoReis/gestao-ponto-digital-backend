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
            'foto' => $this->faker->imageUrl(),
            'unidade_id' => null,
            'cargo_id' => null,
        ];
    }
}
