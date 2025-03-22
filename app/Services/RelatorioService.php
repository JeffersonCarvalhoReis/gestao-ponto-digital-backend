<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Log;

class RelatorioService
{
    /**
     * Processa os dados e gera o relatório completo
     *
     * @param Collection $funcionarios
     * @param \Carbon\CarbonPeriod $periodo
     * @param array $dadosRelatorio
     * @return array
     */
    public function processarRelatorio($funcionarios, $periodo, $dadosRelatorio)
    {
        $relatorioSemanas = [];
        $relatorioDias = [];

        foreach ($funcionarios as $funcionario) {
            $resultado = $this->processarDadosFuncionario(
                $funcionario,
                $periodo,
                $dadosRelatorio
            );

            $relatorioSemanas[$funcionario->nome] = $resultado['horasPorSemana'];

            $relatorioDias[$funcionario->nome] = $resultado['relatorioDias'];

        }

        return [
            'relatorio_semanas' => $relatorioSemanas,
            'relatorio_dias' => $relatorioDias,
        ];
    }

    /**
     * Processa os dados de um funcionário específico
     *
     * @param \App\Models\Funcionario $funcionario
     * @param \Carbon\CarbonPeriod $periodo
     * @param array $dadosRelatorio
     * @return array
     */
    private function processarDadosFuncionario($funcionario, $periodo, $dadosRelatorio)
    {
        $horasPorSemana = [];
        $relatorioDias = [];

        foreach ($periodo as $dia) {
            $diaRelatorio = $this->consolidarDadosDia(
                $dia->toDateString(),
                $funcionario->id,
                $dadosRelatorio
            );

            $relatorioDias[] = $diaRelatorio;

            // Acumula horas por semana
            $semana = $dia->weekOfMonth;
            if (!isset($horasPorSemana[$semana])) {
                $horasPorSemana[$semana] = 0;
            }
            $horasPorSemana[$semana] += $diaRelatorio['minutos_trabalhados'];
        }

        // Formata as horas por semana
        $horasPorSemanaFormatado = $this->formatarHorasPorSemana($horasPorSemana);

        return [
            'horasPorSemana' => $horasPorSemanaFormatado,
            'relatorioDias' => $relatorioDias
        ];
    }

    /**
     * Formata os minutos trabalhados por semana para o formato HH:MM
     *
     * @param array $horasPorSemana
     * @return array
     */
    private function formatarHorasPorSemana($horasPorSemana)
    {
        return array_map(function ($minutos) {
            $horas = intdiv($minutos, 60);
            $restoMinutos = $minutos % 60;
            return sprintf('%02d:%02d', $horas, $restoMinutos);
        }, $horasPorSemana);
    }

    /**
     * Consolida os dados de um dia específico para um funcionário
     *
     * @param string $dia
     * @param int $funcionarioId
     * @param array $dadosRelatorio
     * @return array
     */
    public function consolidarDadosDia($dia, $funcionarioId, $dadosRelatorio)
    {
        // Extrai os dados necessários
        $diasNaoUteis = $dadosRelatorio['diasNaoUteis'];
        $recessos = $dadosRelatorio['recessos'];
        $ferias = $dadosRelatorio['ferias'];
        $justificativas = $dadosRelatorio['justificativas'];
        $registrosPonto = $dadosRelatorio['registrosPonto'];

        // Verifica o status do dia
        $diaNaoUtil = $diasNaoUteis->firstWhere('data', $dia);
        $recesso = $recessos->firstWhere('data', $dia);
        $feriasFuncionario = $ferias->where('funcionario_id', $funcionarioId)->firstWhere('data', $dia);
        $justificativa = $justificativas->where('funcionario_id', $funcionarioId)->firstWhere('data', $dia);

        // Filtra registros de ponto do funcionário no dia
        $registrosPontoDia = $this->filtrarRegistrosPontoDia($registrosPonto, $funcionarioId, $dia);

        // Determina o status e calcula as horas trabalhadas
        $resultado = $this->determinarStatusEHoras($registrosPontoDia, $justificativa, $diaNaoUtil, $feriasFuncionario, $recesso, $dia);

        return [
            'data' => $dia,
            'status' => $resultado['status'],
            'minutos_trabalhados' => $resultado['minutosTrabalhados'],
            'horas_trabalhadas' => $resultado['horasTrabalhadas'],
            'biometrico' => $resultado['biometrico'],
            'justificativa' => $justificativa?->motivo,
            'justificativa_status' => $justificativa?->status,
            'descricao_dia_nao_util' => $diaNaoUtil?->descricao,
        ];
    }

    /**
     * Filtra os registros de ponto de um funcionário em um dia específico
     *
     * @param Collection $registrosPonto
     * @param int $funcionarioId
     * @param string $dia
     * @return Collection
     */
    private function filtrarRegistrosPontoDia($registrosPonto, $funcionarioId, $dia)
    {
        return $registrosPonto->filter(function ($registro) use ($funcionarioId, $dia) {
            return $registro->funcionario_id === $funcionarioId &&
                   Carbon::parse($registro->data_local)->toDateString() === $dia;
        });
    }

    /**
     * Determina o status do dia e calcula as horas trabalhadas
     *
     * @param Collection $registrosPontoDia
     * @param \App\Models\Justificativa|null $justificativa
     * @param \App\Models\DiaNaoUtil|null $diaNaoUtil
     * @param \App\Models\Feria|null $feriasFuncionario
     * @param \App\Models\Recesso|null $recesso
     * @param string $dia
     * @return array
     */
    private function determinarStatusEHoras($registrosPontoDia, $justificativa, $diaNaoUtil, $feriasFuncionario, $recesso, $dia)
    {
        $status = 'Falta';
        $minutosTrabalhados = 0;
        $pontoBiometrico = false;


        if ($registrosPontoDia->isNotEmpty()) {
            $status = 'Presente';
            $minutosTrabalhados = $this->calcularMinutosTrabalhados($registrosPontoDia);
            $ponto =$registrosPontoDia->first();
            $pontoBiometrico = $ponto->biometrico;

        } elseif ($feriasFuncionario) {
            $status = $feriasFuncionario->descricao;
        } elseif ($recesso) {
            $status = 'Recesso';
        } elseif ($diaNaoUtil?->tipo === 'feriado') {
            $status = 'Feriado';
        } elseif ($diaNaoUtil?->tipo === 'final_de_semana') {
            $status = 'Final de Semana';
        }  elseif ($justificativa) {
            $status = $this->determinarStatusJustificativa($justificativa);
        } elseif ($dia > Carbon::today()->toDateString()) {
            $status = '';
        }

        $horasTrabalhadas = $this->formatarMinutosEmHoras($minutosTrabalhados);

        return [
            'status' => $status,
            'minutosTrabalhados' => $minutosTrabalhados,
            'horasTrabalhadas' => $horasTrabalhadas,
            'biometrico' => $pontoBiometrico
        ];
    }

    /**
     * Calcula os minutos trabalhados com base nos registros de ponto
     *
     * @param Collection $registrosPontoDia
     * @return int
     */
    private function calcularMinutosTrabalhados($registrosPontoDia)
    {
        return $registrosPontoDia->sum(function ($registro) {
            if ($registro->hora_saida) {
                return Carbon::parse($registro->hora_entrada)
                    ->diffInMinutes(Carbon::parse($registro->hora_saida));
            }
            return 0;
        });
    }

    /**
     * Determina o status com base na justificativa
     *
     * @param \App\Models\Justificativa $justificativa
     * @return string
     */
    private function determinarStatusJustificativa($justificativa)
    {
        return match ($justificativa->status) {
            'pendente' => 'Pendente',
            'aprovado' => 'Justificado',
            'recusado' => 'Falta',
        };
    }

    /**
     * Formata minutos em horas no formato HH:MM
     *
     * @param int $minutos
     * @return string
     */
    private function formatarMinutosEmHoras($minutos)
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    public function mapearStatusParaSigla(string $status): string
{
    return match($status) {
        'Falta' => 'F',
        'Presente' => 'P',
        'Feriado' => 'FR',
        'Final de Semana' => 'FS',
        'Recesso' => 'R',
        'Justificado' => 'J',
        'Pendente' => 'PE',
        'Férias' => 'FE',
        '' => '-',
        default => 'L',
    };
}

}

