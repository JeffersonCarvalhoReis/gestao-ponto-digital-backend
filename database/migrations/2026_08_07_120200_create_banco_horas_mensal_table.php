<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Conforme confirmado com a coordenação (RH/enfermagem): o banco de horas
     * é apurado e fechado mensalmente e NÃO acumula saldo de um mês para o
     * outro. Por isso a modelagem é uma linha de fechamento por funcionário
     * e por mês (e não um livro-razão infinito de créditos/débitos).
     */
    public function up(): void
    {
        Schema::create('banco_horas_mensal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onDelete('cascade');
            // Sempre o primeiro dia do mês de referência (ex: 2026-08-01).
            $table->date('mes_referencia');
            $table->integer('minutos_previstos')->default(0);
            $table->integer('minutos_realizados')->default(0);
            // Minutos já identificados como hora extra (dobra/viagem) dentro do mês.
            $table->integer('minutos_extra')->default(0);
            // realizados + extra - previstos
            $table->integer('saldo_minutos')->default(0);
            $table->enum('status', ['aberto', 'fechado'])->default('aberto');
            $table->timestamp('fechado_em')->nullable();
            $table->foreignId('fechado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['funcionario_id', 'mes_referencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banco_horas_mensal');
    }
};
