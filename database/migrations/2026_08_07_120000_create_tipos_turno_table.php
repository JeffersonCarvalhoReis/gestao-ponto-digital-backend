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
        Schema::create('tipos_turno', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10); // Ex: P, MT, SD, M, F, AL, AS
            $table->string('nome'); // Ex: "Plantão 24h", "12h (07h-19h)", "ACCR 12h"
            // Quando setor_id é preenchido, este código sobrescreve o significado
            // genérico do mesmo código para aquele setor (ex: "SD" muda de sentido
            // entre enfermagem, higienização e lavanderia).
            $table->foreignId('setor_id')->nullable()->constrained('setores')->onDelete('cascade');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fim')->nullable();
            // Duração prevista em minutos. Null quando o código não representa
            // um turno de trabalho (ex: folga, área suja/limpa).
            $table->integer('duracao_minutos')->nullable();
            // Se false, o código não entra no cálculo de horas previstas/trabalhadas
            // (ex: F = folga, AL/AS = apenas rotação de local de trabalho).
            $table->boolean('conta_como_trabalho')->default(true);
            // Se true, os minutos deste código já nascem classificados como hora
            // extra no fechamento do banco de horas (ex: dobra, viagem).
            $table->boolean('e_hora_extra')->default(false);
            // Cor em hexadecimal para manter a referência visual usada nas
            // planilhas de escala (facilita a transição para quem já usa Excel).
            $table->string('cor', 7)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['codigo', 'setor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_turno');
    }
};
