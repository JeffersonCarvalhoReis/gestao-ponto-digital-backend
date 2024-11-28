<?php

namespace App\Http\Controllers;

use App\Models\DiaNaoUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiaNaoUtilController extends Controller
{
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
            'data' => 'required|date|unique:dias_nao_uteis,data',
            'tipo' => 'required|in:final_de_semana,feriado,recesso,ferias',
            'funcionario_id' => 'nullable|exists:funcionarios,id',
            'descricao' => 'nullable|string',
         ]);

         $diaNaoUtil = DiaNaoUtil::create($validated);

         return response()->json([
            'message' => 'Dia nâo útil adicionado com sucesso!',
            'data' => $diaNaoUtil
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
            'tipo' => 'sometimes|in:final_de_semana,feriado,recesso,ferias',
            'funcionario_id' => 'nullable|exists:funcionarios,id',
            'descricao' => 'nullable|string'
        ]);

        $diaNaoUtil->update($validated);
        return response()->json([
            'message' => 'Dia não útil atulizado com sucesso',
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
        $hoje = Carbon::now();
        $fimDoAno = Carbon::now()->endOfYear();

        $finaisDeSemana = [];
        while ($hoje->lte($fimDoAno)) {
            if($hoje->isSunday()) {
                $finaisDeSemana[] = [
                    'data' => $hoje->toDateString(),
                    'tipo' => 'final_de_semana',
                    'descricao' => 'Domingo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($hoje->isSaturday()) {
                $finaisDeSemana[] = [
                    'data' => $hoje->toDateString(),
                    'tipo' => 'final_de_semana',
                    'descricao' => 'Sábado',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $hoje->addDay();
        }

        DiaNaoUtil::insert($finaisDeSemana);

        return response()->json([
            'message' => 'Finais de semana adicionais automaticamente'
        ], 200);
    }
}
