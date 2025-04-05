<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();


       $permissions = [
        'cargos' => [
            'criar',
            'visualizar',
            'editar',
            'excluir',
        ],
        'funcionarios' => [
            'registrar',
            'editar',
            'visualizar',
            'excluir',
        ],
        'digitais' => [
            'registrar',
            'excluir',
        ],
        'usuarios' => [
            'registrar',
            'visualizar',
            'editar',
            'excluir',
        ],
        'ponto' => [
            'registrar',
            'visualizar',
            'visualizar_dados',
        ],
        'unidades' => [
            'criar',
            'atualizar',
            'visualizar',
            'excluir',
        ],
        'dias_nao_uteis' => [
            'registrar',
            'atualizar',
            'visualizar',
            'excluir',
        ],
        'localidades' => [
            'criar',
            'atualizar',
            'visualizar',
            'excluir',
        ],
        'justificativas' => [
            'registrar',
            'visualizar',
            'editar',
            'excluir',
        ],
        'ferias' => [
            'registrar',
            'visualizar',
            'excluir',
        ],
        'recessos' => [
            'registrar',
            'visualizar',
            'excluir',
        ],
        'relatorios' => [
            'gerar',
        ],
        'biometria' => [
            'registrar',
        ],
        'setor' => [
            'gerenciar'
        ]
    ];

        foreach ($permissions as $module => $actions)
        {
           foreach ($actions as $action)
           {

            Permission::create(['name' => "{$action}_{$module}"]);

            }
        }
    }
}
