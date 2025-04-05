<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

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
