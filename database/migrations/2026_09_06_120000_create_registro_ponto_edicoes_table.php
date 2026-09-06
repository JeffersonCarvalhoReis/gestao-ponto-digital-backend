<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de auditoria das correções manuais feitas por administradores
     * em registros de ponto (ex: turnos que passaram do limite de segurança
     * e ficaram travados "em aberto"). Guarda sempre os valores anteriores
     * e novos, quem editou e o motivo — nunca sobrescreve sem deixar rastro,
     * justamente para que essa tela não vire uma porta para fraude.
     */
    public function up(): void
    {
        Schema::create('registro_ponto_edicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_ponto_id')->constrained('registro_pontos')->onDelete('cascade');
            $table->foreignId('editado_por_id')->constrained('users')->onDelete('cascade');
            $table->date('data_local_anterior')->nullable();
            $table->time('hora_entrada_anterior')->nullable();
            $table->time('hora_saida_anterior')->nullable();
            $table->date('data_local_nova')->nullable();
            $table->time('hora_entrada_nova')->nullable();
            $table->time('hora_saida_nova')->nullable();
            $table->text('motivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_ponto_edicoes');
    }
};
