<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setores', function (Blueprint $table) {
            // Quando true, bloqueia o registro de ponto sem biometria
            // (ponto manual) para os funcionários do setor.
            $table->boolean('bloquear_ponto_sem_biometria')->default(false)->after('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setores', function (Blueprint $table) {
            $table->dropColumn('bloquear_ponto_sem_biometria');
        });
    }
};
