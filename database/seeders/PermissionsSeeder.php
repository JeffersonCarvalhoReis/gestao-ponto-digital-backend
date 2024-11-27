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
        'dados_contratos' => [
            'registrar',
            'editar',
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
        'relacao_funcionarios' => [
            'visualizar',
        ],
        'unidades' => [
            'criar',
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
