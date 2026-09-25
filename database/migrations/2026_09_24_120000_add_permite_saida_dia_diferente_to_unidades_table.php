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
        Schema::table('unidades', function (Blueprint $table) {
            // Quando true (padrão, preserva o comportamento atual), a
            // unidade permite fechar a saída num dia diferente do dia da
            // entrada (plantões que atravessam a meia-noite).
            //
            // Quando false, a saída só pode ser registrada no mesmo dia da
            // entrada; se o funcionário não bateu a saída, o sistema passa
            // a permitir uma nova entrada no dia seguinte mesmo assim (o
            // registro antigo fica em aberto, visível em
            // "Banco de horas > Pendências de Correção de Ponto", para um
            // administrador corrigir manualmente).
            $table->boolean('permite_saida_dia_diferente')->default(true)->after('cnes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn('permite_saida_dia_diferente');
        });
    }
};
