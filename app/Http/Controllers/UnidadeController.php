<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnidadeResource;
use App\Models\Unidade;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:visualizar_unidades')->only('index');
        $this->middleware('permission:registrar_unidades')->only('store');
        $this->middleware('permission:visualizar_unidades')->only('show');
        $this->middleware('permission:editar_unidades')->only('update');
        $this->middleware('permission:excluir_unidades')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Unidade::query();
        if($request->has('nome')){
            $Unidades = $request->input('nome');
            $query->where('nome','like', "%$Unidades%");
        }
        $Unidades = $query->get();
        $Unidades = UnidadeResource::collection($Unidades);
        return response()->json($Unidades, 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
                'nome' => 'required|min:2',
                'localidade_id' => 'required|numeric|exists:localidades,id'
        ]);

       Unidade::create($data);

        return response()->json(['message' => 'Unidade criada com sucesso.'], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
            $unidade = Unidade::findOrFail($id);
            $unidade = new UnidadeResource($unidade);
            return response()->json($unidade, 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
            $unidade = Unidade::findOrFail($id);
            $data = $request->validate([
                'nome' => 'sometimes|min:2',
                'localidade_id' => 'sometimes|numeric|exists:localidades,id'
            ]);

            $unidade->update($data);
            $unidade = new UnidadeResource($unidade);

            return response()->json(['message' => 'Unidade atualizada com sucesso', 'Unidade' => $unidade], 200);


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

            $unidade = Unidade::findOrFail($id);
            $unidade->delete();

            return response()->json(['message' => 'Unidade excluída com sucesso.'], 200);

    }
}
