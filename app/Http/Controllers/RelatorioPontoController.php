<?php

namespace App\Http\Controllers;

use App\Models\DiaNaoUtil;
use App\Models\Feria;
use App\Models\Funcionario;
use App\Models\Justificativa;
use App\Models\Recesso;
use App\Models\RegistroPonto;
use App\Services\DiaNaoUtilService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class RelatorioPontoController extends Controller
{
    protected $diaNaoUtilService;


    public function __construct(DiaNaoUtilService $diaNaoUtilService)
    {
        $this->diaNaoUtilService = $diaNaoUtilService;
        $this->middleware('permission:gerar_relatorios')->only('gerarRelatorio');

    }

    public function gerarRelatorio(Request $request)
    {
        $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'mes' => 'required|date_format:m',
            'ano' => 'required|date_format:Y'
        ]);

        $unidadeId = $request->unidade_id;
        $mes = $request->mes;
        $ano = $request->ano;

        $query = Funcionario::query();

        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id);
        }else {
            $query->where('unidade_id', $unidadeId);
        }

        $funcionarios = $query->get();


        $inicioPeriodo = Carbon::create($ano, $mes, 1)->startOfWeek();
        $fimPeriodo = Carbon::create($ano, $mes)->endOfMonth()->endOfWeek();
        $diasDoMes = CarbonPeriod::create($inicioPeriodo, $fimPeriodo);

        $this->diaNaoUtilService->preencherFinaisDeSemana();
        $this->diaNaoUtilService->preencherFeriados();

        $diasNaoUteis = DiaNaoUtil::whereBetween('data', [$inicioPeriodo, $fimPeriodo])->get();
        $ferias = Feria::whereBetween('data', [$inicioPeriodo, $fimPeriodo])->get();
        $recessos = Recesso::where(function($query) use ($unidadeId){
            $query->where('unidade_id', $unidadeId)
                ->orWhereNull('unidade_id');
        })->whereBetween('data', [$inicioPeriodo, $fimPeriodo])->get();
        $justificativas = Justificativa::whereBetween('data', [$inicioPeriodo, $fimPeriodo])->get();
        $registrosPonto = RegistroPonto::whereBetween('data_local', [$inicioPeriodo, $fimPeriodo])->get();

        $relatorioSemanas = [];
        $relatorioDias = [];

        foreach ($funcionarios as $funcionario) {
            $horasPorSemana = [];
            $funcionarioRelatorioDias = [];

            foreach ($diasDoMes as $dia) {
                $diaRelatorio = $this->consolidarDadosDia(
                    $dia->toDateString(),
                    $recessos,
                    $funcionario->id,
                    $diasNaoUteis,
                    $ferias,
                    $justificativas,
                    $registrosPonto
                );

                $funcionarioRelatorioDias[] = $diaRelatorio;

                $semana = $dia->weekOfMonth;
                if (!isset($horasPorSemana[$semana])) {
                    $horasPorSemana[$semana] = 0;
                }
                $horasPorSemana[$semana] += $diaRelatorio['minutos_trabalhados'];
            }

            $horasPorSemanaFormatado = array_map(function ($minutos) {
                $horas = intdiv($minutos, 60);
                $restoMinutos = $minutos % 60;
                return sprintf('%02d:%02d', $horas, $restoMinutos);
            }, $horasPorSemana);

            $relatorioSemanas[$funcionario->id] = $horasPorSemanaFormatado;
            $relatorioDias[$funcionario->id] = $funcionarioRelatorioDias;
        }

        return response()->json([
            'relatorio_semanas' => $relatorioSemanas,
            'relatorio_dias' => $relatorioDias,
        ], 200);

    }

    function consolidarDadosDia($dia, $recessos, $funcionarioId, $diasNaoUteis, $ferias, $justificativas, $registrosPonto)
     {
        $diaNaoUtil = $diasNaoUteis->firstWhere('data', $dia);
        $recesso = $recessos->firstWhere('data', $dia);
        $feriasFuncionario = $ferias->where('funcionario_id', $funcionarioId)->firstWhere('data', $dia);

        $justificativa = $justificativas->where('funcionario_id', $funcionarioId)->firstWhere('data', $dia);

        $registrosPontoDia = $registrosPonto->filter(function ($registro) use ($funcionarioId, $dia) {
            return $registro->funcionario_id === $funcionarioId &&
                   Carbon::parse($registro->data_local)->toDateString() === $dia;
        });

        $status = 'Falta';
        $minutosTrabalhados = 0;

        if ($registrosPontoDia->isNotEmpty()) {
            $status = 'Presente';

            $minutosTrabalhados = $registrosPontoDia->sum(function ($registro) {
                if($registro->hora_saida){
                    return Carbon::parse($registro->hora_entrada)
                        ->diffInMinutes(Carbon::parse($registro->hora_saida));
                }
                return 0;
            });

        }elseif ($justificativa) {
            $status = match ($justificativa->status) {
                'pendente' => 'Falta',
                'aprovada' => 'Justificado',
                'recusada' => 'Falta',
            };
        } elseif ($diaNaoUtil) {
            $status = 'Dia Não Útil';
        } elseif ($feriasFuncionario) {
            $status = $feriasFuncionario->descricao;
        } elseif($recesso) {
            $status = 'Recesso';
        } elseif ($dia > Carbon::today()->toDateString()) {
            $status = '';
        }

        $horasTrabalhadas = sprintf('%02d:%02d', intdiv($minutosTrabalhados, 60), $minutosTrabalhados % 60);


        return [
            'data' => $dia,
            'status' => $status,
            'minutos_trabalhados' => $minutosTrabalhados,
            'horas_trabalhadas' => $horasTrabalhadas,
            'justificativa' => $justificativa?->motivo,
            'justificativa_status' => $justificativa?->status,
            'descricao_dia_nao_util' => $diaNaoUtil?->descricao,
        ];
    }


}
