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
        Schema::create('dados_contratos', function (Blueprint $table) {
            $table->id();
            $table->string('vinculo');
            $table->integer('carga_horaria');
            $table->date('data_admissao');
            $table->decimal('salario_base', 10, 2);
            $table->foreignId('funcionario_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dados_contratos');
    }
};
