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
        Schema::create('relatorio_pontos', function (Blueprint $table) {
            $table->id();
            $table->time('horas_trabalhadas');
            $table->date('dias_trabalhados');
            $table->date('dias_faltas');
            $table->date('dias_justificados');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatorio_pontos');
    }
};
