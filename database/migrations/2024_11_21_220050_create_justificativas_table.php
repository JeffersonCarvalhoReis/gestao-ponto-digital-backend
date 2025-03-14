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
        Schema::create('justificativas', function (Blueprint $table) {
            $table->id();
            $table->text('motivo');
            $table->text('motivo_recusa')->nullable();
            $table->string('anexo')->nullable();
            $table->date('data');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->enum('status', ['pendente', 'aprovado', 'recusado'])->default('pendente');
            $table->foreignId('funcionario_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificativas');
    }
};
