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
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->date('data_nascimento');
            $table->string('cpf')->unique();
            $table->string('vinculo');
            $table->integer('carga_horaria');
            $table->date('data_admissao');
            $table->decimal('salario_base', 10, 2);
            $table->string('foto')->nullable();
            $table->foreignId('cargo_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('unidade_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};
