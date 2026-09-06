<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distingue no histórico se a ação foi uma correção normal (ajuste de
     * horário) ou um arquivamento em massa (fechado sem apurar horas,
     * usado para dar vazão a um grande volume de registros antigos
     * travados antes desta funcionalidade existir).
     */
    public function up(): void
    {
        Schema::table('registro_ponto_edicoes', function (Blueprint $table) {
            $table->string('tipo')->default('correcao')->after('registro_ponto_id');
        });
    }

    public function down(): void
    {
        Schema::table('registro_ponto_edicoes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
