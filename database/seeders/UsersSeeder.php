<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
            'password' => '123',
            'unidade_id' => '1'

        ]);

        $admin->assignRole('admin');

        $gestor1 = User::create([
            'name'=> 'gestor',
            'email' => 'gestor@email.com',
            'password' => '123',
            'unidade_id' => '1'
        ]);

        $gestor1->assignRole('gestor');

        $gestor2 = User::create([
            'name'=> 'barreiros',
            'email' => 'barreiros@email.com',
            'password' => '123',
            'unidade_id' => '2'
        ]);

        $gestor2->assignRole('gestor');

        $gestor3 = User::create([
            'name'=> 'rioverde',
            'email' => 'rioverde@email.com',
            'password' => '123',
            'unidade_id' => '3'
        ]);

        $gestor3->assignRole('gestor');

        $almas = User::create([
            'name'=> 'almas',
            'email' => 'almas@email.com',
            'password' => '123',
            'unidade_id' => '4'
        ]);

        $almas->assignRole('gestor');

        $lages = User::create([
            'name'=> 'lages',
            'email' => 'lages@email.com',
            'password' => '123',
            'unidade_id' => '3'
        ]);

        $lages->assignRole('gestor');

        $maravilha = User::create([
            'name'=> 'maravilha',
            'email' => 'maravilha@email.com',
            'password' => '123',
            'unidade_id' => '3'
        ]);

        $maravilha->assignRole('gestor');

        $user = User::create([
            'name'=> 'user',
            'email' => 'user@email.com',
            'password' => '123',
            'unidade_id' => '1'
        ]);

        $user->assignRole('user');

        $superAdmin = User::create([
            'name'=> 'Super Admin',
            'email' => 'superadmin@email.com',
            'password' => '123',
            'unidade_id' => '1'

        ]);

        $superAdmin->assignRole('super admin');
    }
}
