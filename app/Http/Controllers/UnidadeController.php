<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnidadeResource;
use App\Models\Localidade;
use App\Models\Unidade;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:visualizar_unidades')->only(['index', 'show']);
        $this->middleware('permission:criar_unidades')->only('store');
        $this->middleware('permission:atualizar_unidades')->only('update');
        $this->middleware('permission:excluir_unidades')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Unidade::query()->with('localidade');

        $query->when($request->nome, function ( $query, $nome ) {
            $query->where('nome','like', "%$nome%");
        });

        $perPage = $request->input('per_page', 10);
        if($perPage == -1) {
            $perPage = Unidade::count();
        }

        $sortBy = $request->sortBy;
        $query->when( $request->order, function ($query, $order) use ($sortBy) {
            if ($sortBy === 'localidade') {
                $query->whereHas('localidade')
                    ->orderBy(
                        Localidade::select('nome')
                            ->whereColumn('localidades.id', 'unidades.localidade_id'),
                        $order
                    );
                }
                else {
                    $query->orderBy($sortBy, $order);
                }
        });

        $unidadesPaginada= $query->paginate($perPage);
        $unidades = UnidadeResource::collection($unidadesPaginada);

        return response()->json([
            'data' => $unidades,
            'meta' => [
                'current_page' => $unidadesPaginada->currentPage(),
                'last_page' => $unidadesPaginada->lastPage(),
                'per_page' => $unidadesPaginada->perPage(),
                'total' => $unidadesPaginada->total(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
                'nome' => 'required|min:2|unique:unidades,nome',
                'localidade_id' => 'required|numeric|exists:localidades,id',
                'cnes' => 'nullable|numeric|unique:unidades,cnes',
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
                'localidade_id' => 'sometimes|numeric|exists:localidades,id',
                'cnes' => 'nullable|numeric|unique:unidades,cnes,'.$id,
            ]);

            $unidade->update($data);
            $unidade = new UnidadeResource($unidade);

            return response()->json([
                'message' => 'Unidade atualizada com sucesso',
                 'Unidade' => $unidade
                ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

            $unidade = Unidade::findOrFail($id);
            $unidade->delete();

            return response()->json([
                'message' => 'Unidade excluída com sucesso.'
            ], 200);
    }
}
