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
        // User::factory(10)->create();
        //   Criar localidades
        //   $localidades = \App\Models\Localidade::factory(5)->create();

        //   // Criar cargos
        //   $cargos = \App\Models\Cargo::factory(10)->create();

        //   // Criar unidades associadas a localidades
        //   $unidades = \App\Models\Unidade::factory(10)->create([
        //       'localidade_id' => $localidades->random()->id, // Escolhe uma localidade aleatória
        //   ]);

        //   // Criar funcionários associados a unidades e cargos
        //   $funcionarios = \App\Models\Funcionario::factory(50)->create([
        //       'unidade_id' => $unidades->random()->id, // Escolhe uma unidade aleatória
        //       'cargo_id' => $cargos->random()->id, // Escolhe um cargo aleatório
        //   ]);

        //   // Criar biometria para cada funcionário
        //   $funcionarios->each(function ($funcionario) {
        //       \App\Models\Biometria::factory()->create([
        //           'funcionario_id' => $funcionario->id,
        //       ]);
        //   });

        //   $funcionarios->each(function ($funcionario) {
        //     \App\Models\DadosContrato::factory()->create([
        //         'funcionario_id' => $funcionario->id,
        //     ]);
        // });


        //   // Criar justificativas para alguns funcionários (opcional)
        //   \App\Models\Justificativa::factory(15)->create([
        //       'funcionario_id' => $funcionarios->random()->id, // Escolhe funcionários aleatórios
        //   ]);

        //   // Criar registros de ponto para funcionários
        //   \App\Models\RegistroPonto::factory(100)->create([
        //       'funcionario_id' => $funcionarios->random()->id, // Escolhe funcionários aleatórios
        //   ]);

        //   // Criar usuários associados às unidades
        //   User::factory(15)->create([
        //       'unidade_id' => $unidades->random()->id, // Escolhe unidades aleatórias
        //   ]);
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'password' => '123'
        // ]);
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            LocalidadesSeeder::class,
            CargosSeeder::class,
            UnidadesSeeder::class,
            FuncionarioSeeder::class,
            UsersSeeder::class,
        ]);
    }
}
