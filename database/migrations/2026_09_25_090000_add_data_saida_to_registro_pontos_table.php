<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "hora_saida" é uma coluna TIME (sem data). Até aqui, o sistema
     * "adivinhava" que a saída era no dia seguinte sempre que a hora da
     * saída era menor ou igual à hora da entrada — o que funciona para a
     * maioria dos plantões, mas quebra em turnos com mais de 24h em que a
     * hora da saída (ex: 16:00 do dia seguinte) é maior que a hora da
     * entrada (ex: 15:00): o sistema calculava como se fossem apenas 1h de
     * trabalho, no mesmo dia.
     *
     * "data_saida" guarda a data real da saída explicitamente, sem
     * depender de adivinhação. É nullable e preenchida a partir de agora
     * (novos registros e correções); registros antigos continuam usando a
     * regra antiga como fallback (ver App\Models\RegistroPonto::duracaoEmMinutos).
     */
    public function up(): void
    {
        Schema::table('registro_pontos', function (Blueprint $table) {
            $table->date('data_saida')->nullable()->after('hora_saida');
        });
    }

    public function down(): void
    {
        Schema::table('registro_pontos', function (Blueprint $table) {
            $table->dropColumn('data_saida');
        });
    }
};
