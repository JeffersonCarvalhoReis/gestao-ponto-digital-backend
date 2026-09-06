<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NewRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin      = Role::firstOrCreate(['name' => 'admin']);
        $superAdmin = Role::firstOrCreate(['name' => 'super admin']);

        /*
         * Permissões atualmente existentes no PermissionsSeeder
         */
        $permissions = Permission::all();

        /*
         * Super Admin recebe todas as permissões
         */
        $superAdmin->givePermissionTo($permissions);

        /*
         * Novas permissões
         */
        $newPermissions = [
            'registrar_turnos',
            'visualizar_turnos',
            'editar_turnos',
            'excluir_turnos',

            'registrar_escalas',
            'visualizar_escalas',
            'excluir_escalas',

            'visualizar_banco_horas',
            'fechar_banco_horas',
        ];

        /*
         * Admin recebe as novas permissões
         */
        $admin->givePermissionTo(
            Permission::whereIn('name', $newPermissions)->get()
        );
        /*
         * User não recebe automaticamente as novas permissões.
         * Adicione aqui somente se desejar.
         */
    }
}
