<?php

namespace Database\Factories;

use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegistroPonto>
 */
class RegistroPontoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hora_entrada' => $this->faker->time(),
            'hora_saida' => $this->faker->time(),
            'funcionario_id' => null,
        ];
    }
}
