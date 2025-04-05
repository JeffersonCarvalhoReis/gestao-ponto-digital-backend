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
            'nome' => 'Sede',
            'setor_id' => 1
        ]);
        Localidade::create([
            'nome' => 'Sede',
            'setor_id' => 2
        ]);
        Localidade::create([
            'nome' => 'Barreiros',
            'setor_id' => 1
        ]);
        Localidade::create([
            'nome' => 'Rio Verde',
            'setor_id' => 1
        ]);
        Localidade::create([
            'nome' => 'Almas',
            'setor_id' => 1
        ]);
        Localidade::create([
            'nome' => 'Lages',
            'setor_id' => 1
        ]);
        Localidade::create([
            'nome' => 'Maravilha',
            'setor_id' => 1
        ]);
    }
    }
