<?php
namespace App\Http\Controllers;

use App\Services\EscalaService;
use Illuminate\Http\Request;

class EscalaController extends Controller
{
    protected $service;

    public function __construct(EscalaService $service)
    {
        $this->service = $service;

        $this->middleware('permission:visualizar_escalas')->only('grade');
        $this->middleware('permission:registrar_escalas')->only(['definirDia', 'definirDiasEmLote', 'copiarMes']);
        $this->middleware('permission:excluir_escalas')->only('removerDia');
    }

    /**
     * Retorna a grade do mês (funcionários x dias) para um setor.
     * Ex: GET /escalas/grade?mes=2026-08&unidade_id=1
     *
     * Por padrão a grade é filtrada pela unidade do usuário logado. Para
     * visualizar todas as unidades do setor, envie unidade_id=todas (ou
     * omita o parâmetro completamente equivale à unidade do usuário).
     */
    public function grade(Request $request)
    {
        $validated = $request->validate([
            'mes'        => 'required|date_format:Y-m',
            'unidade_id' => 'nullable|string',
        ]);

        $unidadeId = $this->resolverUnidadeId($request);

        $grade = $this->service->grade(
            auth()->user()->setor_id,
            $validated['mes'] . '-01',
            $unidadeId
        );

        return response()->json($grade, 200);
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
     * Define/atualiza o turno de um funcionário em um dia específico
     * (uma célula da grade).
     */
    public function definirDia(Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id',
            'data'           => 'required|date',
            'codigo'         => 'required|string|max:10',
            'observacao'     => 'nullable|string|max:255',
        ]);
        $setor = auth()->user()->setor_id;

        try {
            $escala = $this->service->definirDia(
                $validated['funcionario_id'],
                $validated['data'],
                $validated['codigo'],
                $setor,
                $validated['observacao'] ?? null,
                auth()->id()
            );

            return response()->json([
                'message' => 'Escala atualizada com sucesso!',
                'data'    => $escala->load('tipoTurno'),
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Recebe várias células alteradas de uma vez (edição em lote na grade).
     */
    public function definirDiasEmLote(Request $request)
    {
        $validated = $request->validate([
            'lancamentos'                  => 'required|array|min:1',
            'lancamentos.*.funcionario_id' => 'required|exists:funcionarios,id',
            'lancamentos.*.data'           => 'required|date',
            'lancamentos.*.codigo'         => 'required|string|max:10',
            'lancamentos.*.observacao'     => 'nullable|string|max:255',
        ]);

        $setor = auth()->user()->setor_id;

        try {
            $resultados = $this->service->definirDiasEmLote(
                $validated['lancamentos'],
                $setor ?? null,

                auth()->id()
            );

            return response()->json([
                'message' => count($resultados) . ' dia(s) atualizado(s) com sucesso!',
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Limpa a célula (funcionário fica sem turno definido naquele dia).
     */
    public function removerDia(Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id',
            'data'           => 'required|date',
        ]);

        $this->service->removerDia($validated['funcionario_id'], $validated['data']);

        return response()->json(['message' => 'Escala removida com sucesso!'], 200);
    }

    /**
     * Copia a grade inteira de um mês para outro (mesmo padrão por
     * funcionário), evitando remontar a escala do zero todo mês.
     */
    public function copiarMes(Request $request)
    {
        $validated = $request->validate([
            'mes_origem'  => 'required|date_format:Y-m',
            'mes_destino' => 'required|date_format:Y-m',
        ]);

        $setor = auth()->user()->setor_id;

        $total = $this->service->copiarMes(
            $setor,
            $validated['mes_origem'] . '-01',
            $validated['mes_destino'] . '-01',
            auth()->id()
        );

        return response()->json([
            'message' => "{$total} dia(s) copiado(s) com sucesso!",
        ], 200);
    }
}
