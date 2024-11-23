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
            'visualizar_local',
            'visualizar_global',
            'excluir',
        ],
        'digitais' => [
            'registrar_local',
            'registrar_global',
            'excluir_local',
            'excluir_global',
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
            'visualizar_dados_local',
            'visualizar_dados_global',
        ],
        'relacao_funcionarios' => [
            'visualizar_local',
            'visualizar_global',
        ],
        'unidades' => [
            'criar',
            'atualizar',
            'visualizar',
            'excluir',
        ],
        'justificativas' => [
            'registrar',
            'aceitar_recusar',
            'visualizar_local',
            'visualizar_global',
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
