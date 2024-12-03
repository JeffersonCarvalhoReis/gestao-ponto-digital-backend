<?php

namespace Database\Seeders;

use App\Models\Localidade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocalidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Localidade::create([
            'nome' => 'Sede'
        ]);
        Localidade::create([
            'nome' => 'Barreiros'
        ]);
        Localidade::create([
            'nome' => 'Rio Verde'
        ]);
    }
}
