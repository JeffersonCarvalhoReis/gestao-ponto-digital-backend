<?php

namespace Database\Seeders;

use App\Models\Unidade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unidade::create([
            'nome' => 'Hospital',
            'localidade_id' => 1,
            'cnes' => '123456789'
        ]);
        Unidade::create([
            'nome' => 'Caps',
            'localidade_id' => 2,
            'cnes' => '987654321'
        ]);
        Unidade::create([
            'nome' => 'Breno',
            'localidade_id' => 3,
            'cnes' => '45465465'
        ]);
    }
}
