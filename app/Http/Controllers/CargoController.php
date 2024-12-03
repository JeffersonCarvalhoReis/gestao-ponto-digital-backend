<?php

namespace App\Http\Controllers;

use App\Http\Resources\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\Request;

class CargoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_cargos')->only('index');
        $this->middleware('permission:registrar_cargos')->only('store');
        $this->middleware('permission:visualizar_cargos')->only('show');
        $this->middleware('permission:editar_cargos')->only('update');
        $this->middleware('permission:excluir_cargos')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cargo::query();
        if($request->has('nome')){
            $cargos = $request->input('nome');
            $query->where('nome','like', "%$cargos%");
        }
        $cargos = $query->get();
        $cargos = CargoResource::collection($cargos);
        return response()->json($cargos, 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
                'nome' => 'required|min:2'
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
