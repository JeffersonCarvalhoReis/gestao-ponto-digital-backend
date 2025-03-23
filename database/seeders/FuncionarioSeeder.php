<?php
namespace Database\Seeders;

use App\Models\DadosContrato;
use App\Models\Funcionario;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class FuncionarioSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        for ($unidade = 1; $unidade <= 11; $unidade++) {
            for ($i = 1; $i <= 50; $i++) {
                $cpf = $faker->unique()->cpf(false); // false para vir sem máscara

                $funcionario = Funcionario::create([
                    'nome' => $faker->name,
                    'data_nascimento' => $faker->date('Y-m-d', '-18 years'),
                    'status' => $faker->boolean(85),
                    'cpf' => $cpf,
                    'cargo_id' => rand(1, 6),
                    'unidade_id' => $unidade,
                ]);

                DadosContrato::create([
                    'vinculo' => $faker->randomElement(['Contratado', 'Efetivo', 'Comissionado']),
                    'carga_horaria' => $faker->randomElement([20, 30, 40]),
                    'data_admissao' => $faker->date('Y-m-d', '-5 years'),
                    'salario_base' => $faker->randomFloat(2, 1200, 5000),
                    'funcionario_id' => $funcionario->id,
                ]);
            }
        }
    }
}


