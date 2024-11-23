<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        $superAdmin = Role::create(['name' => 'super admin']);
        $gestor = Role::create(['name' => 'gestor']);
        $user = Role::create(['name' => 'user']);

        $permissions = Permission::all();

        $superAdmin->syncPermissions($permissions);

        $adminPermissions = $permissions->filter( function($permission) {
            return in_array($permission->name, [
               'criar_cargos',
               'visualizar_cargos',
               'editar_cargos',
               'excluir_cargos',
               'registrar_funcionarios',
               'visualizar_global_funcionarios',
               'editar_funcionarios',
               'excluir_funcionarios',
               'registrar_global_digitais',
               'excluir_global_digitais',
               'registrar_usuarios',
               'visualizar_usuarios',
               'editar_usuarios',
               'excluir_usuarios',
               'registrar_ponto',
               'visualizar_ponto',
               'visualizar_global_ponto',
               'visualizar_global_relacao_funcionarios',
               'criar_unidades',
               'visualizar_unidades',
               'atualizar_unidades',
               'excluir_unidades',
               'registrar_justificativas',
               'aceitar_recusar_justificativas',
               'visualizar_global_justificativas',

            ]);
        });
        $admin->syncPermissions($adminPermissions);

        $gestorPermissions = $permissions->filter(function($permission){
            return in_array($permission->name, [

                'registrar_local_digitais',
                'excluir_local_digitais',
                'registrar_ponto',
                'visualizar_ponto',
                'visualizar_local_ponto',
                'visualizar_local_relacao_funcionarios',
                'registrar_justificativas',
                'visualizar_local_justificativas',

            ]);
        });

        $gestor->syncPermissions($gestorPermissions);


        $userPermissions = $permissions->filter(function($permission){
            return in_array($permission->name, [

            'registrar_ponto',
            'visualizar_ponto',
            'registrar_justificativas',

            ]);
        });

        $user->syncPermissions($userPermissions);
    }
}
