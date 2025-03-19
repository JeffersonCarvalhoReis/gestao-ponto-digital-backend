<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cargo::create([
            'nome' => 'Recepcionista'
        ]);
        Cargo::create([
            'nome' => 'Auxiliar de serviços gerais'
        ]);
        Cargo::create([
            'nome' => 'Coordenador'
        ]);
        Cargo::create([
            'nome' => 'Digitador'
        ]);
        Cargo::create([
            'nome' => 'Enfermeiro'
        ]);
        Cargo::create([
            'nome' => 'Cozinheiro'
        ]);
    }
}
