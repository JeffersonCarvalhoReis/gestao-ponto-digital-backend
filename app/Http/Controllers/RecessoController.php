<?php

namespace App\Http\Controllers;

use App\Models\Recesso;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecessoController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:visualizar_recessos')->only('index');
        // $this->middleware('permission:registrar_recessos')->only('store');
        // $this->middleware('permission:excluir_recessos')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'unidade' => 'nullable|exists:unidade,id'
        ]);

        $query = Recesso::query();

        if (isset($validated['unidade_id'])) {

            $query->where('unidade_id', $validated['unidade_id']);
        }

        $recesso = $query->get();

        return response()->json($recesso, 200, );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date_format:d/m/Y',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date_format:d/m/Y',
            'unidade_id' => 'nullable|exists:unidades,id',
        ]);


        $dataInicio = Carbon::createFromFormat('d/m/Y', $validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::createFromFormat('d/m/Y', $validated['data_fim']) : $dataInicio;


        $datas = $dataInicio->daysUntil($dataFim)->map(function($data) use ($validated){
            return [
                'data' => $data->toDateString(),
                'unidade_id' => $validated['unidade_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Recesso::insert(collect($datas)->toArray() );

        return response()->json([
            'message' => 'Recesso registrado com sucesso!',
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
            'unidade_id' => 'nullable|exists:unidades,id',
        ]);

        $dataInicio = Carbon::createFromFormat('d/m/Y', $validated['data_inicio'])->toDateString();
        $dataFim = Carbon::createFromFormat('d/m/Y', $validated['data_fim'])->toDateString();

        $query = Recesso::query();

        if (isset($validated['unidade_id'])) {

            $query->where('unidade_id' , $validated['unidade_id']);
        } else {
            $query->whereNull('unidade_id');
        }
        $totalApagado = $query->whereBetween('data', [$dataInicio, $dataFim])
                  ->delete();

        return response()->json([
            'message' => 'Recesso removido com sucesso!',
            'registro_apagados' => $totalApagado
            ], 200);
    }
}
