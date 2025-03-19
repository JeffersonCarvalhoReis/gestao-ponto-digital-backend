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
            'nome' => 'Hospital Municipal Amélia de Carvalho',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'Caps Lar da Esperança',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'UBSF Breno de Carvalho Oliveira',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'Secretaria Municipal de Saúde de Itaguaçu da Bahia',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'Centro de Referência',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'EAP Maravilha',
            'localidade_id' => 5,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'PSF Manoel Nogueira dos Santos Filho Badéu',
            'localidade_id' => 2,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'UBSF Antônio Felicidade',
            'localidade_id' => 3,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'UBSF Sede',
            'localidade_id' => 1,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'UBSF Almas',
            'localidade_id' => 4,
            'cnes' => null
        ]);
        Unidade::create([
            'nome' => 'UBSF Lages',
            'localidade_id' => 5,
            'cnes' => null
        ]);
    }
}
