<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Secrets\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (file_exists(base_path('private/UserSeeder.php'))) {
            require_once base_path('private/UserSeeder.php');
            $this->call([
                PermissionsSeeder::class,
                RolesSeeder::class,
                SetorSeeder::class,
                LocalidadesSeeder::class,
                UnidadesSeeder::class,
                UserSeeder::class,
            ]);
        } else {
            $this->call([
                PermissionsSeeder::class,
                RolesSeeder::class,
                SetorSeeder::class,
                LocalidadesSeeder::class,
                CargosSeeder::class,
                UnidadesSeeder::class,
                FuncionarioSeeder::class,
                UsersSeeder::class,
            ]);
        }


    }
}
