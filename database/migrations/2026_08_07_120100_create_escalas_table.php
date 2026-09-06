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
        Schema::create('escalas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onDelete('cascade');
            $table->date('data');
            $table->foreignId('tipo_turno_id')->constrained('tipos_turno');
            // Anotações do dia vistas nas planilhas atuais: "regulação",
            // "remanejado", "farmácia hospitalar", "treinamento" etc.
            $table->string('observacao')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Um funcionário só pode ter um código de turno por dia.
            $table->unique(['funcionario_id', 'data']);
            $table->index(['data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalas');
    }
};
