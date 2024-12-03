<?php

namespace App\Http\Controllers;

use App\Http\Resources\LocalidadeResource;
use App\Models\Localidade;
use Illuminate\Http\Request;

class LocalidadeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:visualizar_localidades')->only('index');
        $this->middleware('permission:registrar_localidades')->only('store');
        $this->middleware('permission:visualizar_localidades')->only('show');
        $this->middleware('permission:editar_localidades')->only('update');
        $this->middleware('permission:excluir_localidades')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Localidade::query();
        if($request->has('nome')){
            $localidades = $request->input('nome');
            $query->where('nome','like', "%$localidades%");
        }
        $localidades = $query->get();
        $localidades = LocalidadeResource::collection($localidades);
        return response()->json($localidades, 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
                'nome' => 'required|min:2|unique:localidades,nome'
        ]);

       Localidade::create($data);

        return response()->json([
            'message' => 'Localidade criada com sucesso.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $localidade = Localidade::findOrFail($id);
        $localidade = new LocalidadeResource($localidade);
        return response()->json($localidade, 200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $localidade = Localidade::findOrFail($id);

        $data = $request->validate([
            'nome' => 'required|min:2'
        ]);

        $localidade->update($data);
        $localidade = new LocalidadeResource($localidade);

        return response()->json([
            'message' => 'Localidade atualizada com sucesso',
            'Localidade' => $localidade
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $localidade = Localidade::findOrFail($id);
        $localidade->delete();

        return response()->json([
            'message' => 'Localidade excluída com sucesso.'
        ], 200);
    }
}
