<?php

namespace Database\Seeders;

use App\Models\Funcionario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FuncionarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Funcionario::create([
            'nome' => 'João Silva',
            'data_nascimento' => '1995-10-02',
            'cpf' => '12345678901',
            'cargo_id' => 1,
            'unidade_id' => 1,
        ]);
        Funcionario::create([
            'nome' => 'Maria Andrade',
            'data_nascimento' => '1989-12-14',
            'cpf' => '45465456',
            'cargo_id' => 2,
            'unidade_id' => 2,
        ]);
        Funcionario::create([
            'nome' => 'Sebastião Machado',
            'data_nascimento' => '1965-09-29',
            'cpf' => '455545465488',
            'cargo_id' => 2,
            'unidade_id' => 2,
        ]);
    }
}
