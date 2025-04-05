<?php

namespace App\Http\Controllers;

use App\Http\Resources\SetorResource;
use App\Models\Setor;
use Illuminate\Http\Request;

class SetorController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:gerenciar_setor');

    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Setor::query();

        $query->when($request->nome, function($query, $nome) {
            $query->where('nome', 'like',"%$nome%");
        });
        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = Setor::count();
        }

        if(!$request->order) {
            $query->orderBy('updated_at', 'desc');
        }

        $order = $request->order;
        $query->when( $request->sortBy, function ($query, $sortBy) use ($order){
            $query->orderBy($sortBy, $order);
        });

        $setoresPaginado = $query->paginate($perPage);

        $setores = SetorResource::collection($setoresPaginado);

        return response()->json([
            'data' => $setores,
            'meta' => [
                'current_page' => $setoresPaginado->currentPage(),
                'last_page' => $setoresPaginado->lastPage(),
                'per_page' => $setoresPaginado->perPage(),
                'total' => $setoresPaginado->total(),
            ],
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
           'nome' => 'required|min:2|string|unique:setores'
        ]);

        Setor::create($data);

        return response()->json(['message' => 'Setor criado com sucesso.'], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $setor = Setor::findOrFail($id);

        return response()->json($setor, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'nome' => 'sometimes|unique:setores,nome|min:2',
        ]);

        $setor = Setor::findOrFail($id);

        $setor->update($data);
        $setor = new SetorResource($setor);

        return response()->json([
            'message' => 'Setor atualizado com sucesso',
            'Setor' => $setor
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $setor = Setor::findOrFail($id);
        $setor->delete();

        return response()->json([
            'message' => 'Setor excluído com sucesso.'
        ], 200);
    }
}
