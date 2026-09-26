<?php
namespace App\Http\Controllers;

use App\Models\BancoHorasMensal;
use App\Models\Funcionario;
use App\Services\BancoHorasService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BancoHorasController extends Controller
{
    protected $service;

    public function __construct(BancoHorasService $service)
    {
        $this->service = $service;

        $this->middleware('permission:visualizar_banco_horas')->only(['extrato', 'extratoFuncionario']);
        $this->middleware('permission:fechar_banco_horas')->only(['fecharMesFuncionario', 'fecharMes', 'reabrirMes', 'reabrirMesTodos']);
    }

    /**
     * Extrato do mês.
     * Ex: GET /banco-horas/extrato?mes=2026-08&unidade_id=1
     *
     * Por padrão o extrato é filtrado pela unidade do usuário logado. Para
     * visualizar todas as unidades do setor, envie unidade_id=todas (ou
     * omita o parâmetro completamente).
     */
    /**
     * Extrato do mês.
     * Ex: GET /banco-horas/extrato?mes=2026-08&unidade_id=1
     *
     * Por padrão o extrato é filtrado pela unidade do usuário logado. Para
     * visualizar todas as unidades do setor, envie unidade_id=todas (ou
     * omita o parâmetro completamente).
     *
     * Retorna uma linha por funcionário ativo do escopo, esteja o mês
     * fechado ou não: se já existe um fechamento oficial (banco_horas_mensal),
     * usa esse valor congelado; caso contrário, calcula o saldo "ao vivo"
     * (não oficial) e marca `oficial: false`, para deixar claro na tela que
     * aquele número ainda pode mudar até alguém fechar o mês de fato.
     */
    public function extrato(Request $request)
    {
        $request->validate([
            'mes'        => 'required|date_format:Y-m',
            'unidade_id' => 'nullable|string',
        ]);

        $unidadeId = $this->resolverUnidadeId($request);

        $setorId = auth()->user()->setor_id;

        $mesReferencia = Carbon::parse($request->input('mes') . '-01')->toDateString();

        $funcionarios = Funcionario::doSetor($setorId)
            ->where('status', true)
            ->when($unidadeId, function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $fechamentos = BancoHorasMensal::whereIn('funcionario_id', $funcionarios->pluck('id'))
            ->where('mes_referencia', $mesReferencia)
            ->get()
            ->keyBy('funcionario_id');

        $registros = $funcionarios->map(function ($funcionario) use ($fechamentos, $mesReferencia) {
            $fechamento = $fechamentos->get($funcionario->id);

            // Importante: checar o status real da linha, não só se ela
            // existe. Uma linha "reaberta" (reabrirMes/reabrirMesSetor)
            // continua existindo em banco_horas_mensal, só que com
            // status = 'aberto' — nesse caso ela deve voltar a se comportar
            // como prévia ao vivo, e não continuar sendo tratada como
            // fechamento oficial.
            if ($fechamento && $fechamento->status === 'fechado') {
                return [
                    'id'                 => $fechamento->id,
                    'funcionario_id'     => $funcionario->id,
                    'funcionario'        => ['id' => $funcionario->id, 'nome' => $funcionario->nome],
                    'mes_referencia'     => $fechamento->mes_referencia,
                    'minutos_previstos'  => $fechamento->minutos_previstos,
                    'minutos_realizados' => $fechamento->minutos_realizados,
                    'minutos_extra'      => $fechamento->minutos_extra,
                    'saldo_minutos'      => $fechamento->saldo_minutos,
                    'status'             => 'fechado',
                    'oficial'            => true,
                ];
            }

            $resumo = $this->service->calcularResumoMes($funcionario->id, $mesReferencia);

            return [
                'id'                 => null,
                'funcionario_id'     => $funcionario->id,
                'funcionario'        => ['id' => $funcionario->id, 'nome' => $funcionario->nome],
                'mes_referencia'     => $mesReferencia,
                'minutos_previstos'  => $resumo['minutos_previstos'],
                'minutos_realizados' => $resumo['minutos_realizados'],
                'minutos_extra'      => $resumo['minutos_extra'],
                'saldo_minutos'      => $resumo['saldo_minutos'],
                'status'             => 'aberto',
                'oficial'            => false,
            ];
        })->values();

        return response()->json(['data' => $registros], 200);
    }

    /**
     * Resolve o unidade_id a ser aplicado no filtro a partir da requisição:
     * - Parâmetro ausente: usa a unidade do usuário logado (padrão).
     * - Parâmetro "todas" ou vazio: sem filtro (todas as unidades do setor).
     * - Caso contrário: usa o id numérico informado.
     */
    private function resolverUnidadeId(Request $request): ?int
    {
        if (! $request->has('unidade_id')) {
            return auth()->user()->unidade_id;
        }

        $valor = $request->input('unidade_id');

        if ($valor === 'todas' || $valor === '' || $valor === null) {
            return null;
        }

        return (int) $valor;
    }

    /**
     * Extrato de um único funcionário (histórico mês a mês).
     */
    public function extratoFuncionario(Funcionario $funcionario)
    {
        $registros = BancoHorasMensal::where('funcionario_id', $funcionario->id)
            ->orderByDesc('mes_referencia')
            ->get();

        return response()->json(['data' => $registros], 200);
    }

    /**
     * Fecha o mês de um único funcionário.
     */
    public function fecharMesFuncionario(Request $request, Funcionario $funcionario)
    {
        $validated = $request->validate([
            'mes' => 'required|date_format:Y-m',
        ]);

        $fechamento = $this->service->fecharMesFuncionario(
            $funcionario->id,
            $validated['mes'] . '-01',
            auth()->id()
        );

        return response()->json([
            'message' => 'Mês fechado com sucesso!',
            'data'    => $fechamento,
        ], 200);
    }

    /**
     * Fecha o mês de todos os funcionários de uma vez.
     *
     * O fechamento respeita a mesma seleção de unidade usada no extrato:
     * se "unidade_id" for informado (e não for "todas"), fecha apenas os
     * funcionários daquela unidade; caso contrário, fecha todo o setor.
     */
    public function fecharMes(Request $request)
    {
        $validated = $request->validate([
            'mes'        => 'required|date_format:Y-m',
            'unidade_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== 'todas' && ! is_numeric($value)) {
                        $fail('A unidade deve ser um ID numérico ou "todas".');
                    }
                },
            ],
        ]);

        $unidadeId = $validated['unidade_id'] ?? null;

        if ($unidadeId === 'todas') {
            $unidadeId = null;
        } else {
            $unidadeId = $unidadeId !== null
                ? (int) $unidadeId
                : null;
        }

        $fechamentos = $this->service->fecharMesSetor(
            auth()->user()->setor_id,
            $validated['mes'] . '-01',
            auth()->id(),
            $unidadeId
        );

        $escopo = $unidadeId
            ? 'da unidade selecionada'
            : 'de todas as unidades';

        return response()->json([
            'message' => count($fechamentos) . " funcionário(s) {$escopo} com o mês fechado!",
            'data' => $fechamentos,
        ], 200);
    }
    /**
     * Reabre um mês já fechado (ex: precisa corrigir ponto ou escala
     * depois do fechamento).
     */
    public function reabrirMes(Request $request, Funcionario $funcionario)
    {
        $validated = $request->validate([
            'mes' => 'required|date_format:Y-m',
        ]);

        $registro = $this->service->reabrirMes($funcionario->id, $validated['mes'] . '-01');

        if (! $registro) {
            return response()->json(['message' => 'Nenhum fechamento encontrado para este mês.'], 404);
        }

        return response()->json([
            'message' => 'Mês reaberto com sucesso!',
            'data'    => $registro,
        ], 200);
    }

    /**
     * Reabre o mês de todos os funcionários de uma vez (espelha fecharMes).
     * Respeita a mesma seleção de unidade usada no extrato: se "unidade_id"
     * for informado (e não for "todas"), reabre apenas aquela unidade;
     * caso contrário, reabre o setor inteiro. Quem já estava aberto
     * simplesmente não é afetado.
     */
    public function reabrirMesTodos(Request $request)
    {
        $validated = $request->validate([
            'mes'        => 'required|date_format:Y-m',
            'unidade_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== 'todas' && ! is_numeric($value)) {
                        $fail('A unidade deve ser um ID numérico ou "todas".');
                    }
                },
            ],
        ]);

        $unidadeId = $validated['unidade_id'] ?? null;

        if ($unidadeId === 'todas') {
            $unidadeId = null;
        } else {
            $unidadeId = $unidadeId !== null
                ? (int) $unidadeId
                : null;
        }

        $reabertos = $this->service->reabrirMesSetor(
            auth()->user()->setor_id,
            $validated['mes'] . '-01',
            $unidadeId
        );

        $escopo = $unidadeId
            ? 'da unidade selecionada'
            : 'de todas as unidades';

        return response()->json([
            'message' => count($reabertos) . " funcionário(s) {$escopo} com o mês reaberto!",
            'data' => $reabertos,
        ], 200);
    }
}
