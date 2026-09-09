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
        $this->middleware('permission:fechar_banco_horas')->only(['fecharMesFuncionario', 'fecharMes', 'reabrirMes']);
    }

    /**
     * Extrato do mês.
     * Ex: GET /banco-horas/extrato?mes=2026-08&unidade_id=1
     *
     * Por padrão o extrato é filtrado pela unidade do usuário logado. Para
     * visualizar todas as unidades do setor, envie unidade_id=todas (ou
     * omita o parâmetro completamente).
     */
    public function extrato(Request $request)
    {
        $request->validate([
            'mes'        => 'required|date_format:Y-m',
            'unidade_id' => 'nullable|string',
        ]);

        $unidadeId = $this->resolverUnidadeId($request);

        $setorId = auth()->user()->setor_id;

        $funcionarios = Funcionario::doSetor($setorId)
            ->when($unidadeId, function ($query) use ($unidadeId) {
                $query->where('unidade_id', $unidadeId);
            })
            ->pluck('id');

        $registros = BancoHorasMensal::with('funcionario:id,nome')
            ->whereIn('funcionario_id', $funcionarios)
            ->where(
                'mes_referencia',
                Carbon::parse($request->input('mes') . '-01')->toDateString()
            )
            ->join('funcionarios', 'banco_horas_mensal.funcionario_id', '=', 'funcionarios.id')
            ->orderBy('funcionarios.nome')
            ->select('banco_horas_mensal.*')
            ->get();

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
            'unidade_id' => 'nullable|integer',
        ]);

        $unidadeId = $validated['unidade_id'] ?? null;

        $unidadeId = $this->resolverUnidadeId($request);

        $fechamentos = $this->service->fecharMesSetor(
            auth()->user()->setor_id,
            $validated['mes'] . '-01',
            auth()->id(),
            $unidadeId
        );

        $escopo = $unidadeId ? 'da unidade selecionada' : 'de todas as unidades';

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
}
