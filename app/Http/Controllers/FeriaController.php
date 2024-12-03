<?php

namespace App\Http\Controllers;

use App\Models\Feria;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_ferias')->only('index');
        $this->middleware('permission:registrar_ferias')->only('store');
        $this->middleware('permission:excluir_ferias')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id'
        ]);


        $ferias = Feria::where('funcionario_id', $validated['funcionario_id'])->get();
        return response()->json($ferias, 200, );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date_format:d/m/Y',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date_format:d/m/Y',
            'funcionario_id' => 'required|exists:funcionarios,id',
        ]);


        $dataInicio = Carbon::createFromFormat('d/m/Y', $validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::createFromFormat('d/m/Y', $validated['data_fim']) : $dataInicio;


        $datas = $dataInicio->daysUntil($dataFim)->map(function($data) use ($validated){
            return [
                'data' => $data->toDateString(),
                'funcionario_id' => $validated['funcionario_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Feria::insert(collect($datas)->toArray() );

        return response()->json([
            'message' => 'Férias adicionadas com sucesso!',
        ], 201);
    }

       /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date_format:d/m/Y',
            'data_fim' => 'required|after_or_equal:data_inicio|date_format:d/m/Y',
            'funcionario_id' => 'required|exists:funcionarios,id',
        ]);

        $dataInicio = Carbon::createFromFormat('d/m/Y', $validated['data_inicio'])->toDateString();
        $dataFim = Carbon::createFromFormat('d/m/Y', $validated['data_fim'])->toDateString();

        $totalApagado = Feria::where('funcionario_id', $validated['funcionario_id'])
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->delete();

            return response()->json([
                'message' => 'Férias removidas com sucesso!',
                'registro_apagados' => $totalApagado
                ], 200);
    }
}
