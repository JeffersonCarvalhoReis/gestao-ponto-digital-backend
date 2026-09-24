<?php

namespace App\Http\Controllers;

use App\Http\Resources\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\Request;

class CargoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_cargos')->only(['index', 'show']);
        $this->middleware('permission:criar_cargos')->only('store');
        $this->middleware('permission:editar_cargos')->only('update');
        $this->middleware('permission:excluir_cargos')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cargo::query();
        $query->when($request->nome, function ( $query, $nome ) {
            $query->where('nome','like', "%$nome%");
        });

        // Restringe aos cargos que de fato têm funcionário(s) cadastrado(s)
        // na unidade informada — usado pelos filtros de "cargo" que
        // dependem da unidade selecionada (ex: grade de escalas, pendências
        // de correção de ponto), para não listar cargos inexistentes ali.
        $query->when($request->unidade_id, function ($query, $unidadeId) {
            $query->whereHas('funcionarios', function ($q) use ($unidadeId) {
                $q->where('unidade_id', $unidadeId);
            });
        });

        // Quando não há unidade específica selecionada (ex: "Todas as
        // Unidades"), permite restringir aos cargos usados dentro de um
        // setor inteiro.
        $query->when(!$request->unidade_id && $request->setor_id, function ($query) use ($request) {
            $query->whereHas('funcionarios.unidade.localidade', function ($q) use ($request) {
                $q->where('setor_id', $request->setor_id);
            });
        });


        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = Cargo::count();
        }

        if (!$request->has('sortBy') && !$request->has('order')) {
            $query->orderBy('nome', 'asc');
        }
        $order = $request->order;
        $query->when( $request->sortBy, function ($query, $sortBy) use ($order){
            $query->orderBy($sortBy, $order);
        });

        $cargosPaginado= $query->paginate($perPage);
        $cargos = CargoResource::collection($cargosPaginado);

        return response()->json([
            'data' => $cargos,
            'meta' => [
                'current_page' => $cargosPaginado->currentPage(),
                'last_page' => $cargosPaginado->lastPage(),
                'per_page' => $cargosPaginado->perPage(),
                'total' => $cargosPaginado->total(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
                'nome' => 'required|string|unique:cargos|min:2'
        ]);

       Cargo::create($data);

        return response()->json([
            'message' => 'Cargo criado com sucesso.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cargo = Cargo::findOrFail($id);
        $cargo = new CargoResource($cargo);
        return response()->json($cargo, 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cargo = Cargo::findOrFail($id);

        $data = $request->validate([
            'nome' => 'required|min:2'
        ]);

        $cargo->update($data);
        $cargo = new CargoResource($cargo);

        return response()->json([
            'message' => 'Cargo atualizado com sucesso',
            'Cargo' => $cargo
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->delete();

        return response()->json([
            'message' => 'Cargo excluído com sucesso.'
        ], 200);
    }
}
