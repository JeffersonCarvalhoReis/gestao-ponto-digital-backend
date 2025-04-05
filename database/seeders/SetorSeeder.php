<?php

namespace Database\Seeders;

use App\Models\Setor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setor::create(['nome' => 'Atenção Básica']);
        Setor::create(['nome' => 'Hospital Municipal Amélia de Carvalho']);
    }
}
