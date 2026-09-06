<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Um registro "arquivado" é um turno em aberto (sem hora_saida) que um
     * administrador decidiu encerrar sem apurar horas — por exemplo, um
     * grande volume de registros travados desde antes desta funcionalidade
     * existir, onde não há como saber com confiança o horário real de
     * saída. Diferente de uma correção normal, aqui a hora_saida
     * continua NULL de propósito (não conta como horas trabalhadas) e o
     * registro só some da fila de pendências.
     */
    public function up(): void
    {
        Schema::table('registro_pontos', function (Blueprint $table) {
            $table->timestamp('arquivado_em')->nullable()->after('hora_saida');
            $table->foreignId('arquivado_por_id')->nullable()->after('arquivado_em')->constrained('users')->onDelete('set null');
            $table->text('motivo_arquivamento')->nullable()->after('arquivado_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('registro_pontos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('arquivado_por_id');
            $table->dropColumn(['arquivado_em', 'motivo_arquivamento']);
        });
    }
};
