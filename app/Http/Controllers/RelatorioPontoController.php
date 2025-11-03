<?php
namespace App\Http\Controllers;

use App\Exports\RelatorioPontoExport;
use App\Exports\RelatorioPontoIndividualExport;
use App\Models\DiaNaoUtil;
use App\Models\Feria;
use App\Models\Funcionario;
use App\Models\Justificativa;
use App\Models\Recesso;
use App\Models\RegistroPonto;
use App\Models\Unidade;
use App\Services\DiaNaoUtilService;
use App\Services\RelatorioService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RelatorioPontoController extends Controller
{
    protected $diaNaoUtilService;
    protected $relatorioService;

    public function __construct(DiaNaoUtilService $diaNaoUtilService, RelatorioService $relatorioService)
    {
        $this->diaNaoUtilService = $diaNaoUtilService;
        $this->relatorioService  = $relatorioService;
        $this->middleware('permission:gerar_relatorios')->only('gerarRelatorio');
    }

    public function exportarRelatorioExcel(Request $request)
    {
        $this->validarRequisicao($request);

        $unidadeId = $request->unidade;
        $mes       = $request->mes;
        $ano       = $request->ano;

        $funcionarios = $this->obterFuncionarios($unidadeId);
        $periodo      = $this->definirPeriodoMes($ano, $mes);

        $this->preencherDiasNaoUteis();

        $dadosRelatorio = $this->obterDadosRelatorio($periodo, $unidadeId, $funcionarios);

        $resultado = $this->relatorioService->processarRelatorio($funcionarios, $periodo, $dadosRelatorio);

        $unidade = Unidade::find($unidadeId);

        $cabecalho = ['Funcionário'];
        foreach ($periodo as $data) {
            $cabecalho[] = $data->format('j') . ' ' . ucfirst(substr($data->translatedFormat('D'), 0, 3));
        }
        // Transforma o resultado em uma Collection simples para exportação
        $linhasExportacao = [];

        $relatorioDias      = $resultado['relatorio_dias'];
        $dadosSemanais      = $resultado['relatorio_semanas'];
        $linhasExportacao   = [];
        $linhasExportacao[] = $cabecalho;

        foreach ($relatorioDias as $nomeFuncionario => $registros) {
            foreach ($registros as $registro) {
                $siglaStatus = $this->relatorioService->mapearStatusParaSigla($registro['status']);

                $linhasExportacao[] = [
                    'entrada'                => $registro['entrada'],
                    'saida'                  => $registro['saida'],
                    'funcionario'            => $nomeFuncionario,
                    'data'                   => $registro['data'],
                    'sigla_status'           => $siglaStatus,
                    'horas_trabalhadas'      => $registro['horas_trabalhadas'],
                    'justificativa'          => $registro['justificativa'] ?? '',
                    'status_justificativa'   => $registro['justificativa_status'] ?? '',
                    'descricao_dia_nao_util' => $registro['descricao_dia_nao_util'] ?? '',
                ];
            }
        }

        $linhasExportacaoSemanais = [];
        foreach ($dadosSemanais as $nomeFuncionario => $registros) {
            // Inicializa um array para armazenar os registros do funcionário em uma linha
            if (! isset($linhasExportacaoSemanais[$nomeFuncionario])) {
                $linhasExportacaoSemanais[$nomeFuncionario] = ['funcionario' => $nomeFuncionario];
            }

            // Adiciona os registros como colunas
            foreach ($registros as $index => $registro) {
                $linhasExportacaoSemanais[$nomeFuncionario]["data_$index"] = str_replace(':', 'h', $registro);
            }
        }
        $linhasExportacaoSemanais = array_values($linhasExportacaoSemanais);

        return Excel::download(new RelatorioPontoExport(collect($linhasExportacao), collect($linhasExportacaoSemanais), $ano, $mes, $unidade->nome), 'relatorio_ponto.xlsx');
    }

    /**
     * Gera relatório de ponto dos funcionários
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gerarRelatorio(Request $request)
    {
        $this->validarRequisicao($request);

        $unidadeId = $request->unidade;
        $mes       = $request->mes;
        $ano       = $request->ano;
        // Obtém funcionários com base nas permissões do usuário
        $funcionarios = $this->obterFuncionarios($unidadeId);

        // Define o período do relatório (mês completo + semanas completas)
        $periodo = $this->definirPeriodo($ano, $mes);

        // Preenche dados de dias não úteis se necessário
        $this->preencherDiasNaoUteis();

        // Obtém todos os dados necessários para o relatório
        $dadosRelatorio = $this->obterDadosRelatorio($periodo, $unidadeId, $funcionarios);

        // Processa os dados e gera o relatório
        $resultado = $this->relatorioService->processarRelatorio(
            $funcionarios,
            $periodo,
            $dadosRelatorio
        );

        return response()->json($resultado, 200);
    }

    public function gerarRelatorioIndividual(Request $request)
    {
        $dados = $request->all();

        return Excel::download(new RelatorioPontoIndividualExport($dados), 'relatorio_ponto.xlsx');
    }

    /**
     * Valida os dados da requisição
     */
    private function validarRequisicao(Request $request)
    {
        $request->validate([
            'unidade' => 'required|exists:unidades,id',
            'mes'     => 'required|date_format:m',
            'ano'     => 'required|date_format:Y',
        ]);
    }

    /**
     * Obtém os funcionários com base nas permissões do usuário
     */
    private function obterFuncionarios($unidadeId)
    {
        $query = Funcionario::query();
        $user  = Auth::user();

        if (! $user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id);
        } else {
            $query->where('unidade_id', $unidadeId);
        }

        return $query->get();
    }

    /**
     * Define o período do relatório (mês completo + semanas completas)
     */
    private function definirPeriodo($ano, $mes)
    {
        $inicioPeriodo = Carbon::create($ano, $mes, 1)->startOfWeek();
        $fimPeriodo    = Carbon::create($ano, $mes)->endOfMonth()->endOfWeek();

        return CarbonPeriod::create($inicioPeriodo, $fimPeriodo);
    }
    private function definirPeriodoMes($ano, $mes)
    {
        $inicioPeriodo = Carbon::create($ano, $mes, 1);
        $fimPeriodo    = Carbon::create($ano, $mes)->endOfMonth();

        return CarbonPeriod::create($inicioPeriodo, $fimPeriodo);
    }

    /**
     * Preenche dados de dias não úteis se necessário
     */
    private function preencherDiasNaoUteis()
    {
        $this->diaNaoUtilService->preencherFinaisDeSemana();
        $this->diaNaoUtilService->preencherFeriados();
    }

    /**
     * Obtém todos os dados necessários para o relatório
     */
    private function obterDadosRelatorio($periodo, $unidadeId, $funcionarios)
    {
        $inicioPeriodo   = $periodo->getStartDate();
        $fimPeriodo      = $periodo->getEndDate();
        $funcionariosIds = $funcionarios->pluck('id');

        return [
            'diasNaoUteis'   => $this->obterDiasNaoUteis($inicioPeriodo, $fimPeriodo, $unidadeId),
            'ferias'         => $this->obterFerias($inicioPeriodo, $fimPeriodo, $funcionariosIds),
            'recessos'       => $this->obterRecessos($unidadeId, $inicioPeriodo, $fimPeriodo),
            'justificativas' => $this->obterJustificativas($inicioPeriodo, $fimPeriodo, $funcionariosIds),
            'registrosPonto' => $this->obterRegistrosPonto($inicioPeriodo, $fimPeriodo, $funcionariosIds),
        ];
    }

    /**
     * Obtém dias não úteis no período
     */
    private function obterDiasNaoUteis($inicioPeriodo, $fimPeriodo, $unidadeId)
    {
        $unidade = Unidade::with('localidade')->where('id', $unidadeId)->first();

        $unidadeSetorId = $unidade->localidade->setor_id;

        return DiaNaoUtil::where('setor_id', $unidadeSetorId)
            ->whereBetween('data', [$inicioPeriodo, $fimPeriodo])
            ->get()
            ->keyBy('data');
    }

    /**
     * Obtém férias no período
     */
    private function obterFerias($inicioPeriodo, $fimPeriodo, $funcionariosIds)
    {
        return Feria::whereIn('funcionario_id', $funcionariosIds)
            ->whereBetween('data', [$inicioPeriodo, $fimPeriodo])
            ->get()
            ->groupBy('funcionario_id')
            ->map(fn($g) => $g->keyBy('data'));
    }

    /**
     * Obtém recessos no período
     */
    private function obterRecessos($unidadeId, $inicioPeriodo, $fimPeriodo)
    {
        $unidade = Unidade::with('localidade')->where('id', $unidadeId)->first();

        $unidadeSetorId = $unidade->localidade->setor_id;

        return Recesso::whereBetween('data', [$inicioPeriodo, $fimPeriodo])
            ->where(function ($q) use ($unidadeId, $unidadeSetorId) {
                $q->where('unidade_id', $unidadeId)
                    ->orWhere('setor_id', $unidadeSetorId);
            })
            ->get()
            ->groupBy('setor_id')
            ->map(fn($g) => $g->keyBy('data'));
    }

    /**
     * Obtém justificativas no período
     */
    private function obterJustificativas($inicioPeriodo, $fimPeriodo, $funcionariosIds)
    {
        return Justificativa::whereIn('funcionario_id', $funcionariosIds)
            ->whereBetween('data', [$inicioPeriodo, $fimPeriodo])
            ->get()
            ->groupBy('funcionario_id')
            ->map(fn($g) => $g->keyBy('data'));
    }

    /**
     * Obtém registros de ponto no período
     */
    private function obterRegistrosPonto($inicioPeriodo, $fimPeriodo, $funcionariosIds)
    {
        return RegistroPonto::whereIn('funcionario_id', $funcionariosIds)
            ->whereBetween('data_local', [$inicioPeriodo, $fimPeriodo])
            ->get()
            ->groupBy('funcionario_id')
            ->map(fn($grupo) => $grupo->groupBy(fn($r) => Carbon::parse($r->data_local)->toDateString()));
    }
}
