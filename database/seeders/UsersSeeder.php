<?php

namespace Database\Seeders;

use App\Models\unidade;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $admin =  User::create([
            'name'=> 'admin',
            'email' => 'admin@email.com',
            'password' => '123'
        ]);

        $admin->assignRole('admin');

        $gestor = User::create([
            'name'=> 'gestor',
            'email' => 'gestor@email.com',
            'password' => '123',
            'unidade_id' => '1'
        ]);

        $gestor->assignRole('gestor');

        $user = User::create([
            'name'=> 'user',
            'email' => 'user@email.com',
            'password' => '123',
            'unidade_id' => '2'
        ]);

        $user->assignRole('user');

        $superAdmin = User::create([
            'name'=> 'Super Admin',
            'email' => 'superadmin@email.com',
            'password' => '123'
        ]);

        $superAdmin->assignRole('super admin');
    }
}
