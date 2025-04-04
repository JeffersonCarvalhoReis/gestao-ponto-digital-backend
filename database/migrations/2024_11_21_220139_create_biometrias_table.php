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
        Schema::create('biometrias', function (Blueprint $table) {
            $table->id();
            $table->text('template');
            $table->foreignId('funcionario_id')->constrained()->onDelete('cascade');
            $table->unique('funcionario_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometrias');
    }
};
