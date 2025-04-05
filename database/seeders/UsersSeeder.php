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
            'user'=> 'admin',
            'password' => '123',
            'unidade_id' => 4,
            'setor_id' => 1,

        ]);

        $admin->assignRole('admin');
       $admin =  User::create([
            'user'=> 'adminhosp',
            'password' => '123',
            'unidade_id' => 1,
            'setor_id' => 2,

        ]);

        $admin->assignRole('admin');

        $gestor1 = User::create([
            'user'=> 'gestor',
            'password' => '123',
            'unidade_id' => 4,
            'setor_id' => 1,
        ]);

        $gestor1->assignRole('gestor');
        $gestor1 = User::create([
            'user'=> 'hospital',
            'password' => '123',
            'unidade_id' => 1,
            'setor_id' => 2,
        ]);

        $gestor1->assignRole('gestor');

        $gestor2 = User::create([
            'user'=> 'barreiros',
            'password' => '123',
            'unidade_id' => 7,
            'setor_id' => 1,
        ]);

        $gestor2->assignRole('gestor');

        $gestor3 = User::create([
            'user'=> 'rioverde',
            'password' => '123',
            'unidade_id' => 8,
            'setor_id' => 1,
        ]);

        $gestor3->assignRole('gestor');

        $almas = User::create([
            'user'=> 'almas',
            'password' => '123',
            'unidade_id' => 10,
            'setor_id' => 1,
        ]);

        $almas->assignRole('gestor');

        $lages = User::create([
            'user'=> 'lages',
            'password' => '123',
            'unidade_id' => 11,
            'setor_id' => 1,
        ]);

        $lages->assignRole('gestor');

        $maravilha = User::create([
            'user'=> 'maravilha',
            'password' => '123',
            'unidade_id' => 6,
            'setor_id' => 1,
        ]);

        $maravilha->assignRole('gestor');

        $user = User::create([
            'user'=> 'user',
            'password' => '123',
            'unidade_id' => 4,
            'setor_id' => 1,
        ]);

        $user->assignRole('user');

        $superAdmin = User::create([
            'user'=> 'Super Admin',
            'password' => '123',
            'unidade_id' => 4,
            'setor_id' => 1,

        ]);

        $superAdmin->assignRole('super admin');
    }
}
