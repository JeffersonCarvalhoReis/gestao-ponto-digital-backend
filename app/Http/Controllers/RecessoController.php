<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecessoResource;
use App\Models\Recesso;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RecessoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_recessos')->only('index');
        $this->middleware('permission:registrar_recessos')->only('store');
        $this->middleware('permission:excluir_recessos')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Recesso::with('unidade');

        if(!$user->hasRole('super admin')) {

            $query->where('setor_id', $user->setor_id);

        };

        $query->when($request->unidade_id, function ($query, $unidade_id) {
            $query->where('unidade_id', $unidade_id);

        });

        $recesso = $query->get();
        $recesso =  RecessoResource::collection($recesso);

        return response()->json($recesso, 200, );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date',
            'unidade_id' => 'nullable|exists:unidades,id',
        ]);

        $dataInicio = Carbon::create($validated['data_inicio']);
        $dataFim = isset($validated['data_fim']) ? Carbon::create( $validated['data_fim']) : $dataInicio;


        $datas = $dataInicio->daysUntil($dataFim)->map(function($data) use ($validated, $dataInicio, $dataFim, $user){
            return [
                'data' => $data->toDateString(),
                'unidade_id' => $validated['unidade_id'] ?? null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'tipo' => 'recesso',
                'descricao' => 'Recesso',
                'setor_id' => $user->setor_id,
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
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|after_or_equal:data_inicio|date',
            'unidade_id' => 'nullable|exists:unidades,id',
        ]);

        $dataInicio = Carbon::create($validated['data_inicio'])->toDateString();
        $dataFim = Carbon::create($validated['data_fim'])->toDateString();

        $query = Recesso::query();

        if (isset($validated['unidade_id'])) {

            $query->where('unidade_id' , $validated['unidade_id']);
        }

        $totalApagado = $query->whereBetween('data', [$dataInicio, $dataFim])
                  ->delete();

        return response()->json([
            'message' => 'Recesso removido com sucesso!',
            'registro_apagados' => $totalApagado
            ], 200);
    }
}
