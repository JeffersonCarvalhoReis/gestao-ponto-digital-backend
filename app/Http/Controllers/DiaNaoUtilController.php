<?php

namespace App\Http\Controllers;

use App\Models\DiaNaoUtil;
use App\Services\DiaNaoUtilService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiaNaoUtilController extends Controller
{
    protected $service;

    public function __construct(DiaNaoUtilService $service)
    {
        $this->service = $service;

        $this->middleware('permission:visualizar_dias_nao_uteis')->only('index');
        $this->middleware('permission:registrar_dias_nao_uteis')->only('store');
        $this->middleware('permission:visualizar_dias_nao_uteis')->only('show');
        $this->middleware('permission:editar_dias_nao_uteis')->only('update');
        $this->middleware('permission:excluir_dias_nao_uteis')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DiaNaoUtil::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date_format:d/m/Y',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date_format:d/m/Y',
            'tipo' => 'required|in:feriado,final_de_semana',
            'descricao' => 'nullable|string'
        ]);


        $dataInicio = Carbon::createFromFormat('d/m/Y', $validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::createFromFormat('d/m/Y', $validated['data_fim']) : $dataInicio;


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
            'data' => 'sometimes||date_format:d/m/Y|unique:dias_nao_uteis,data,' . $diaNaoUtil->id,
            'tipo' => 'sometimes|in:final_de_semana,feriado',
            'descricao' => 'nullable|string'
        ]);

        if (isset($validated['data'])) {
            $validated['data'] = Carbon::createFromFormat('d/m/Y', $validated['data'])->format('Y-m-d');
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
}
