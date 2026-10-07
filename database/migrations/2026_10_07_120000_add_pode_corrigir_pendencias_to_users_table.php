<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite que um administrador libere, usuário a usuário, o acesso de um
     * gestor à tela de "Pendências de Correção de Ponto". Por padrão ninguém
     * (além de admin/super admin) tem esse acesso. A flag só tem efeito para
     * usuários com a função "gestor"; o escopo do gestor fica limitado à
     * unidade à qual ele pertence.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pode_corrigir_pendencias')->default(false)->after('setor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pode_corrigir_pendencias');
        });
    }
};
