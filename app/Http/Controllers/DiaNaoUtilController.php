<?php

namespace App\Http\Controllers;

use App\Models\DiaNaoUtil;
use App\Models\Recesso;
use App\Services\DiaNaoUtilService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiaNaoUtilController extends Controller
{
    protected $service;

    public function __construct(DiaNaoUtilService $service)
    {
        $this->service = $service;

        $this->middleware('permission:visualizar_dias_nao_uteis')->only(['index', 'show']);
        $this->middleware('permission:registrar_dias_nao_uteis')->only('store');
        $this->middleware('permission:editar_dias_nao_uteis')->only('update');
        $this->middleware('permission:excluir_dias_nao_uteis')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $anoAtual = Carbon::now()->year;

        $feriadosExistentes = DiaNaoUtil::where('tipo', 'feriado')
            ->whereYear('created_at', $anoAtual)
            ->exists();

        if (!$feriadosExistentes) {
            $this->preencherFeriados();
        }

        $finaisDeSemanaExistentes = DiaNaoUtil::where('tipo', 'final_de_semana')
            ->whereYear('created_at', $anoAtual)
            ->exists();

        if (!$finaisDeSemanaExistentes) {
            $this->preencherFinaisDeSemana();
        }

        return DiaNaoUtil::all();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date',
            'tipo' => 'required|in:feriado,final_de_semana',
            'descricao' => 'nullable|string'
        ]);


        $dataInicio = Carbon::create($validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::create($validated['data_fim']) : $dataInicio;


        $datas = $dataInicio->daysUntil($dataFim)->map(function($data) use ($validated){
            return [
                'data' => $data->toDateString(),
                'tipo' => $validated['tipo'],
                'descricao' => $validated['descricao'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        DiaNaoUtil::upsert(
            collect($datas)->toArray(),
            ['data'],
            ['tipo', 'descricao', 'updated_at']
        );

        return response()->json([
            'message' => 'Dias não úteis adicionados com sucesso!',
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $diaNaoUtil = DiaNaoUtil::findOrFail($id);

        $validated = $request->validate([
            'data' => 'sometimes|date|unique:dias_nao_uteis,data,' . $diaNaoUtil->id,
            'tipo' => 'sometimes|in:final_de_semana,feriado',
            'descricao' => 'nullable|string'
        ]);

        if (isset($validated['data'])) {
            $validated['data'] = Carbon::create($validated['data']);
        } else{
            $validated['data'] = $diaNaoUtil->data;
        }

        $diaNaoUtil->upsert(
            $validated,
            ['data'],
            ['tipo', 'descricao', 'updated_at']
        );

        return response()->json([
            'message' => 'Dia não útil atualizado com sucesso',
            'data' => $diaNaoUtil
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $diaNaoUtil = DiaNaoUtil::findOrFail($id);
        $diaNaoUtil->delete();

        return response()->json([
            'message' => 'Dia não útil removido com sucesso'
        ], 200);
    }

    public function preencherFinaisDeSemana()
    {
        $this->service->preencherFinaisDeSemana();

        return response()->json([
            'message' => 'Finais de semana adicionais automaticamente'
        ], 200);
    }

    public function preencherFeriados()
    {
        try {
            $this->service->preencherFeriados();

            return response()->json(['message' => 'Feriados adicionados automaticamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function proximosFeriados() {

        $hoje = Carbon::now()->format('Y-m-d');
        $proximosDias = Carbon::now()->addDays(30)->format('Y-m-d');

        $query = Recesso::query();

        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'super admin'])) {
            $query->where('unidade_id', $user->unidade_id)->orWhere('unidade_id', null);
        }

        $feriados = DiaNaoUtil::where('tipo', 'feriado')->whereBetween('data', [$hoje, $proximosDias])
        ->get(['data', 'descricao', 'tipo']);

        $recessos = $query->where(function ($query) use ($hoje, $proximosDias) {
            $query->whereBetween('data_inicio', [$hoje, $proximosDias])
                  ->orWhereBetween('data_fim', [$hoje, $proximosDias]);
        })
        ->distinct()
        ->get(['data_inicio', 'data_fim', 'descricao', 'tipo']);

        $feriadosRecessos = [...$recessos, ...$feriados];

        return response()->json([
            'proximos_feriados' => $feriadosRecessos,
        ], 200);
    }
}
