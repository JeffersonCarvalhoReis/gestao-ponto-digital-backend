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
               'visualizar_funcionarios',
               'editar_funcionarios',
               'excluir_funcionarios',
               'registrar_dados_contratos',
               'visualizar_dados_contratos',
               'editar_dados_contratos',
               'excluir_dados_contratos',
               'registrar_digitais',
               'excluir_digitais',
               'registrar_usuarios',
               'visualizar_usuarios',
               'editar_usuarios',
               'excluir_usuarios',
               'registrar_ponto',
               'visualizar_ponto',
               'visualizar_ponto',
               'criar_unidades',
               'visualizar_unidades',
               'atualizar_unidades',
               'excluir_unidades',
               'visualizar_localidades',
               'criar_localidades',
               'atualizar_localidades',
               'excluir_localidades',
               'registrar_justificativas',
               'excluir_justificativas',
               'editar_justificativas',
               'visualizar_justificativas',
               'gerar_relatorios',
               'registrar_ferias',
               'excluir_ferias',
               'visualizar_ferias',
               'registrar_recessos',
               'excluir_recessos',
               'visualizar_recessos',
               'criar_dias_nao_uteis',
               'visualizar_dias_nao_uteis',
               'atualizar_dias_nao_uteis',
               'excluir_dias_nao_uteis',
               'registrar_biometria'

            ]);
        });
        $admin->syncPermissions($adminPermissions);

        $gestorPermissions = $permissions->filter(function($permission){
            return in_array($permission->name, [

                'registrar_digitais',
                'excluir_digitais',
                'registrar_ponto',
                'visualizar_ponto',
                'visualizar_ponto',
                'registrar_funcionarios',
                'editar_funcionarios',
                'visualizar_funcionarios',
                'excluir_funcionarios',
                'registrar_justificativas',
                'excluir_justificativas',
                'editar_justificativas',
                'visualizar_justificativas',
                'gerar_relatorios',
                'visualizar_cargos',
                'registrar_biometria'
            ]);
        });

        $gestor->syncPermissions($gestorPermissions);


        $userPermissions = $permissions->filter(function($permission){
            return in_array($permission->name, [

            'registrar_ponto',
            'visualizar_ponto',
            'registrar_justificativas',
            'registrar_justificativas',
            'excluir_justificativas',
            'editar_justificativas',
            'visualizar_justificativas',

            ]);
        });

        $user->syncPermissions($userPermissions);
    }
}
