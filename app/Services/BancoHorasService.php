<?php
namespace App\Services;

use App\Models\BancoHorasMensal;
use App\Models\Escala;
use App\Models\Funcionario;
use App\Models\RegistroPonto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BancoHorasService
{
    /**
     * Calcula (sem persistir) o resumo do banco de horas do mês para um
     * funcionário: previsto, realizado, extra e saldo. Usado tanto pelo
     * fechamento oficial quanto pelo relatório de ponto, que precisa exibir
     * o saldo do mês mesmo antes de o RH "fechar" oficialmente o período.
     */
    public function calcularResumoMes(int $funcionarioId, string $mesReferencia): array
    {
        $inicio = Carbon::parse($mesReferencia)->startOfMonth();
        $fim    = Carbon::parse($mesReferencia)->endOfMonth();

        $escalasDoMes = Escala::with('tipoTurno')
            ->where('funcionario_id', $funcionarioId)
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->get();

        $minutosPrevistos = $escalasDoMes
            ->filter(fn($escala) => $escala->tipoTurno->conta_como_trabalho && ! $escala->tipoTurno->e_hora_extra)
            ->sum(fn($escala) => $escala->tipoTurno->duracao_minutos ?? 0);

        $minutosExtra = $escalasDoMes
            ->filter(fn($escala) => $escala->tipoTurno->e_hora_extra)
            ->sum(fn($escala) => $escala->tipoTurno->duracao_minutos ?? 0);

        $minutosRealizados = $this->calcularMinutosRealizados($funcionarioId, $inicio, $fim);

        $saldoMinutos = ($minutosRealizados + $minutosExtra) - $minutosPrevistos;

        return [
            'funcionario_id'      => $funcionarioId,
            'mes_referencia'      => $inicio->toDateString(),
            'minutos_previstos'   => $minutosPrevistos,
            'minutos_realizados'  => $minutosRealizados,
            'minutos_extra'       => $minutosExtra,
            'saldo_minutos'       => $saldoMinutos,
            'previsto_formatado'  => $this->formatarMinutos($minutosPrevistos),
            'realizado_formatado' => $this->formatarMinutos($minutosRealizados),
            'extra_formatado'     => $this->formatarMinutos($minutosExtra),
            'saldo_formatado'     => $this->formatarMinutos($saldoMinutos),
        ];
    }

    /**
     * Calcula o resumo (sem persistir) de vários funcionários de uma vez,
     * indexado por funcionario_id — usado pelo relatório de ponto.
     */
    public function calcularResumoMesEmLote(array $funcionarioIds, string $mesReferencia): array
    {
        $resumos = [];

        foreach ($funcionarioIds as $funcionarioId) {
            $resumos[$funcionarioId] = $this->calcularResumoMes($funcionarioId, $mesReferencia);
        }

        return $resumos;
    }

    /**
     * Fecha o mês de um único funcionário: persiste o resumo calculado e
     * marca como fechado. Conforme confirmado com a coordenação, o saldo
     * NÃO acumula para o mês seguinte: cada fechamento é isolado.
     */
    public function fecharMesFuncionario(int $funcionarioId, string $mesReferencia, int $fechadoPor): BancoHorasMensal
    {
        $resumo = $this->calcularResumoMes($funcionarioId, $mesReferencia);

        return BancoHorasMensal::updateOrCreate(
            ['funcionario_id' => $funcionarioId, 'mes_referencia' => $resumo['mes_referencia']],
            [
                'minutos_previstos'  => $resumo['minutos_previstos'],
                'minutos_realizados' => $resumo['minutos_realizados'],
                'minutos_extra'      => $resumo['minutos_extra'],
                'saldo_minutos'      => $resumo['saldo_minutos'],
                'status'             => 'fechado',
                'fechado_em'         => now(),
                'fechado_por'        => $fechadoPor,
            ]
        );
    }

    /**
     * Fecha o mês de todos os funcionários de um setor de uma vez.
     * Se $unidadeId for informado, restringe o fechamento aos funcionários
     * daquela unidade; caso contrário, fecha o setor inteiro (todas as
     * unidades).
     */
    public function fecharMesSetor(int $setorId, string $mesReferencia, int $fechadoPor, ?int $unidadeId = null): array
    {
        $funcionarios = Funcionario::doSetor($setorId)
            ->where('status', true)
            ->when($unidadeId, function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->orderBy('nome')
            ->pluck('id');

        $resultados = [];

        DB::transaction(function () use ($funcionarios, $mesReferencia, $fechadoPor, &$resultados) {
            foreach ($funcionarios as $funcionarioId) {
                $resultados[] = $this->fecharMesFuncionario($funcionarioId, $mesReferencia, $fechadoPor);
            }
        });

        return $resultados;
    }

    /**
     * Soma os minutos realmente batidos no ponto (biometria ou manual)
     * dentro do período, somando entrada/saída de cada registro.
     */
    private function calcularMinutosRealizados(int $funcionarioId, Carbon $inicio, Carbon $fim): int
    {
        $registros = RegistroPonto::where('funcionario_id', $funcionarioId)
            ->whereBetween('data_local', [$inicio->toDateString(), $fim->toDateString()])
            ->get();

        return $registros->sum(function ($registro) {
            if (! $registro->hora_saida) {
                return 0;
            }

            $entrada = Carbon::parse($registro->hora_entrada);
            $saida   = Carbon::parse($registro->hora_saida);

            // Plantão de 24h pode virar o dia (entrada 07h, saída 07h do dia
            // seguinte) — se a saída for "menor" que a entrada, soma 1 dia.
            if ($saida->lessThanOrEqualTo($entrada)) {
                $saida->addDay();
            }

            return $entrada->diffInMinutes($saida);
        });
    }

    /**
     * Formata minutos (podendo ser negativo) em HH:MM.
     */
    private function formatarMinutos(int $minutos): string
    {
        $sinal      = $minutos < 0 ? '-' : '';
        $minutosAbs = abs($minutos);

        return sprintf('%s%02d:%02d', $sinal, intdiv($minutosAbs, 60), $minutosAbs % 60);
    }

    /**
     * Reabre um fechamento (uso administrativo, caso precise corrigir ponto
     * ou escala depois do fechamento já ter sido feito).
     */
    public function reabrirMes(int $funcionarioId, string $mesReferencia): ?BancoHorasMensal
    {
        $registro = BancoHorasMensal::where('funcionario_id', $funcionarioId)
            ->where('mes_referencia', Carbon::parse($mesReferencia)->startOfMonth()->toDateString())
            ->first();

        $registro?->update([
            'status'      => 'aberto',
            'fechado_em'  => null,
            'fechado_por' => null,
        ]);

        return $registro;
    }
}
