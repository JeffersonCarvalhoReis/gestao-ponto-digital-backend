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
        Schema::table('localidades', function (Blueprint $table) {
            $table->unsignedBigInteger('setor_id');
            $table->foreign('setor_id')->references('id')->on('setores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localidades', function (Blueprint $table) {
            $table->dropForeign(['setor_id']);
            $table->dropColumn('setor_id');
        });
    }
};
